<?php

namespace App\Services\Purchasing;

use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Services\Branch\BranchContext;
use App\Services\Documents\DocumentNumberService;
use App\Services\Inventory\InventoryService;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Owns the purchase-order lifecycle and its financial/inventory side effects:
 *   draft → ordered → (partial) → received     and supplier payments (payables).
 *
 * Receiving an item creates a costed inventory batch through InventoryService,
 * keeping stock valuation consistent with what was actually purchased.
 */
class PurchaseService
{
    public function __construct(
        protected InventoryService $inventory,
        protected DocumentNumberService $numbers,
        protected BranchContext $context,
        protected NotificationService $notifications,
    ) {}

    /**
     * Create or update a draft/ordered purchase order together with its line items.
     *
     * @param  array{supplier_id:int, order_date:string, expected_delivery_date?:?string, payment_due_date?:?string, notes?:?string}  $attributes
     * @param  array<int, array{product_id:int, variant_id?:?int, quantity?:int, cost_per_unit:float, cost_per_unit_usd?:float}>  $items
     */
    public function save(array $attributes, array $items, ?PurchaseOrder $order = null): PurchaseOrder
    {
        if ($order && ! $order->isEditable()) {
            throw new LogicException('This purchase order can no longer be edited.');
        }

        $items = array_values(array_filter($items, fn ($i) => ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new LogicException('A purchase order must contain at least one item.');
        }

        $isNew = $order === null;

        $order = DB::transaction(function () use ($attributes, $items, $order) {
            $branchId = $this->context->currentBranchId();

            $order ??= new PurchaseOrder([
                'tenant_id' => $this->context->currentTenantId(),
                'branch_id' => $branchId,
                'po_number' => $this->numbers->next('purchase_order', $branchId),
                'order_status' => PurchaseOrder::STATUS_DRAFT,
                'payment_status' => PurchaseOrder::PAY_UNPAID,
                'created_by' => Auth::id(),
            ]);

            $order->fill([
                'supplier_id' => $attributes['supplier_id'],
                'order_date' => $attributes['order_date'],
                'expected_delivery_date' => $attributes['expected_delivery_date'] ?? null,
                'payment_due_date' => $attributes['payment_due_date'] ?? null,
                'notes' => $attributes['notes'] ?? null,
            ])->save();

            $order->items()->delete();

            foreach ($items as $item) {
                $cost = (float) $item['cost_per_unit'];
                $costUsd = (float) ($item['cost_per_unit_usd'] ?? 0);
                $qty = (int) $item['quantity'];

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $qty,
                    'cost_per_unit' => $cost,
                    'cost_per_unit_usd' => $costUsd,
                    'total' => round($cost * $qty, 2),
                    'total_usd' => round($costUsd * $qty, 2),
                ]);
            }

            $this->recalculateTotals($order);

            return $order->refresh();
        });

        if ($isNew) {
            rescue(fn () => $this->notifications->purchaseOrderCreated($order), report: false);
        }

        return $order;
    }

    public function place(PurchaseOrder $order): void
    {
        if ($order->order_status !== PurchaseOrder::STATUS_DRAFT) {
            throw new LogicException('Only draft orders can be placed.');
        }

        $order->update(['order_status' => PurchaseOrder::STATUS_ORDERED]);
    }

    /**
     * Receive quantities into stock. $receipts maps purchase_order_item id => quantity.
     * Omitted or zero quantities default to the item's outstanding balance.
     *
     * @param  array<int, int>  $receipts
     */
    public function receive(PurchaseOrder $order, array $receipts = [], ?int $locationId = null): void
    {
        if (! $order->isReceivable()) {
            throw new LogicException('This purchase order cannot receive stock in its current state.');
        }

        DB::transaction(function () use ($order, $receipts, $locationId) {
            foreach ($order->items as $item) {
                $outstanding = $item->outstandingQuantity();

                if ($outstanding <= 0) {
                    continue;
                }

                $qty = $receipts[$item->id] ?? $outstanding;
                $qty = min((int) $qty, $outstanding);

                if ($qty <= 0) {
                    continue;
                }

                $this->inventory->receive(
                    branchId: $order->branch_id,
                    productId: $item->product_id,
                    quantity: $qty,
                    unitCost: (float) $item->cost_per_unit,
                    variantId: $item->variant_id,
                    locationId: $locationId,
                    source: $order,
                    type: StockMovement::TYPE_RECEIPT,
                );

                $item->increment('received_quantity', $qty);
            }

            $this->syncOrderStatus($order);
        });
    }

    /**
     * Record a payment to the supplier against this order (a payable settlement).
     *
     * @param  array{amount:float, payment_method:string, payment_date:string, reference_number?:?string, transaction_number?:?string, notes?:?string}  $data
     */
    public function recordPayment(PurchaseOrder $order, array $data): Payment
    {
        if ((float) $data['amount'] <= 0) {
            throw new LogicException('Payment amount must be positive.');
        }

        return DB::transaction(function () use ($order, $data) {
            $payment = Payment::create([
                'tenant_id' => $order->tenant_id,
                'branch_id' => $order->branch_id,
                'supplier_id' => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'direction' => Payment::DIRECTION_OUT,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'transaction_number' => $data['transaction_number'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'payment_date' => $data['payment_date'],
                'notes' => $data['notes'] ?? null,
                'recorded_by' => Auth::id(),
            ]);

            $order->increment('paid_amount', (float) $data['amount']);
            $this->syncPaymentStatus($order->refresh());

            return $payment;
        });
    }

    public function cancel(PurchaseOrder $order): void
    {
        if ($order->items->sum('received_quantity') > 0) {
            throw new LogicException('Cannot cancel a purchase order that has received stock.');
        }

        if ($order->order_status === PurchaseOrder::STATUS_RECEIVED) {
            throw new LogicException('Received orders cannot be cancelled.');
        }

        $order->update(['order_status' => PurchaseOrder::STATUS_CANCELLED]);
    }

    protected function recalculateTotals(PurchaseOrder $order): void
    {
        $order->update([
            'total_amount' => round((float) $order->items()->sum('total'), 2),
            'total_amount_usd' => round((float) $order->items()->sum('total_usd'), 2),
        ]);

        $this->syncPaymentStatus($order);
    }

    protected function syncOrderStatus(PurchaseOrder $order): void
    {
        $order->load('items');

        $fullyReceived = $order->items->every(fn ($i) => $i->received_quantity >= $i->quantity);
        $anyReceived = $order->items->contains(fn ($i) => $i->received_quantity > 0);

        $order->update([
            'order_status' => $fullyReceived
                ? PurchaseOrder::STATUS_RECEIVED
                : ($anyReceived ? PurchaseOrder::STATUS_PARTIAL : $order->order_status),
        ]);
    }

    protected function syncPaymentStatus(PurchaseOrder $order): void
    {
        $paid = (float) $order->paid_amount;
        $total = (float) $order->total_amount;

        $status = match (true) {
            $paid <= 0 => PurchaseOrder::PAY_UNPAID,
            $paid + 0.01 >= $total => PurchaseOrder::PAY_PAID,
            default => PurchaseOrder::PAY_PARTIAL,
        };

        if ($order->payment_status !== $status) {
            $order->update(['payment_status' => $status]);
        }
    }
}
