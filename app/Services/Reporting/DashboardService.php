<?php

namespace App\Services\Reporting;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Services\Branch\BranchContext;
use App\Services\Collections\CollectionsService;
use App\Support\DateRange;
use App\Support\TenantMoney;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates dashboard KPIs. Results are cached (Redis in production) per
 * tenant + active-branch + day, since the figures are read far more often than
 * they change. Call refresh() after a write if an immediate update is needed.
 *
 * Scalar sums go through the branch-scoped Eloquent query; grouped/joined
 * aggregates use the query builder with an explicit branch filter.
 */
class DashboardService
{
    public function __construct(
        protected BranchContext $context,
        protected CollectionsService $collections,
    ) {}

    public const TTL = 300;

    public function cacheKey(): string
    {
        $tenant = $this->context->currentTenantId() ?? 'x';
        $branch = $this->context->currentBranchId() ?? 'all';

        return "dashboard:{$tenant}:{$branch}:".now()->toDateString();
    }

    public function refresh(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array<string, mixed>
     */
    public function kpis(): array
    {
        return Cache::remember($this->cacheKey(), self::TTL, function () {
            $today = [now()->startOfDay(), now()->endOfDay()];
            $month = [now()->startOfMonth(), now()->endOfMonth()];
            $year = [now()->startOfYear(), now()->endOfYear()];

            $aging = $this->collections->summary();

            return [
                'revenue' => [
                    'today' => $this->sales($today)['net'],
                    'month' => $this->sales($month)['net'],
                    'year' => $this->sales($year)['net'],
                ],
                'expenses' => [
                    'today' => $this->expensesTotal($today),
                    'month' => $this->expensesTotal($month),
                    'year' => $this->expensesTotal($year),
                ],
                'profit' => [
                    'today' => $this->profit($today),
                    'month' => $this->profit($month),
                    'year' => $this->profit($year),
                ],
                'outstanding' => $aging['total'],
                'aging' => $aging,
                'low_stock' => $this->lowStockCount(),
                'top_products' => $this->topProducts($year),
                'top_brands' => $this->topBrands($year),
                'recent' => $this->recentTransactions(),
                'shipping' => [
                    'pending' => Shipment::query()->whereNotIn('status', [Shipment::STATUS_DELIVERED, Shipment::STATUS_RETURNED])->count(),
                    'delivered' => Shipment::query()->where('status', Shipment::STATUS_DELIVERED)->count(),
                ],
                'trend' => $this->monthlyTrend(),
                'rate' => TenantMoney::exchangeRate(),
            ];
        });
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
     * @param  array{0:Carbon, 1:Carbon}  $period
     * @return array{net:float, cost:float}
     */
    protected function sales(array $period): array
    {
        $query = SalesInvoice::query()->posted()->whereBetween('invoice_date', DateRange::bounds($period[0]->toDateString(), $period[1]->toDateString()));

        return [
            'net' => round((float) (clone $query)->sum('net_amount'), 2),
            'cost' => round((float) $query->sum('cost_total'), 2),
        ];
    }

    /**
     * @param  array{0:Carbon, 1:Carbon}  $period
     */
    protected function expensesTotal(array $period): float
    {
        $ids = $this->scopeBranchIds();

        return round((float) DB::table('expenses')
            ->whereNull('deleted_at')
            ->whereIn('branch_id', $ids)
            ->whereBetween('expense_date', DateRange::bounds($period[0]->toDateString(), $period[1]->toDateString()))
            ->sum('amount'), 2);
    }

    /**
     * @param  array{0:Carbon, 1:Carbon}  $period
     */
    protected function profit(array $period): float
    {
        $sales = $this->sales($period);

        return round($sales['net'] - $sales['cost'] - $this->expensesTotal($period), 2);
    }

    protected function lowStockCount(): int
    {
        $stock = ProductBatch::query()
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        return Product::query()
            ->active()
            ->where('reorder_level', '>', 0)
            ->get(['id', 'reorder_level'])
            ->filter(fn (Product $p) => (int) ($stock[$p->id] ?? 0) < (int) $p->reorder_level)
            ->count();
    }

    /**
     * @param  array{0:Carbon, 1:Carbon}  $period
     * @return list<array{name:string, value:float}>
     */
    protected function topProducts(array $period): array
    {
        $rows = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->join('products as p', 'p.id', '=', 'sii.product_id')
            ->whereNull('si.deleted_at')
            ->where('si.status', 'posted')
            ->whereIn('si.branch_id', $this->scopeBranchIds())
            ->whereBetween('si.invoice_date', DateRange::bounds($period[0]->toDateString(), $period[1]->toDateString()))
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get(['p.name as name', DB::raw('SUM(sii.quantity) as qty')]);

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['name' => (string) $row->name, 'value' => (float) $row->qty];
        }

        return $result;
    }

    /**
     * @param  array{0:Carbon, 1:Carbon}  $period
     * @return list<array{name:string, value:float}>
     */
    protected function topBrands(array $period): array
    {
        $rows = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->join('products as p', 'p.id', '=', 'sii.product_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->whereNull('si.deleted_at')
            ->where('si.status', 'posted')
            ->whereIn('si.branch_id', $this->scopeBranchIds())
            ->whereBetween('si.invoice_date', DateRange::bounds($period[0]->toDateString(), $period[1]->toDateString()))
            ->groupBy('b.id', 'b.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get(['b.name as name', DB::raw('SUM(sii.total) as total')]);

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['name' => $row->name !== null ? (string) $row->name : '—', 'value' => round((float) $row->total, 2)];
        }

        return $result;
    }

    /**
     * @return list<array{type:string, number:string, party:string, amount:float, date:string}>
     */
    protected function recentTransactions(): array
    {
        $ids = $this->scopeBranchIds();

        $sales = DB::table('sales_invoices as si')
            ->leftJoin('customers as c', 'c.id', '=', 'si.customer_id')
            ->whereNull('si.deleted_at')
            ->where('si.status', 'posted')
            ->whereIn('si.branch_id', $ids)
            ->orderByDesc('si.id')->limit(10)
            ->get(['si.invoice_number as number', 'si.net_amount as amount', 'si.invoice_date as date', 'c.name as party']);

        $purchases = DB::table('purchase_orders as po')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->whereNull('po.deleted_at')
            ->whereIn('po.branch_id', $ids)
            ->orderByDesc('po.id')->limit(10)
            ->get(['po.po_number as number', 'po.total_amount as amount', 'po.order_date as date', 's.name as party']);

        $tx = [];

        foreach ($sales as $row) {
            $tx[] = [
                'type' => 'sale',
                'number' => (string) $row->number,
                'party' => $row->party !== null ? (string) $row->party : __('sales.walk_in'),
                'amount' => (float) $row->amount,
                'date' => (string) $row->date,
            ];
        }

        foreach ($purchases as $row) {
            $tx[] = [
                'type' => 'purchase',
                'number' => (string) $row->number,
                'party' => $row->party !== null ? (string) $row->party : '—',
                'amount' => (float) $row->amount,
                'date' => (string) $row->date,
            ];
        }

        usort($tx, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return array_slice($tx, 0, 10);
    }

    /**
     * Revenue vs expenses for the trailing 12 months.
     *
     * @return array{labels:list<string>, revenue:list<float>, expenses:list<float>}
     */
    protected function monthlyTrend(): array
    {
        $labels = [];
        $revenue = [];
        $expenses = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $period = [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];

            $labels[] = $month->format('M Y');
            $revenue[] = $this->sales($period)['net'];
            $expenses[] = $this->expensesTotal($period);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'expenses' => $expenses];
    }
}
