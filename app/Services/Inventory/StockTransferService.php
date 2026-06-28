<?php

namespace App\Services\Inventory;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Services\Branch\BranchContext;
use App\Services\Documents\DocumentNumberService;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Orchestrates the inter-branch stock transfer lifecycle:
 *   requested → approved → dispatched → received  (or → cancelled while pending)
 *
 * Stock leaves the source branch at dispatch (FIFO, capturing weighted cost) and
 * arrives at the destination at receipt (as a new batch preserving that cost).
 */
class StockTransferService
{
    public function __construct(
        protected InventoryService $inventory,
        protected DocumentNumberService $numbers,
        protected BranchContext $context,
        protected NotificationService $notifications,
    ) {}

    /**
     * @param  array<int, array{product_id:int, variant_id?:int|null, quantity?:int}>  $items
     */
    public function request(int $fromBranchId, int $toBranchId, array $items, ?string $notes = null): StockTransfer
    {
        if ($fromBranchId === $toBranchId) {
            throw new LogicException('Source and destination branches must differ.');
        }

        $items = array_values(array_filter($items, fn ($i) => ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new LogicException('A transfer must contain at least one item.');
        }

        $transfer = DB::transaction(function () use ($fromBranchId, $toBranchId, $items, $notes) {
            $transfer = StockTransfer::create([
                'tenant_id' => $this->context->currentTenantId(),
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'number' => $this->numbers->next('transfer', $fromBranchId),
                'status' => StockTransfer::STATUS_REQUESTED,
                'notes' => $notes,
                'requested_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $transfer;
        });

        rescue(fn () => $this->notifications->transferRequested($transfer), report: false);

        return $transfer;
    }

    public function approve(StockTransfer $transfer): void
    {
        $this->assertStatus($transfer, StockTransfer::STATUS_REQUESTED);

        $transfer->update([
            'status' => StockTransfer::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }

    public function dispatch(StockTransfer $transfer): void
    {
        $this->assertStatus($transfer, StockTransfer::STATUS_APPROVED);

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $result = $this->inventory->deduct(
                    branchId: $transfer->from_branch_id,
                    productId: $item->product_id,
                    quantity: $item->quantity,
                    variantId: $item->variant_id,
                    type: StockMovement::TYPE_TRANSFER_OUT,
                    reference: $transfer,
                );

                $item->update(['unit_cost' => round($result['cost'] / max($item->quantity, 1), 2)]);
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_DISPATCHED,
                'dispatched_by' => Auth::id(),
                'dispatched_at' => now(),
            ]);
        });
    }

    public function receive(StockTransfer $transfer): void
    {
        $this->assertStatus($transfer, StockTransfer::STATUS_DISPATCHED);

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $this->inventory->receive(
                    branchId: $transfer->to_branch_id,
                    productId: $item->product_id,
                    quantity: $item->quantity,
                    unitCost: (float) $item->unit_cost,
                    variantId: $item->variant_id,
                    batchNumber: $transfer->number.'-'.$item->id,
                    source: $transfer,
                    type: StockMovement::TYPE_TRANSFER_IN,
                );

                $item->update(['received_quantity' => $item->quantity]);
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_RECEIVED,
                'received_by' => Auth::id(),
                'received_at' => now(),
            ]);
        });
    }

    public function cancel(StockTransfer $transfer): void
    {
        if (! $transfer->isPending()) {
            throw new LogicException('Only pending transfers can be cancelled.');
        }

        $transfer->update(['status' => StockTransfer::STATUS_CANCELLED]);
    }

    protected function assertStatus(StockTransfer $transfer, string $expected): void
    {
        if ($transfer->status !== $expected) {
            throw new LogicException("Transfer #{$transfer->number} must be '{$expected}' (currently '{$transfer->status}').");
        }
    }
}
