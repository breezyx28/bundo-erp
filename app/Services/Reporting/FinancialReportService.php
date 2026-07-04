<?php

namespace App\Services\Reporting;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Services\Branch\BranchContext;
use App\Support\DateRange;
use Illuminate\Support\Facades\DB;

/**
 * Financial statements (P&L, cash flow) and branch comparison. All reads respect
 * the active branch scope, so the same methods serve both per-branch and
 * consolidated (head-office) views.
 */
class FinancialReportService
{
    public function __construct(protected BranchContext $context) {}

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
     * @return array{revenue:float, cogs:float, gross_profit:float, expenses:float, net_profit:float, expense_breakdown:list<array{category:string, total:float}>}
     */
    public function profitAndLoss(string $from, string $to): array
    {
        $range = DateRange::bounds($from, $to);

        $salesQuery = SalesInvoice::query()->whereBetween('invoice_date', $range);
        $revenue = round((float) (clone $salesQuery)->sum('net_amount'), 2);
        $cogs = round((float) $salesQuery->sum('cost_total'), 2);
        $grossProfit = round($revenue - $cogs, 2);

        $breakdown = Expense::query()
            ->whereBetween('expense_date', $range)
            ->with('category:id,name')
            ->get(['expense_category_id', 'amount']);

        $byCategory = [];
        $expensesTotal = 0.0;

        foreach ($breakdown as $expense) {
            $name = $expense->category?->name ?? __('reports.uncategorized');
            $byCategory[$name] = ($byCategory[$name] ?? 0) + (float) $expense->amount;
            $expensesTotal += (float) $expense->amount;
        }

        arsort($byCategory);
        $expenseBreakdown = [];
        foreach ($byCategory as $name => $total) {
            $expenseBreakdown[] = ['category' => $name, 'total' => round($total, 2)];
        }

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => round($expensesTotal, 2),
            'net_profit' => round($grossProfit - $expensesTotal, 2),
            'expense_breakdown' => $expenseBreakdown,
        ];
    }

    /**
     * @return array{cash_in:float, cash_out_payments:float, cash_out_expenses:float, net:float}
     */
    public function cashFlow(string $from, string $to): array
    {
        $range = DateRange::bounds($from, $to);

        $cashIn = (float) Payment::query()
            ->where('direction', Payment::DIRECTION_IN)
            ->whereBetween('payment_date', $range)
            ->sum('amount');

        $cashOutPayments = (float) Payment::query()
            ->where('direction', Payment::DIRECTION_OUT)
            ->whereBetween('payment_date', $range)
            ->sum('amount');

        $cashOutExpenses = (float) Expense::query()
            ->whereBetween('expense_date', $range)
            ->sum('amount');

        return [
            'cash_in' => round($cashIn, 2),
            'cash_out_payments' => round($cashOutPayments, 2),
            'cash_out_expenses' => round($cashOutExpenses, 2),
            'net' => round($cashIn - $cashOutPayments - $cashOutExpenses, 2),
        ];
    }

    /**
     * Per-branch revenue / expenses / profit for the consolidated comparison view.
     *
     * @return list<array{branch:string, revenue:float, cogs:float, expenses:float, profit:float}>
     */
    public function branchComparison(string $from, string $to): array
    {
        $ids = $this->scopeBranchIds();
        $range = DateRange::bounds($from, $to);

        $sales = DB::table('sales_invoices')
            ->whereNull('deleted_at')
            ->whereIn('branch_id', $ids)
            ->whereBetween('invoice_date', $range)
            ->groupBy('branch_id')
            ->get(['branch_id', DB::raw('SUM(net_amount) as net'), DB::raw('SUM(cost_total) as cost')]);

        $expenses = DB::table('expenses')
            ->whereNull('deleted_at')
            ->whereIn('branch_id', $ids)
            ->whereBetween('expense_date', $range)
            ->groupBy('branch_id')
            ->get(['branch_id', DB::raw('SUM(amount) as total')]);

        /** @var array<int, array{branch:string, revenue:float, cogs:float, expenses:float, profit:float}> $rows */
        $rows = [];
        $branchNames = Branch::query()->pluck('name', 'id');

        foreach ($sales as $row) {
            $bid = (int) $row->branch_id;
            $rows[$bid] = [
                'branch' => (string) ($branchNames[$bid] ?? '—'),
                'revenue' => round((float) $row->net, 2),
                'cogs' => round((float) $row->cost, 2),
                'expenses' => 0.0,
                'profit' => 0.0,
            ];
        }

        foreach ($expenses as $row) {
            $bid = (int) $row->branch_id;
            if (! isset($rows[$bid])) {
                $rows[$bid] = ['branch' => (string) ($branchNames[$bid] ?? '—'), 'revenue' => 0.0, 'cogs' => 0.0, 'expenses' => 0.0, 'profit' => 0.0];
            }
            $rows[$bid]['expenses'] = round((float) $row->total, 2);
        }

        foreach ($rows as &$row) {
            $row['profit'] = round($row['revenue'] - $row['cogs'] - $row['expenses'], 2);
        }
        unset($row);

        usort($rows, fn ($a, $b) => $b['profit'] <=> $a['profit']);

        return $rows;
    }
}
