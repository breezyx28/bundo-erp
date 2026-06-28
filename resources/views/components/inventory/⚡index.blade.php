<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Services\Branch\BranchContext;
use App\Services\Inventory\InventoryService;
use App\Support\FormSelectCatalog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Inventory')] class extends Component
{
    use UiToast, WithPagination;

    public string $search = '';

    public bool $onlyLowStock = false;

    // Receive modal
    public bool $showReceive = false;

    public ?int $r_product_id = null;

    public ?int $r_variant_id = null;

    public ?int $r_location_id = null;

    public int $r_quantity = 1;

    public float $r_unit_cost = 0;

    public string $r_batch_number = '';

    // Adjust modal
    public bool $showAdjust = false;

    public ?int $a_product_id = null;

    public ?int $a_variant_id = null;

    public int $a_quantity = 0;

    public string $a_reason = '';

    // Movements drawer
    public bool $showMovements = false;

    public ?int $m_product_id = null;

    public string $m_product_name = '';

    /** @var list<array{id:int,name:string}> */
    public array $productOptions = [];

    public bool $formCatalogsLoaded = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyLowStock(): void
    {
        $this->resetPage();
    }

    protected function loadFormCatalogs(): void
    {
        if ($this->formCatalogsLoaded) {
            return;
        }

        $this->productOptions = app(FormSelectCatalog::class)->products();
        $this->formCatalogsLoaded = true;
    }

    protected function context(): BranchContext
    {
        return app(BranchContext::class);
    }

    public function canAdjust(): bool
    {
        return Gate::allows('inventory.adjust');
    }

    public function canReceive(): bool
    {
        return Gate::allows('inventory.receive');
    }

    public function with(): array
    {
        $onHand = ProductBatch::query()
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $products = Product::query()
            ->active()
            ->search($this->search)
            ->orderBy('name')
            ->paginate(10);

        $products->getCollection()->transform(function (Product $p) use ($onHand) {
            $p->on_hand = (int) ($onHand[$p->id] ?? 0);
            $p->low_stock = $p->reorder_level > 0 && $p->on_hand <= $p->reorder_level;

            return $p;
        });

        if ($this->onlyLowStock) {
            $products->setCollection($products->getCollection()->filter(fn ($p) => $p->low_stock)->values());
        }

        return [
            'products' => $products,
            'locationOptions' => StockLocation::query()->active()->orderBy('name')->get(['id', 'name']),
            'branchName' => $this->context()->currentBranch()?->name,
            'isConsolidated' => $this->context()->isConsolidated(),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'sku', 'label' => __('fields.sku')],
                ['key' => 'on_hand', 'label' => __('inventory.on_hand'), 'class' => 'text-end'],
                ['key' => 'reorder_level', 'label' => __('fields.reorder_level'), 'class' => 'text-end'],
            ],
        ];
    }

    /** @return array<int, array{id:int, name:string}> */
    public function variantsFor(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        return app(FormSelectCatalog::class)->variantsFor($productId);
    }

    protected function requireBranch(): ?int
    {
        $branchId = $this->context()->currentBranchId();

        if ($branchId === null) {
            $this->error(__('inventory.pick_branch'));

            return null;
        }

        return $branchId;
    }

    public function openReceive(): void
    {
        $this->loadFormCatalogs();
        $this->reset(['r_product_id', 'r_variant_id', 'r_location_id', 'r_batch_number']);
        $this->r_quantity = 1;
        $this->r_unit_cost = 0;
        $this->showReceive = true;
    }

    public function receive(InventoryService $inventory): void
    {
        $this->authorize('inventory.receive');

        if (! $branchId = $this->requireBranch()) {
            return;
        }

        $data = $this->validate([
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
            variantId: $data['r_variant_id'],
            locationId: $data['r_location_id'],
            batchNumber: $data['r_batch_number'] ?: null,
        );

        $this->showReceive = false;
        $this->success(__('inventory.stock_received'));
    }

    public function openAdjust(int $productId): void
    {
        $this->reset(['a_variant_id', 'a_reason']);
        $this->a_product_id = $productId;
        $this->a_quantity = app(InventoryService::class)->availableQuantity($productId);
        $this->showAdjust = true;
    }

    public function adjust(InventoryService $inventory): void
    {
        $this->authorize('inventory.adjust');

        if (! $branchId = $this->requireBranch()) {
            return;
        }

        $data = $this->validate([
            'a_product_id' => 'required|integer|exists:products,id',
            'a_variant_id' => 'nullable|integer|exists:product_variants,id',
            'a_quantity' => 'required|integer|min:0',
            'a_reason' => 'nullable|string|max:255',
        ]);

        $inventory->adjust(
            branchId: $branchId,
            productId: $data['a_product_id'],
            newQuantity: $data['a_quantity'],
            variantId: $data['a_variant_id'],
            reason: $data['a_reason'],
        );

        $this->showAdjust = false;
        $this->success(__('inventory.stock_adjusted'));
    }

    public function openMovements(int $productId, string $name): void
    {
        $this->m_product_id = $productId;
        $this->m_product_name = $name;
        $this->showMovements = true;
    }

    /** @return \Illuminate\Support\Collection<int, StockMovement> */
    public function movements()
    {
        if (! $this->m_product_id) {
            return collect();
        }

        return StockMovement::query()
            ->where('product_id', $this->m_product_id)
            ->with('user:id,name')
            ->latest('id')
            ->limit(50)
            ->get();
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.inventory')" separator progress-indicator>
        <x-slot:subtitle>
            @if ($isConsolidated)
                <span class="text-warning">{{ __('inventory.consolidated_hint') }}</span>
            @elseif ($branchName)
                <span class="text-base-content/60">{{ $branchName }}</span>
            @endif
        </x-slot:subtitle>
        <x-slot:actions>
            @if ($this->canReceive())
                <x-ui.button :label="__('inventory.receive_stock')" icon="o-arrow-down-tray" class="btn-primary btn-sm" wire:click="openReceive" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$products" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                    <x-ui.toggle :label="__('inventory.low_stock_only')" wire:model.live="onlyLowStock" class="shrink-0" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_on_hand', $row)
                <div class="text-end tabular-nums">
                    <span class="font-semibold">{{ number_format($row->on_hand) }}</span>
                    @if ($row->low_stock)
                        <x-ui.badge :value="__('inventory.low')" class="badge-warning badge-sm ms-1" />
                    @endif
                </div>
            @endscope
            @scope('cell_reorder_level', $row)
                <span class="text-end tabular-nums text-base-content/60">{{ number_format($row->reorder_level) }}</span>
            @endscope
            @scope('actions', $row)
                <div class="flex gap-1">
                    <x-ui.button icon="o-clock" wire:click="openMovements({{ $row->id }}, '{{ addslashes($row->name) }}')"
                        class="btn-text btn-circle btn-sm" tooltip="{{ __('inventory.movements') }}" />
                    @if ($this->canAdjust())
                        <x-ui.button icon="o-adjustments-horizontal" wire:click="openAdjust({{ $row->id }})"
                            class="btn-text btn-circle btn-sm" tooltip="{{ __('inventory.adjust') }}" />
                    @endif
                </div>
            @endscope
        </x-ui.table>
    </x-ui.card>

    {{-- Receive stock --}}
    <x-ui.modal wire:model="showReceive" :title="__('inventory.receive_stock')" separator box-class="max-w-xl">
        <div class="grid gap-4">
            <x-form.search-select :label="__('nav.products')" wire:model.live="r_product_id" :options="$productOptions"
                :placeholder="__('common.none')" />
            @if ($this->variantsFor($r_product_id))
                <x-form.search-select :label="__('fields.variants')" wire:model="r_variant_id" :options="$this->variantsFor($r_product_id)"
                    :placeholder="__('common.none')" />
            @endif
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input :label="__('inventory.quantity')" wire:model="r_quantity" type="number" min="1" />
                <x-ui.input :label="__('fields.cost_price')" wire:model="r_unit_cost" type="number" step="0.01" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select :label="__('inventory.location')" wire:model="r_location_id" :options="$locationOptions"
                    option-value="id" option-label="name" :placeholder="__('common.none')" />
                <x-ui.input :label="__('inventory.batch_number')" wire:model="r_batch_number"
                    :placeholder="__('inventory.batch_auto')" />
            </div>
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showReceive', false)" />
            <x-ui.button :label="__('inventory.receive_stock')" class="btn-primary" wire:click="receive" spinner="receive" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Adjust stock --}}
    <x-ui.modal wire:model="showAdjust" :title="__('inventory.adjust_stock')" separator box-class="max-w-lg">
        <div class="grid gap-4">
            @if ($this->variantsFor($a_product_id))
                <x-form.search-select :label="__('fields.variants')" wire:model="a_variant_id" :options="$this->variantsFor($a_product_id)"
                    :placeholder="__('common.none')" />
            @endif
            <x-ui.input :label="__('inventory.new_quantity')" wire:model="a_quantity" type="number" min="0"
                :hint="__('inventory.adjust_hint')" />
            <x-ui.textarea :label="__('inventory.reason')" wire:model="a_reason" rows="2" />
        </div>
        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showAdjust', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary" wire:click="adjust" spinner="adjust" />
        </x-slot:actions>
    </x-ui.modal>

    {{-- Movement history --}}
    <x-ui.drawer wire:model="showMovements" :title="$m_product_name" :subtitle="__('inventory.movements')" right separator with-close-button class="w-11/12 lg:w-1/3">
        <div class="flex flex-col gap-2">
            @forelse ($this->movements() as $m)
                <div class="flex items-center justify-between rounded-box border border-base-300 p-3">
                    <div>
                        <x-ui.badge :value="__('inventory.types.' . $m->type)"
                            class="{{ $m->quantity_change >= 0 ? 'badge-success' : 'badge-error' }} badge-sm" />
                        <div class="mt-1 text-xs text-base-content/60">
                            {{ $m->created_at->format('Y-m-d H:i') }} · {{ $m->user?->name ?? '—' }}
                        </div>
                        @if ($m->reason)
                            <div class="text-xs text-base-content/50">{{ $m->reason }}</div>
                        @endif
                    </div>
                    <div class="tabular-nums font-semibold {{ $m->quantity_change >= 0 ? 'text-success' : 'text-error' }}">
                        {{ $m->quantity_change >= 0 ? '+' : '' }}{{ number_format($m->quantity_change) }}
                    </div>
                </div>
            @empty
                <x-ui.alert :title="__('common.no_results')" icon="o-information-circle" />
            @endforelse
        </div>
    </x-ui.drawer>
</div>
