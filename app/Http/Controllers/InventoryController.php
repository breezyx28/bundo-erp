<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Support\FormSelectCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request, BranchContext $context, FormSelectCatalog $catalog): Response
    {
        $search = (string) $request->string('search');
        $onlyLowStock = $request->boolean('low_stock');

        $onHand = ProductBatch::query()
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $products = Product::query()
            ->active()
            ->search($search)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $products->getCollection()->transform(function (Product $p) use ($onHand) {
            $p->on_hand = (int) ($onHand[$p->id] ?? 0);
            $p->low_stock = $p->reorder_level > 0 && $p->on_hand <= $p->reorder_level;

            return $p;
        });

        if ($onlyLowStock) {
            $products->setCollection($products->getCollection()->filter(fn ($p) => $p->low_stock)->values());
        }

        $products = $products->through(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'on_hand' => $p->on_hand,
            'reorder_level' => $p->reorder_level,
            'low_stock' => $p->low_stock,
        ]);

        return Inertia::render('Inventory/Index', [
            'products' => $products,
            'productOptions' => $catalog->products(),
            'variantsByProduct' => $this->variantsByProduct(),
            'locationOptions' => StockLocation::query()->active()->orderBy('name')->get(['id', 'name']),
            'branchName' => $context->currentBranch()?->name,
            'isConsolidated' => $context->isConsolidated(),
            'movements' => Inertia::optional(fn () => $this->movements($request->integer('movement_product'))),
            'filters' => [
                'search' => $search,
                'low_stock' => $onlyLowStock,
            ],
            'canReceive' => Gate::allows('inventory.receive'),
            'canAdjust' => Gate::allows('inventory.adjust'),
        ]);
    }

    public function receive(Request $request, BranchContext $context, InventoryService $inventory): RedirectResponse
    {
        Gate::authorize('inventory.receive');

        $branchId = $context->currentBranchId();

        if ($branchId === null) {
            $this->toastError(__('inventory.pick_branch'));

            return redirect()->route('inventory.index');
        }

        $data = $request->validate([
            'r_product_id' => 'required|integer|exists:products,id',
            'r_variant_id' => 'nullable|integer|exists:product_variants,id',
            'r_location_id' => 'nullable|integer|exists:stock_locations,id',
            'r_quantity' => 'required|integer|min:1',
            'r_unit_cost' => 'required|numeric|min:0',
            'r_batch_number' => 'nullable|string|max:100',
        ]);

        $inventory->receive(
            branchId: $branchId,
            productId: $data['r_product_id'],
            quantity: $data['r_quantity'],
            unitCost: (float) $data['r_unit_cost'],
            variantId: $data['r_variant_id'] ?? null,
            locationId: $data['r_location_id'] ?? null,
            batchNumber: ($data['r_batch_number'] ?? null) ?: null,
        );

        $this->toastSuccess(__('inventory.stock_received'));

        return redirect()->route('inventory.index');
    }

    public function adjust(Request $request, BranchContext $context, InventoryService $inventory): RedirectResponse
    {
        Gate::authorize('inventory.adjust');

        $branchId = $context->currentBranchId();

        if ($branchId === null) {
            $this->toastError(__('inventory.pick_branch'));

            return redirect()->route('inventory.index');
        }

        $data = $request->validate([
            'a_product_id' => 'required|integer|exists:products,id',
            'a_variant_id' => 'nullable|integer|exists:product_variants,id',
            'a_quantity' => 'required|integer|min:0',
            'a_reason' => 'nullable|string|max:255',
        ]);

        $inventory->adjust(
            branchId: $branchId,
            productId: $data['a_product_id'],
            newQuantity: $data['a_quantity'],
            variantId: $data['a_variant_id'] ?? null,
            reason: $data['a_reason'] ?? '',
        );

        $this->toastSuccess(__('inventory.stock_adjusted'));

        return redirect()->route('inventory.index');
    }

    /**
     * @return array<int, array<int, array{id:int, name:string}>>
     */
    protected function variantsByProduct(): array
    {
        return ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->where('has_variants', true)->active())
            ->orderBy('size')
            ->orderBy('color')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($group) => $group->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'name' => $v->label(),
            ])->values()->all())
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function movements(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        return StockMovement::query()
            ->where('product_id', $productId)
            ->with('user:id,name')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (StockMovement $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'type_label' => __('inventory.types.'.$m->type),
                'quantity_change' => $m->quantity_change,
                'reason' => $m->reason,
                'user' => $m->user?->name,
                'created_at' => $m->created_at?->format('Y-m-d H:i'),
            ])
            ->all();
    }
}
