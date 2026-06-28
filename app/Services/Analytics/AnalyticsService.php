<?php

namespace App\Services\Analytics;

use App\Models\Branch;
use App\Models\SalesInvoice;
use App\Services\Branch\BranchContext;
use App\Support\DateRange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Predictive analytics over historical operational data. Everything respects the
 * active branch scope, so each method serves both a single-branch and a
 * consolidated (head-office) view. No external ML: forecasts use ordinary
 * least-squares trend fitting and velocity-based projections, which are
 * transparent, deterministic and cheap to compute.
 */
class AnalyticsService
{
    public function __construct(protected BranchContext $context) {}

    public const TTL = 600;

    /** Trailing window (days) used to measure sales velocity for inventory projections. */
    public const VELOCITY_WINDOW = 90;

    /** Target days of cover a reorder should replenish to. */
    public const REORDER_COVER_DAYS = 60;

    public function cacheKey(string $suffix): string
    {
        $tenant = $this->context->currentTenantId() ?? 'x';
        $branch = $this->context->currentBranchId() ?? 'all';

        return "analytics:{$tenant}:{$branch}:{$suffix}:".now()->toDateString();
    }

    public function refresh(): void
    {
        foreach (['forecast', 'products', 'customers', 'inventory', 'branches'] as $suffix) {
            Cache::forget($this->cacheKey($suffix));
        }
    }

