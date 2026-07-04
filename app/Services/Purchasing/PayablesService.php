<?php

namespace App\Services\Purchasing;

use App\Models\PurchaseOrder;

/**
 * Supplier payables and aging — the mirror of {@see \App\Services\Collections\CollectionsService}
 * for outstanding purchase orders. Reads go through the branch-scoped PurchaseOrder query.
 */
class PayablesService
{
    /** Aging bucket keys in display order. */
    public const BUCKETS = ['current', 'd30', 'd60', 'd90'];

    /**
     * Outstanding payables grouped by supplier, split into aging buckets.
     *
     * @return list<array{supplier_id:?int, supplier:string, current:float, d30:float, d60:float, d90:float, total:float, oldest_days:int}>
     */
    public function aging(): array
    {
        $orders = PurchaseOrder::query()
            ->outstandingPayables()
            ->with('supplier:id,name')
            ->get(['id', 'supplier_id', 'order_date', 'payment_due_date', 'total_amount', 'paid_amount']);

        /** @var array<int, array{supplier_id:?int, supplier:string, current:float, d30:float, d60:float, d90:float, total:float, oldest_days:int}> $rows */
        $rows = [];

        foreach ($orders as $order) {
            $key = $order->supplier_id ?? 0;

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'supplier_id' => $order->supplier_id,
                    'supplier' => $order->supplier?->name ?? '—',
                    'current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0,
                    'total' => 0.0, 'oldest_days' => 0,
                ];
            }

            $outstanding = $order->outstanding();

            match ($order->paymentAgingBucket()) {
                'current' => $rows[$key]['current'] += $outstanding,
                'd30' => $rows[$key]['d30'] += $outstanding,
                'd60' => $rows[$key]['d60'] += $outstanding,
                default => $rows[$key]['d90'] += $outstanding,
            };

            $rows[$key]['total'] += $outstanding;
            $rows[$key]['oldest_days'] = max($rows[$key]['oldest_days'], $order->paymentDaysOverdue());
        }

        usort($rows, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $rows;
    }

    /**
     * Bucket totals + grand outstanding across the active scope.
     *
     * @return array{current:float, d30:float, d60:float, d90:float, total:float}
     */
    public function summary(): array
    {
        $totals = ['current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0, 'total' => 0.0];

        PurchaseOrder::query()
            ->outstandingPayables()
            ->get(['id', 'order_date', 'payment_due_date', 'total_amount', 'paid_amount'])
            ->each(function (PurchaseOrder $order) use (&$totals) {
                $outstanding = $order->outstanding();
                $totals[$order->paymentAgingBucket()] += $outstanding;
                $totals['total'] += $outstanding;
            });

        return array_map(fn ($v) => round($v, 2), $totals);
    }
}
