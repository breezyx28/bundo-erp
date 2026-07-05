<?php

namespace App\Services\Sales;

use App\Models\CustomerBranchBalance;
use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Services\Branch\BranchContext;
use App\Services\Documents\DocumentNumberService;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;
use App\Support\TenantMoney;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * The sales invoice engine.
 *
 * Each logical line deducts stock through InventoryService (FIFO by default, or a
 * caller-supplied batch order), and every batch allocation becomes its own invoice
 * item carrying the exact COGS of that batch. Totals, discounts, dual-currency
 * figures, the cash/credit payment lifecycle, and customer receivables are all kept
 * consistent inside a single transaction.
 */
class SalesService
{
    public function __construct(
        protected InventoryService $inventory,
        protected DocumentNumberService $numbers,
        protected BranchContext $context,
        protected NotificationService $notifications,
    ) {}

    /**
     * @param  array{customer_id?:?int, invoice_date:string, due_date?:?string, sale_type:string, discount_type?:?string, discount_value?:float, exchange_rate?:?float, payment_method?:?string, paid_amount?:float, notes?:?string}  $attributes
     * @param  array<int, array{product_id:int, variant_id?:?int, quantity:int, unit_price:float, discount_type?:?string, discount_value?:float, batch_selections?:?array<int, array{batch_id:int, quantity:int}>}>  $lines
     */
    public function createInvoice(array $attributes, array $lines): SalesInvoice
    {
        $lines = array_values(array_filter($lines, fn ($l) => $l['quantity'] > 0));

        if ($lines === []) {
            throw new LogicException('An invoice must contain at least one item.');
        }

        $saleType = $attributes['sale_type'];

        if ($saleType === SalesInvoice::TYPE_CREDIT && empty($attributes['customer_id'])) {
            throw new LogicException('A credit sale requires a customer.');
        }

        return DB::transaction(function () use ($attributes, $lines, $saleType) {
            $branchId = $this->context->currentBranchId();

            if ($branchId === null) {
                throw new LogicException('Select a branch before creating an invoice.');
            }

            $rate = (float) ($attributes['exchange_rate'] ?? 0) ?: TenantMoney::exchangeRate();

            $invoice = SalesInvoice::create([
                'tenant_id' => $this->context->currentTenantId(),
                'branch_id' => $branchId,
                'customer_id' => $attributes['customer_id'] ?? null,
                'invoice_number' => $this->numbers->next('invoice', $branchId),
                'invoice_date' => $attributes['invoice_date'],
                'due_date' => $saleType === SalesInvoice::TYPE_CREDIT ? ($attributes['due_date'] ?? null) : null,
                'sale_type' => $saleType,
                'status' => SalesInvoice::STATUS_POSTED,
                'posted_at' => now(),
                'exchange_rate' => $rate,
                'notes' => $attributes['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $subtotal = 0.0;
            $costTotal = 0.0;

            foreach ($lines as $line) {
                $qty = (int) $line['quantity'];
                $unitPrice = (float) $line['unit_price'];
                $lineGross = $unitPrice * $qty;
                $lineDiscount = $this->discountAmount($lineGross, $line['discount_type'] ?? null, (float) ($line['discount_value'] ?? 0));
                $lineNet = $lineGross - $lineDiscount;

                $result = $this->inventory->deduct(
                    branchId: $branchId,
                    productId: $line['product_id'],
                    quantity: $qty,
                    variantId: $line['variant_id'] ?? null,
                    batchSelections: $line['batch_selections'] ?? null,
                    type: StockMovement::TYPE_SALE,
                    reference: $invoice,
                );

                $costTotal += $result['cost'];
                $subtotal += $lineNet;

                // One invoice item per batch allocation, so COGS is recorded exactly.
                foreach ($result['allocations'] as $alloc) {
                    $allocTotal = round($lineNet * ($alloc['quantity'] / $qty), 2);

                    $invoice->items()->create([
                        'product_id' => $line['product_id'],
                        'variant_id' => $line['variant_id'] ?? null,
                        'batch_id' => $alloc['batch_id'],
                        'quantity' => $alloc['quantity'],
                        'unit_price' => $unitPrice,
                        'unit_price_usd' => $this->toUsd($unitPrice, $rate),
                        'cost_per_unit' => $alloc['unit_cost'],
                        'discount_type' => $line['discount_type'] ?? null,
                        'discount_value' => (float) ($line['discount_value'] ?? 0),
                        'total' => $allocTotal,
                        'total_usd' => $this->toUsd($allocTotal, $rate),
                    ]);
                }
            }

            $discountAmount = $this->discountAmount($subtotal, $attributes['discount_type'] ?? null, (float) ($attributes['discount_value'] ?? 0));
            $netAmount = round($subtotal - $discountAmount, 2);

            $paid = $saleType === SalesInvoice::TYPE_CASH
                ? $netAmount
                : min((float) ($attributes['paid_amount'] ?? 0), $netAmount);

            $invoice->fill([
                'total_amount' => round($subtotal, 2),
                'total_amount_usd' => $this->toUsd($subtotal, $rate),
                'discount_type' => $attributes['discount_type'] ?? null,
                'discount_value' => (float) ($attributes['discount_value'] ?? 0),
                'discount_amount' => round($discountAmount, 2),
                'net_amount' => $netAmount,
                'net_amount_usd' => $this->toUsd($netAmount, $rate),
                'cost_total' => round($costTotal, 2),
                'paid_amount' => round($paid, 2),
                'balance' => round($netAmount - $paid, 2),
                'payment_status' => $this->paymentStatus($paid, $netAmount),
                'payment_method' => $paid > 0 ? ($attributes['payment_method'] ?? Payment::METHOD_CASH) : null,
            ])->save();

            if ($paid > 0) {
                $this->writePayment($invoice, $paid, $attributes['payment_method'] ?? Payment::METHOD_CASH, $attributes['invoice_date']);
            }

            // Credit (or partially-paid) sales raise the customer's receivable.
            if ($invoice->balance > 0 && $invoice->customer_id) {
                $this->adjustCustomerBalance($invoice->customer_id, $branchId, (float) $invoice->balance);
            }

            return $invoice->refresh();
        });
    }

    /**
     * Record a customer payment against an invoice (used by sales and by collections).
     *
     * @param  array{amount:float, payment_method:string, payment_date:string, reference_number?:?string, transaction_number?:?string, notes?:?string}  $data
     */
    public function recordPayment(SalesInvoice $invoice, array $data): Payment
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new LogicException('Payment amount must be positive.');
        }

        if ($amount - 0.01 > (float) $invoice->balance) {
            throw new LogicException('Payment exceeds the outstanding balance.');
        }

        $payment = DB::transaction(function () use ($invoice, $data, $amount) {
            $payment = $this->writePayment(
                $invoice,
                $amount,
                $data['payment_method'],
                $data['payment_date'],
                $data['reference_number'] ?? null,
                $data['transaction_number'] ?? null,
                $data['notes'] ?? null,
            );

            $invoice->increment('paid_amount', $amount);
            $invoice->refresh();
            $invoice->update([
                'balance' => round((float) $invoice->net_amount - (float) $invoice->paid_amount, 2),
                'payment_status' => $this->paymentStatus((float) $invoice->paid_amount, (float) $invoice->net_amount),
            ]);

            if ($invoice->customer_id) {
                $this->adjustCustomerBalance($invoice->customer_id, $invoice->branch_id, -$amount);
            }

            return $payment;
        });

        rescue(fn () => $this->notifications->paymentReceived($payment), report: false);

        return $payment;
    }

    /**
     * Create or update a held sale without touching inventory.
     *
     * @param  array{id?:?int, customer_id?:?int, invoice_date?:string, due_date?:?string, sale_type?:string, discount_type?:?string, discount_value?:float, exchange_rate?:?float, notes?:?string, hold_label?:?string}  $attributes
     * @param  array<int, array{product_id:int, variant_id?:?int, quantity:int, unit_price:float, discount_type?:?string, discount_value?:float}>  $lines
     */
    public function saveDraft(array $attributes, array $lines = []): SalesInvoice
    {
        return DB::transaction(function () use ($attributes, $lines) {
            $branchId = $this->context->currentBranchId();

            if ($branchId === null) {
                throw new LogicException('Select a branch before holding a sale.');
            }

            $rate = (float) ($attributes['exchange_rate'] ?? 0) ?: TenantMoney::exchangeRate();
            $saleType = $attributes['sale_type'] ?? SalesInvoice::TYPE_CASH;

            if ($saleType === SalesInvoice::TYPE_CREDIT && empty($attributes['customer_id'])) {
                throw new LogicException('A credit sale requires a customer.');
            }

            $draftId = $attributes['id'] ?? null;
            $invoice = $draftId
                ? SalesInvoice::query()->draft()->findOrFail($draftId)
                : new SalesInvoice;

            if ($draftId && ! $invoice->isDraft()) {
                throw new LogicException('Only draft invoices can be updated.');
            }

            $invoice->fill([
                'tenant_id' => $this->context->currentTenantId(),
                'branch_id' => $branchId,
                'customer_id' => $attributes['customer_id'] ?? null,
                'invoice_date' => $attributes['invoice_date'] ?? now()->toDateString(),
                'due_date' => $saleType === SalesInvoice::TYPE_CREDIT ? ($attributes['due_date'] ?? null) : null,
                'sale_type' => $saleType,
                'status' => SalesInvoice::STATUS_DRAFT,
                'hold_label' => $attributes['hold_label'] ?? null,
                'exchange_rate' => $rate,
                'notes' => $attributes['notes'] ?? null,
                'created_by' => $invoice->exists ? $invoice->created_by : Auth::id(),
            ])->save();

            $lines = array_values(array_filter($lines, fn ($l) => ($l['quantity'] ?? 0) > 0));
            $invoice->items()->delete();

            $subtotal = 0.0;

            foreach ($lines as $line) {
                $qty = (int) $line['quantity'];
                $unitPrice = (float) $line['unit_price'];
                $lineGross = $unitPrice * $qty;
                $lineDiscount = $this->discountAmount($lineGross, $line['discount_type'] ?? null, (float) ($line['discount_value'] ?? 0));
                $lineNet = $lineGross - $lineDiscount;
                $subtotal += $lineNet;

                $invoice->items()->create([
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'] ?? null,
                    'batch_id' => null,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_price_usd' => $this->toUsd($unitPrice, $rate),
                    'cost_per_unit' => 0,
                    'discount_type' => $line['discount_type'] ?? null,
                    'discount_value' => (float) ($line['discount_value'] ?? 0),
                    'total' => round($lineNet, 2),
                    'total_usd' => $this->toUsd($lineNet, $rate),
                ]);
            }

            $discountAmount = $this->discountAmount($subtotal, $attributes['discount_type'] ?? null, (float) ($attributes['discount_value'] ?? 0));
            $netAmount = round($subtotal - $discountAmount, 2);

            $invoice->fill([
                'total_amount' => round($subtotal, 2),
                'total_amount_usd' => $this->toUsd($subtotal, $rate),
                'discount_type' => $attributes['discount_type'] ?? null,
                'discount_value' => (float) ($attributes['discount_value'] ?? 0),
                'discount_amount' => round($discountAmount, 2),
                'net_amount' => $netAmount,
                'net_amount_usd' => $this->toUsd($netAmount, $rate),
                'cost_total' => 0,
                'paid_amount' => 0,
                'balance' => $netAmount,
                'payment_status' => SalesInvoice::PAY_UNPAID,
                'payment_method' => null,
            ])->save();

            return $invoice->refresh();
        });
    }

    /**
     * Complete a held sale: deduct stock, assign invoice number, record payment.
     *
     * @param  array{payment_method?:?string, paid_amount?:float}  $paymentAttributes
     */
    public function postDraft(SalesInvoice $draft, array $paymentAttributes = []): SalesInvoice
    {
        if (! $draft->isDraft()) {
            throw new LogicException('Only draft invoices can be posted.');
        }

        $draft->load('items');

        if ($draft->items->isEmpty()) {
            throw new LogicException('An invoice must contain at least one item.');
        }

        $saleType = $draft->sale_type;

        if ($saleType === SalesInvoice::TYPE_CREDIT && ! $draft->customer_id) {
            throw new LogicException('A credit sale requires a customer.');
        }

        return DB::transaction(function () use ($draft, $paymentAttributes, $saleType) {
            $branchId = $draft->branch_id;
            $rate = (float) $draft->exchange_rate ?: TenantMoney::exchangeRate();

            $lines = $draft->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_type' => $item->discount_type,
                'discount_value' => $item->discount_value,
            ])->all();

            $draft->items()->delete();

            $subtotal = 0.0;
            $costTotal = 0.0;

            foreach ($lines as $line) {
                $qty = (int) $line['quantity'];
                $unitPrice = (float) $line['unit_price'];
                $lineGross = $unitPrice * $qty;
                $lineDiscount = $this->discountAmount($lineGross, $line['discount_type'] ?? null, (float) ($line['discount_value'] ?? 0));
                $lineNet = $lineGross - $lineDiscount;

                $result = $this->inventory->deduct(
                    branchId: $branchId,
                    productId: $line['product_id'],
                    quantity: $qty,
                    variantId: $line['variant_id'] ?? null,
                    batchSelections: null,
                    type: StockMovement::TYPE_SALE,
                    reference: $draft,
                );

                $costTotal += $result['cost'];
                $subtotal += $lineNet;

                foreach ($result['allocations'] as $alloc) {
                    $allocTotal = round($lineNet * ($alloc['quantity'] / $qty), 2);

                    $draft->items()->create([
                        'product_id' => $line['product_id'],
                        'variant_id' => $line['variant_id'] ?? null,
                        'batch_id' => $alloc['batch_id'],
                        'quantity' => $alloc['quantity'],
                        'unit_price' => $unitPrice,
                        'unit_price_usd' => $this->toUsd($unitPrice, $rate),
                        'cost_per_unit' => $alloc['unit_cost'],
                        'discount_type' => $line['discount_type'] ?? null,
                        'discount_value' => (float) ($line['discount_value'] ?? 0),
                        'total' => $allocTotal,
                        'total_usd' => $this->toUsd($allocTotal, $rate),
                    ]);
                }
            }

            $discountAmount = $this->discountAmount($subtotal, $draft->discount_type, (float) $draft->discount_value);
            $netAmount = round($subtotal - $discountAmount, 2);

            $paid = $saleType === SalesInvoice::TYPE_CASH
                ? $netAmount
                : min((float) ($paymentAttributes['paid_amount'] ?? 0), $netAmount);

            $draft->fill([
                'invoice_number' => $this->numbers->next('invoice', $branchId),
                'status' => SalesInvoice::STATUS_POSTED,
                'posted_at' => now(),
                'total_amount' => round($subtotal, 2),
                'total_amount_usd' => $this->toUsd($subtotal, $rate),
                'discount_amount' => round($discountAmount, 2),
                'net_amount' => $netAmount,
                'net_amount_usd' => $this->toUsd($netAmount, $rate),
                'cost_total' => round($costTotal, 2),
                'paid_amount' => round($paid, 2),
                'balance' => round($netAmount - $paid, 2),
                'payment_status' => $this->paymentStatus($paid, $netAmount),
                'payment_method' => $paid > 0 ? ($paymentAttributes['payment_method'] ?? Payment::METHOD_CASH) : null,
            ])->save();

            if ($paid > 0) {
                $this->writePayment(
                    $draft,
                    $paid,
                    $paymentAttributes['payment_method'] ?? Payment::METHOD_CASH,
                    $draft->invoice_date->toDateString(),
                );
            }

            if ($draft->balance > 0 && $draft->customer_id) {
                $this->adjustCustomerBalance($draft->customer_id, $branchId, (float) $draft->balance);
            }

            return $draft->refresh();
        });
    }

    public function discardDraft(SalesInvoice $draft): void
    {
        if (! $draft->isDraft()) {
            throw new LogicException('Only draft invoices can be discarded.');
        }

        DB::transaction(function () use ($draft) {
            $draft->items()->delete();
            $draft->forceDelete();
        });
    }

    /**
     * Void an invoice: restore stock, reverse receivable and payments, then soft-delete.
     */
    public function void(SalesInvoice $invoice): void
    {
        if ($invoice->trashed()) {
            throw new LogicException('Invoice is already voided.');
        }

        if ($invoice->isDraft()) {
            throw new LogicException('Draft invoices must be discarded, not voided.');
        }

        DB::transaction(function () use ($invoice) {
            foreach ($invoice->items as $item) {
                $this->inventory->receive(
                    branchId: $invoice->branch_id,
                    productId: $item->product_id,
                    quantity: $item->quantity,
                    unitCost: (float) $item->cost_per_unit,
                    variantId: $item->variant_id,
                    source: $invoice,
                    type: StockMovement::TYPE_RETURN,
                );
            }

            if ($invoice->customer_id && $invoice->balance > 0) {
                $this->adjustCustomerBalance($invoice->customer_id, $invoice->branch_id, -(float) $invoice->balance);
            }

            $invoice->payments()->delete();
            $invoice->update(['payment_status' => SalesInvoice::PAY_UNPAID]);
            $invoice->delete();
        });
    }

    protected function writePayment(
        SalesInvoice $invoice,
        float $amount,
        string $method,
        string $date,
        ?string $reference = null,
        ?string $transaction = null,
        ?string $notes = null,
    ): Payment {
        return Payment::create([
            'tenant_id' => $invoice->tenant_id,
            'branch_id' => $invoice->branch_id,
            'customer_id' => $invoice->customer_id,
            'sales_invoice_id' => $invoice->id,
            'direction' => Payment::DIRECTION_IN,
            'amount' => $amount,
            'payment_method' => $method,
            'reference_number' => $reference,
            'transaction_number' => $transaction,
            'payment_date' => $date,
            'notes' => $notes,
            'recorded_by' => Auth::id(),
        ]);
    }

    protected function adjustCustomerBalance(int $customerId, int $branchId, float $delta): void
    {
        $balance = CustomerBranchBalance::firstOrCreate(
            ['customer_id' => $customerId, 'branch_id' => $branchId],
            ['balance' => 0],
        );

        $balance->increment('balance', $delta);
    }

    protected function discountAmount(float $base, ?string $type, float $value): float
    {
        if ($value <= 0 || $type === null) {
            return 0.0;
        }

        return $type === 'percentage'
            ? round($base * min($value, 100) / 100, 2)
            : min(round($value, 2), $base);
    }

    protected function toUsd(float $sdg, float $rate): float
    {
        return $rate > 0 ? round($sdg / $rate, 2) : 0.0;
    }

    protected function paymentStatus(float $paid, float $net): string
    {
        return match (true) {
            $paid <= 0 => SalesInvoice::PAY_UNPAID,
            $paid + 0.01 >= $net => SalesInvoice::PAY_PAID,
            default => SalesInvoice::PAY_PARTIAL,
        };
    }
}