    /**
     * Branch ids in the current scope (single branch, or all allowed when consolidated).
     *
     * @return list<int>
     */
    protected function scopeBranchIds(): array
    {
        $current = $this->context->currentBranchId();

        return $current !== null
            ? [$current]
            : $this->context->allowedBranchIds()->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Monthly revenue history plus an N-month linear-regression projection.
     *
     * @return array{labels:list<string>, actual:list<float|null>, forecast:list<float|null>, growth:float}
     */
    public function salesForecast(int $history = 12, int $ahead = 6): array
    {
        return Cache::remember($this->cacheKey('forecast'), self::TTL, function () use ($history, $ahead) {
            $labels = [];
            $actual = [];
            $values = [];

            for ($i = $history - 1; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $value = $this->monthlyRevenue($month);
                $labels[] = $month->format('M Y');
                $actual[] = $value;
                $values[] = $value;
            }

            [$slope, $intercept] = $this->linearFit($values);

            // The forecast series re-anchors on the last actual point for a continuous line.
            $forecast = array_fill(0, count($values), null);
            $lastIndex = count($values) - 1;
            if ($lastIndex >= 0) {
                $forecast[$lastIndex] = $actual[$lastIndex];
            }

            for ($k = 1; $k <= $ahead; $k++) {
                $index = $history - 1 + $k;
                $labels[] = now()->addMonths($k)->format('M Y');
                $actual[] = null;
                $forecast[] = round(max(0, $slope * $index + $intercept), 2);
            }

            // Month-over-month growth implied by the fitted trend, relative to the last actual.
            $last = $values[$lastIndex] ?? 0.0;
            $growth = $last > 0 ? round(($slope / $last) * 100, 1) : 0.0;

            return [
                'labels' => $labels,
                'actual' => $actual,
                'forecast' => $forecast,
                'growth' => $growth,
            ];
        });
    }

    protected function monthlyRevenue(Carbon $month): float
    {
        // Eloquent query respects the active BranchScope (single branch or consolidated).
        return round((float) SalesInvoice::query()
            ->whereBetween('invoice_date', DateRange::bounds(
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ))
            ->sum('net_amount'), 2);
    }

    /**
     * Ordinary least-squares fit over y indexed by 0..n-1.
     *
     * @param  list<float>  $y
     * @return array{0:float, 1:float} [slope, intercept]
     */
    protected function linearFit(array $y): array
    {
        $n = count($y);
        if ($n < 2) {
            return [0.0, $n === 1 ? $y[0] : 0.0];
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXy = 0.0;
        $sumXx = 0.0;

        foreach ($y as $i => $value) {
            $sumX += $i;
            $sumY += $value;
            $sumXy += $i * $value;
            $sumXx += $i * $i;
        }

        $denominator = ($n * $sumXx) - ($sumX * $sumX);
        if ($denominator == 0.0) {
            return [0.0, $sumY / $n];
        }

        $slope = (($n * $sumXy) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        return [$slope, $intercept];
    }

    /**
     * Best sellers and slow movers over the trailing window.
     *
     * @return array{best:list<array{name:string, qty:float, revenue:float}>, slow:list<array{name:string, stock:float, sold:float}>}
     */
    public function productPerformance(): array
    {
        return Cache::remember($this->cacheKey('products'), self::TTL, function () {
            $ids = $this->scopeBranchIds();
            $range = DateRange::bounds(now()->subDays(self::VELOCITY_WINDOW)->toDateString(), now()->toDateString());

            $bestRows = DB::table('sales_invoice_items as sii')
                ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
                ->join('products as p', 'p.id', '=', 'sii.product_id')
                ->whereNull('si.deleted_at')
                ->whereIn('si.branch_id', $ids)
                ->whereBetween('si.invoice_date', $range)
                ->groupBy('p.id', 'p.name')
                ->orderByDesc('qty')
                ->limit(10)
                ->get(['p.name as name', DB::raw('SUM(sii.quantity) as qty'), DB::raw('SUM(sii.total) as revenue')]);

            $best = [];
            foreach ($bestRows as $row) {
                $best[] = [
                    'name' => (string) $row->name,
                    'qty' => round((float) $row->qty, 2),
                    'revenue' => round((float) $row->revenue, 2),
                ];
            }

            // On-hand quantity per product in scope.
            $stockRows = DB::table('product_batches')
                ->whereIn('branch_id', $ids)
                ->groupBy('product_id')
                ->havingRaw('SUM(quantity) > 0')
                ->get(['product_id', DB::raw('SUM(quantity) as stock')]);

            // Quantity sold per product over the window.
            $soldRows = DB::table('sales_invoice_items as sii')
                ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
                ->whereNull('si.deleted_at')
                ->whereIn('si.branch_id', $ids)
                ->whereBetween('si.invoice_date', $range)
                ->groupBy('sii.product_id')
                ->get(['sii.product_id', DB::raw('SUM(sii.quantity) as sold')]);

            /** @var array<int, float> $soldByProduct */
            $soldByProduct = [];
            foreach ($soldRows as $row) {
                $soldByProduct[(int) $row->product_id] = (float) $row->sold;
            }

            $names = DB::table('products')->pluck('name', 'id');

            $slow = [];
            foreach ($stockRows as $row) {
                $pid = (int) $row->product_id;
                $slow[] = [
                    'name' => (string) ($names[$pid] ?? '—'),
                    'stock' => round((float) $row->stock, 2),
                    'sold' => round($soldByProduct[$pid] ?? 0.0, 2),
                ];
            }

            usort($slow, fn ($a, $b) => $a['sold'] <=> $b['sold']);

            return ['best' => $best, 'slow' => array_slice($slow, 0, 10)];
        });
    }

    /**
     * Customer lifetime value and churn risk, ranked by value.
     *
     * @return list<array{name:string, clv:float, orders:int, last_order:string, days_since:int, segment:string}>
     */
    public function customerAnalysis(): array
    {
        return Cache::remember($this->cacheKey('customers'), self::TTL, function () {
            $ids = $this->scopeBranchIds();

            $rows = DB::table('sales_invoices as si')
                ->join('customers as c', 'c.id', '=', 'si.customer_id')
                ->whereNull('si.deleted_at')
                ->whereIn('si.branch_id', $ids)
                ->groupBy('c.id', 'c.name')
                ->orderByDesc('clv')
                ->limit(20)
                ->get([
                    'c.name as name',
                    DB::raw('SUM(si.net_amount) as clv'),
                    DB::raw('COUNT(si.id) as orders'),
                    DB::raw('MAX(si.invoice_date) as last_order'),
                ]);

            $result = [];
            foreach ($rows as $row) {
                $lastOrder = $row->last_order !== null ? (string) $row->last_order : null;
                $daysSince = $lastOrder !== null
                    ? (int) Carbon::parse($lastOrder)->startOfDay()->diffInDays(now()->startOfDay())
                    : 9999;

                $result[] = [
                    'name' => (string) $row->name,
                    'clv' => round((float) $row->clv, 2),
                    'orders' => (int) $row->orders,
                    'last_order' => $lastOrder !== null ? Carbon::parse($lastOrder)->toDateString() : '—',
                    'days_since' => $daysSince,
                    'segment' => $this->churnSegment($daysSince, (int) $row->orders),
                ];
            }

            return $result;
        });
    }

    /** Behavioural segment from recency + frequency. */
    protected function churnSegment(int $daysSince, int $orders): string
    {
        return match (true) {
            $daysSince > 180 => 'churned',
            $daysSince > 90 => 'at_risk',
            $orders >= 5 => 'loyal',
            default => 'active',
        };
    }

    /**
     * Per-product stock velocity, projected stockout date and reorder suggestion.
     * Only products at risk (will run out within the reorder cover window) are returned.
     *
     * @return list<array{name:string, stock:float, daily:float, days_left:int, stockout:string, reorder:float}>
     */
    public function inventoryOptimization(): array
    {
        return Cache::remember($this->cacheKey('inventory'), self::TTL, function () {
            $ids = $this->scopeBranchIds();
            $range = DateRange::bounds(now()->subDays(self::VELOCITY_WINDOW)->toDateString(), now()->toDateString());

            $stockRows = DB::table('product_batches')
                ->whereIn('branch_id', $ids)
                ->groupBy('product_id')
                ->havingRaw('SUM(quantity) > 0')
                ->get(['product_id', DB::raw('SUM(quantity) as stock')]);

            $soldRows = DB::table('sales_invoice_items as sii')
                ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
                ->whereNull('si.deleted_at')
                ->whereIn('si.branch_id', $ids)
                ->whereBetween('si.invoice_date', $range)
                ->groupBy('sii.product_id')
                ->get(['sii.product_id', DB::raw('SUM(sii.quantity) as sold')]);

            /** @var array<int, float> $soldByProduct */
            $soldByProduct = [];
            foreach ($soldRows as $row) {
                $soldByProduct[(int) $row->product_id] = (float) $row->sold;
            }

            $names = DB::table('products')->pluck('name', 'id');

            $result = [];
            foreach ($stockRows as $row) {
                $pid = (int) $row->product_id;
                $sold = $soldByProduct[$pid] ?? 0.0;
                if ($sold <= 0) {
                    continue; // No demand signal; not a stockout risk.
                }

                $daily = $sold / self::VELOCITY_WINDOW;
                $stock = (float) $row->stock;
                $daysLeft = $daily > 0 ? (int) floor($stock / $daily) : 9999;

                if ($daysLeft > self::REORDER_COVER_DAYS) {
                    continue; // Comfortable cover; nothing to recommend yet.
                }

                $target = $daily * self::REORDER_COVER_DAYS;
                $reorder = max(0.0, $target - $stock);

                $result[] = [
                    'name' => (string) ($names[$pid] ?? '—'),
                    'stock' => round($stock, 2),
                    'daily' => round($daily, 2),
                    'days_left' => $daysLeft,
                    'stockout' => now()->addDays($daysLeft)->toDateString(),
                    'reorder' => (float) ceil($reorder),
                ];
            }

            usort($result, fn ($a, $b) => $a['days_left'] <=> $b['days_left']);

            return array_slice($result, 0, 20);
        });
    }

    /**
     * Branch ranking by net profit, with revenue / expenses / receivables.
     *
     * @return list<array{rank:int, branch:string, revenue:float, cogs:float, expenses:float, profit:float, outstanding:float}>
     */
    public function branchRanking(): array
    {
        return Cache::remember($this->cacheKey('branches'), self::TTL, function () {
            $ids = $this->scopeBranchIds();

            $sales = DB::table('sales_invoices')
                ->whereNull('deleted_at')
                ->whereIn('branch_id', $ids)
                ->groupBy('branch_id')
                ->get([
                    'branch_id',
                    DB::raw('SUM(net_amount) as net'),
                    DB::raw('SUM(cost_total) as cost'),
                    DB::raw('SUM(balance) as outstanding'),
                ]);

            $expenses = DB::table('expenses')
                ->whereNull('deleted_at')
                ->whereIn('branch_id', $ids)
                ->groupBy('branch_id')
                ->get(['branch_id', DB::raw('SUM(amount) as total')]);

            /** @var array<int, float> $expenseByBranch */
            $expenseByBranch = [];
            foreach ($expenses as $row) {
                $expenseByBranch[(int) $row->branch_id] = (float) $row->total;
            }

            $branchNames = Branch::query()->pluck('name', 'id');

            /** @var list<array{rank:int, branch:string, revenue:float, cogs:float, expenses:float, profit:float, outstanding:float}> $rows */
            $rows = [];
            foreach ($sales as $row) {
                $bid = (int) $row->branch_id;
                $revenue = round((float) $row->net, 2);
                $cogs = round((float) $row->cost, 2);
                $exp = round($expenseByBranch[$bid] ?? 0.0, 2);

                $rows[] = [
                    'rank' => 0,
                    'branch' => (string) ($branchNames[$bid] ?? '—'),
                    'revenue' => $revenue,
                    'cogs' => $cogs,
                    'expenses' => $exp,
                    'profit' => round($revenue - $cogs - $exp, 2),
                    'outstanding' => round((float) $row->outstanding, 2),
                ];
            }

            usort($rows, fn ($a, $b) => $b['profit'] <=> $a['profit']);

            $ranked = [];
            foreach ($rows as $i => $row) {
                $row['rank'] = $i + 1;
                $ranked[] = $row;
            }

            return $ranked;
        });
    }
}
