<?php

namespace App\Services\Collections;

use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Services\Sales\SalesService;
use App\Support\DateRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Receivables, aging, collections and collection-performance reporting.
 *
 * All reads go through the branch-scoped SalesInvoice query, so a manager sees only
 * their branch while a head-office user in the consolidated view sees every allowed
 * branch automatically.
 */
class CollectionsService
{
    /** Aging bucket keys in display order. */
    public const BUCKETS = ['current', 'd30', 'd60', 'd90'];

    public function __construct(protected SalesService $sales) {}

    /**
     * Outstanding receivables grouped by customer, split into aging buckets.
     *
     * @return list<array{customer_id:?int, customer:string, current:float, d30:float, d60:float, d90:float, total:float, oldest_days:int}>
     */
    public function aging(): array
    {
        $invoices = SalesInvoice::query()
            ->outstanding()
            ->with('customer:id,name')
            ->get(['id', 'customer_id', 'invoice_date', 'due_date', 'balance']);

        /** @var array<int, array{customer_id:?int, customer:string, current:float, d30:float, d60:float, d90:float, total:float, oldest_days:int}> $rows */
        $rows = [];

        foreach ($invoices as $invoice) {
            $key = $invoice->customer_id ?? 0;

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'customer_id' => $invoice->customer_id,
                    'customer' => $invoice->customer?->name ?? __('sales.walk_in'),
                    'current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0,
                    'total' => 0.0, 'oldest_days' => 0,
                ];
            }

            $balance = (float) $invoice->balance;

            match ($invoice->agingBucket()) {
                'current' => $rows[$key]['current'] += $balance,
                'd30' => $rows[$key]['d30'] += $balance,
                'd60' => $rows[$key]['d60'] += $balance,
                default => $rows[$key]['d90'] += $balance,
            };

            $rows[$key]['total'] += $balance;
            $rows[$key]['oldest_days'] = max($rows[$key]['oldest_days'], $invoice->daysOverdue());
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

        SalesInvoice::query()
            ->outstanding()
            ->get(['id', 'invoice_date', 'due_date', 'balance'])
            ->each(function (SalesInvoice $invoice) use (&$totals) {
                $balance = (float) $invoice->balance;
                $totals[$invoice->agingBucket()] += $balance;
                $totals['total'] += $balance;
            });

        return array_map(fn ($v) => round($v, 2), $totals);
    }

    /**
     * Unpaid invoices for a customer, oldest due first.
     *
     * @return Collection<int, SalesInvoice>
     */
    public function customerInvoices(int $customerId): Collection
    {
        return SalesInvoice::query()
            ->outstanding()
            ->where('customer_id', $customerId)
            ->orderByRaw('COALESCE(due_date, invoice_date) asc')
            ->get();
    }

    /**
     * Apply a lump-sum collection across a customer's oldest invoices (FIFO by due date).
     *
     * @param  array{amount:float, payment_method:string, payment_date:string, reference_number?:?string}  $data
     * @return array<int, array{invoice:string, amount:float}>
     */
    public function collectFromCustomer(int $customerId, array $data): array
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new LogicException('Collection amount must be positive.');
        }

        return DB::transaction(function () use ($customerId, $data, $amount) {
            $remaining = $amount;
            $allocations = [];

            foreach ($this->customerInvoices($customerId) as $invoice) {
                if ($remaining <= 0.001) {
                    break;
                }

                $apply = min($remaining, (float) $invoice->balance);

                $this->sales->recordPayment($invoice, [
                    'amount' => $apply,
                    'payment_method' => $data['payment_method'],
                    'payment_date' => $data['payment_date'],
                    'reference_number' => $data['reference_number'] ?? null,
                ]);

                $allocations[] = ['invoice' => $invoice->invoice_number, 'amount' => round($apply, 2)];
                $remaining -= $apply;
            }

            if ($allocations === []) {
                throw new LogicException('This customer has no outstanding invoices.');
            }

            return $allocations;
        });
    }

    /**
     * Collection performance over a date range, grouped by branch and by method.
     *
     * @return array{total:float, count:int, by_branch:Collection<int, array{branch:string, total:float, count:int}>, by_method:array<string, float>}
     */
    public function performance(string $from, string $to): array
    {
        $payments = Payment::query()
            ->where('direction', Payment::DIRECTION_IN)
            ->whereBetween('payment_date', DateRange::bounds($from, $to))
            ->with('branch:id,name')
            ->get(['id', 'branch_id', 'amount', 'payment_method']);

        /** @var array<int, array{branch:string, total:float, count:int}> $branchRows */
        $branchRows = [];
        /** @var array<string, float> $byMethod */
        $byMethod = [];
        $total = 0.0;

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;
            $total += $amount;

            $bid = (int) $payment->branch_id;
            if (! isset($branchRows[$bid])) {
                $branchRows[$bid] = ['branch' => $payment->branch->name, 'total' => 0.0, 'count' => 0];
            }
            $branchRows[$bid]['total'] = round($branchRows[$bid]['total'] + $amount, 2);
            $branchRows[$bid]['count']++;

            $method = (string) $payment->payment_method;
            $byMethod[$method] = round(($byMethod[$method] ?? 0.0) + $amount, 2);
        }

        return [
            'total' => round($total, 2),
            'count' => $payments->count(),
            'by_branch' => collect($branchRows)->sortByDesc('total')->values(),
            'by_method' => $byMethod,
        ];
    }

    public function markReminded(SalesInvoice $invoice): void
    {
        $invoice->update(['last_reminder_at' => now()]);
    }
}
