<?php

namespace App\Services\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\ProductBatch;
use App\Models\Scopes\BranchScope;
use App\Models\StockMovement;
use App\Services\Branch\BranchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for stock mutations. Every method that changes stock
 * runs inside a transaction with row locks on the affected batches, so quantities
 * and COGS remain consistent under concurrent sales/transfers.
 *
 * Quantities are whole units (pairs of shoes). Costs are per-unit in base currency.
 */
class InventoryService
{
    public function __construct(protected BranchContext $context) {}

    /** Total on-hand quantity for an item in a branch. */
    public function availableQuantity(int $productId, ?int $variantId = null, ?int $branchId = null): int
    {
        $branchId = $branchId ?? $this->context->currentBranchId();

        return (int) ProductBatch::query()->withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $branchId)
            ->forItem($productId, $variantId)
            ->sum('quantity');
    }

    /**
     * Receive stock into a branch as a new batch and log a receipt movement.
     */
    public function receive(
        int $branchId,
        int $productId,
        int $quantity,
        float $unitCost,
        ?int $variantId = null,
        ?int $locationId = null,
        ?string $batchNumber = null,
        ?Model $source = null,
        ?Carbon $receivedAt = null,
        string $type = StockMovement::TYPE_RECEIPT,
    ): ProductBatch {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Receive quantity must be positive.');
        }

        return DB::transaction(function () use ($branchId, $productId, $quantity, $unitCost, $variantId, $locationId, $batchNumber, $source, $receivedAt, $type) {
            $batch = new ProductBatch([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'location_id' => $locationId,
                'batch_number' => $batchNumber ?: 'B-'.now()->format('ymd').'-'.strtoupper(substr(uniqid(), -5)),
                'quantity' => $quantity,
                'initial_quantity' => $quantity,
                'unit_cost' => $unitCost,
                'received_at' => $receivedAt ?? now(),
            ]);

            if ($source) {
                $batch->source_type = $source->getMorphClass();
                $batch->source_id = $source->getKey();
            }

            $batch->save();

            $this->logMovement($branchId, $productId, $variantId, $batch->id, $type, $quantity, $unitCost, $source);

            return $batch;
        });
    }

    /**
     * Deduct stock for a sale/transfer. Defaults to FIFO; pass $batchSelections
     * (ordered [['batch_id'=>x,'quantity'=>y], ...]) for manual batch selection.
     *
     * @return array{cost: float, allocations: array<int, array{batch_id:int, quantity:int, unit_cost:float}>}
     */
    public function deduct(
        int $branchId,
        int $productId,
        int $quantity,
        ?int $variantId = null,
        ?array $batchSelections = null,
        string $type = StockMovement::TYPE_SALE,
        ?Model $reference = null,
    ): array {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Deduct quantity must be positive.');
        }

        return DB::transaction(function () use ($branchId, $productId, $quantity, $variantId, $batchSelections, $type, $reference) {
            $available = (int) ProductBatch::query()->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branchId)
                ->forItem($productId, $variantId)
                ->lockForUpdate()
                ->sum('quantity');

            if ($available < $quantity) {
                throw InsufficientStockException::for($productId, $quantity, $available);
            }

            $batches = $this->resolveBatchesForDeduction($branchId, $productId, $variantId, $batchSelections);

            $remaining = $quantity;
            $cost = 0.0;
            $allocations = [];

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, $batch->quantity);

                if ($take <= 0) {
                    continue;
                }

                $batch->decrement('quantity', $take);
                $remaining -= $take;
                $cost += $take * (float) $batch->unit_cost;
                $allocations[] = ['batch_id' => $batch->id, 'quantity' => $take, 'unit_cost' => (float) $batch->unit_cost];

                $this->logMovement($branchId, $productId, $variantId, $batch->id, $type, -$take, (float) $batch->unit_cost, $reference);
            }

            if ($remaining > 0) {
                // Should not happen because of the availability check + lock.
                throw InsufficientStockException::for($productId, $quantity, $quantity - $remaining);
            }

            return ['cost' => round($cost, 2), 'allocations' => $allocations];
        });
    }

    /**
     * Set absolute on-hand quantity for an item, recording an adjustment.
     */
    public function adjust(int $branchId, int $productId, int $newQuantity, ?int $variantId = null, string $reason = ''): void
    {
        DB::transaction(function () use ($branchId, $productId, $newQuantity, $variantId, $reason) {
            $current = $this->availableQuantity($productId, $variantId, $branchId);
            $delta = $newQuantity - $current;

            if ($delta === 0) {
                return;
            }

            if ($delta > 0) {
                $unitCost = $this->weightedAverageCost($branchId, $productId, $variantId);
                $this->receive($branchId, $productId, $delta, $unitCost, $variantId, batchNumber: 'ADJ-'.now()->format('ymdHis'), type: StockMovement::TYPE_ADJUSTMENT);
                StockMovement::query()->withoutGlobalScope(BranchScope::class)->where('branch_id', $branchId)
                    ->where('product_id', $productId)->latest('id')->limit(1)
                    ->update(['reason' => $reason]);

                return;
            }

            $this->deduct($branchId, $productId, abs($delta), $variantId, type: StockMovement::TYPE_ADJUSTMENT);
            StockMovement::query()->withoutGlobalScope(BranchScope::class)->where('branch_id', $branchId)
                ->where('product_id', $productId)->where('type', StockMovement::TYPE_ADJUSTMENT)
                ->latest('id')->limit(1)->update(['reason' => $reason]);
        });
    }

    public function weightedAverageCost(int $branchId, int $productId, ?int $variantId = null): float
    {
        $batches = ProductBatch::query()->withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $branchId)
            ->forItem($productId, $variantId)
            ->where('quantity', '>', 0)
            ->get(['quantity', 'unit_cost']);

        $totalQty = $batches->sum('quantity');

        if ($totalQty <= 0) {
            return (float) (ProductBatch::query()->withoutGlobalScope(BranchScope::class)->where('branch_id', $branchId)
                ->forItem($productId, $variantId)->latest('received_at')->value('unit_cost') ?? 0);
        }

        $totalValue = $batches->sum(fn ($b) => $b->quantity * (float) $b->unit_cost);

        return round($totalValue / $totalQty, 2);
    }

    /**
     * @return Collection<int, ProductBatch>
     */
    protected function resolveBatchesForDeduction(int $branchId, int $productId, ?int $variantId, ?array $batchSelections)
    {
        if ($batchSelections) {
            $ids = collect($batchSelections)->pluck('batch_id')->all();

            $batches = ProductBatch::query()->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branchId)
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Preserve the caller's chosen order.
            return collect($ids)->map(fn ($id) => $batches->get($id))->filter()->values();
        }

        return ProductBatch::query()->withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $branchId)
            ->forItem($productId, $variantId)
            ->available()
            ->lockForUpdate()
            ->get();
    }

    protected function logMovement(int $branchId, int $productId, ?int $variantId, ?int $batchId, string $type, int $quantityChange, float $unitCost, ?Model $reference): void
    {
        $movement = new StockMovement([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'batch_id' => $batchId,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'unit_cost' => $unitCost,
            'user_id' => Auth::id(),
        ]);

        if ($reference) {
            $movement->reference_type = $reference->getMorphClass();
            $movement->reference_id = $reference->getKey();
        }

        $movement->save();
    }
}
