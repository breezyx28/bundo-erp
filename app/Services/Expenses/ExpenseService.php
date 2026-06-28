<?php

namespace App\Services\Expenses;

use App\Models\Expense;
use App\Support\DateRange;

/**
 * Expense reporting. Reads go through the branch-scoped Expense query, so the figures
 * reflect the active branch (or every allowed branch in the consolidated view).
 */
class ExpenseService
{
    /**
     * Expense totals for a date range, broken down by category and by branch.
     *
     * @return array{total:float, count:int, by_category:list<array{category:string, total:float, count:int}>, by_branch:list<array{branch:string, total:float, count:int}>}
     */
    public function report(string $from, string $to): array
    {
        $expenses = Expense::query()
            ->whereBetween('expense_date', DateRange::bounds($from, $to))
            ->with(['category:id,name', 'branch:id,name'])
            ->get(['id', 'branch_id', 'expense_category_id', 'amount']);

        /** @var array<int, array{category:string, total:float, count:int}> $byCategory */
        $byCategory = [];
        /** @var array<int, array{branch:string, total:float, count:int}> $byBranch */
        $byBranch = [];
        $total = 0.0;

        foreach ($expenses as $expense) {
            $amount = (float) $expense->amount;
            $total += $amount;

            $cid = (int) $expense->expense_category_id;
            if (! isset($byCategory[$cid])) {
                $byCategory[$cid] = ['category' => $expense->category->name, 'total' => 0.0, 'count' => 0];
            }
            $byCategory[$cid]['total'] = round($byCategory[$cid]['total'] + $amount, 2);
            $byCategory[$cid]['count']++;

            $bid = (int) $expense->branch_id;
            if (! isset($byBranch[$bid])) {
                $byBranch[$bid] = ['branch' => $expense->branch->name, 'total' => 0.0, 'count' => 0];
            }
            $byBranch[$bid]['total'] = round($byBranch[$bid]['total'] + $amount, 2);
            $byBranch[$bid]['count']++;
        }

        usort($byCategory, fn ($a, $b) => $b['total'] <=> $a['total']);
        usort($byBranch, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'total' => round($total, 2),
            'count' => $expenses->count(),
            'by_category' => $byCategory,
            'by_branch' => $byBranch,
        ];
    }
}
