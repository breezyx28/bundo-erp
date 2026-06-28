<?php

use App\Models\Product;
use App\Support\FormSelectCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Traits\ConfirmsDeletion;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Products')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithFileUploads, WithPagination;

    public string $search = '';

    public ?int $categoryFilter = null;

    public ?int $brandFilter = null;

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form
    public string $name = '';

    public string $sku = '';

    public string $barcode = '';

    public ?int $category_id = null;

    public ?int $brand_id = null;

    public string $unit = 'pair';

    public float $cost_price = 0;

    public float $selling_price = 0;

    public int $reorder_level = 0;

    public string $description = '';

    public bool $is_active = true;

    public bool $has_variants = false;

    /** @var array<int, array{id:?int, sku:string, size:string, color:string, cost_price:float, selling_price:float}> */
    public array $variants = [];

    public $image;

    public function updatedBarcode(mixed $value): void
    {
        if ($value === null) {
            $this->barcode = '';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedBrandFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $catalog = app(FormSelectCatalog::class);

        return [
            'products' => Product::query()
                ->with(['category', 'brand', 'media'])
                ->search($this->search)
                ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
                ->when($this->brandFilter, fn ($q) => $q->where('brand_id', $this->brandFilter))
                ->latest()
                ->paginate(10),
            'headers' => [
                ['key' => 'image', 'label' => '', 'sortable' => false],
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'sku', 'label' => __('fields.sku')],
                ['key' => 'category', 'label' => __('fields.category')],
                ['key' => 'selling_price', 'label' => __('fields.selling_price')],
                ['key' => 'is_active', 'label' => __('common.status')],
            ],
            'categories' => $catalog->categories(),
            'brands' => $catalog->brands(),
        ];
    }

    public function canManage(): bool
    {
        return Gate::allows('products.create');
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'barcode', 'category_id', 'brand_id', 'description', 'image', 'variants']);
        $this->barcode = $this->barcode ?? '';
        $this->unit = 'pair';
        $this->cost_price = 0;
        $this->selling_price = 0;
        $this->reorder_level = 0;
        $this->is_active = true;
        $this->has_variants = false;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $product = Product::with('variants')->findOrFail($id);
        $this->editingId = $product->id;
        $attributes = $product->only([
            'name', 'sku', 'barcode', 'category_id', 'brand_id', 'unit', 'reorder_level', 'is_active', 'has_variants',
        ]);
        $attributes['barcode'] = $attributes['barcode'] ?? '';
        $this->fill($attributes);
        $this->cost_price = (float) $product->cost_price;
        $this->selling_price = (float) $product->selling_price;
        $this->description = (string) $product->description;
        $this->image = null;
        $this->variants = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'size' => $v->options['size'] ?? '',
            'color' => $v->options['color'] ?? '',
            'cost_price' => (float) $v->cost_price,
            'selling_price' => (float) $v->selling_price,
        ])->toArray();
        $this->showModal = true;
    }

    public function addVariant(): void
    {
        $this->variants[] = ['id' => null, 'sku' => '', 'size' => '', 'color' => '', 'cost_price' => $this->cost_price, 'selling_price' => $this->selling_price];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'products.update' : 'products.create');

        if (! $this->sku) {
            $this->sku = 'SKU-'.Str::upper(Str::random(8));
        }

        $data = $this->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit' => 'required|string|max:50',
            'cost_price' => 'numeric|min:0',
            'selling_price' => 'numeric|min:0',
            'reorder_level' => 'integer|min:0',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'has_variants' => 'boolean',
            'image' => 'nullable|image|max:4096',
            'variants' => 'array',
            'variants.*.sku' => 'required_with:variants|string|max:100',
            'variants.*.cost_price' => 'numeric|min:0',
            'variants.*.selling_price' => 'numeric|min:0',
        ]);

        $product = Product::updateOrCreate(
            ['id' => $this->editingId],
            collect($data)->except(['image', 'variants'])->toArray(),
        );

        $this->syncVariants($product);

        if ($this->image) {
            $product->clearMediaCollection('images');
            $product->addMedia($this->image->getRealPath())
                ->usingFileName($this->image->getClientOriginalName())
                ->toMediaCollection('images');
        }

        $this->showModal = false;
        $this->success($this->editingId ? __('common.updated') : __('common.created'));
    }

    protected function syncVariants(Product $product): void
    {
        if (! $this->has_variants) {
            $product->variants()->delete();

            return;
        }

        $keepIds = [];

        foreach ($this->variants as $variant) {
            $model = $product->variants()->updateOrCreate(
                ['id' => $variant['id'] ?? null],
                [
                    'sku' => $variant['sku'],
                    'options' => array_filter(['size' => $variant['size'] ?? null, 'color' => $variant['color'] ?? null]),
                    'cost_price' => $variant['cost_price'] ?? 0,
                    'selling_price' => $variant['selling_price'] ?? 0,
                    'is_active' => true,
                ],
            );
            $keepIds[] = $model->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->delete();
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $this->authorize('products.delete');
        Product::findOrFail($this->deleteId)->delete();
        $this->cancelDelete();
        $this->warning(__('common.deleted'));
    }
}; ?>

<div>
    <x-ui.header :title="__('nav.products')" separator progress-indicator>
        <x-slot:actions>
            @if ($this->canManage())
                <x-ui.button :label="__('common.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
            @endif
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$products" with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters class="w-full">
                    <x-ui.input :placeholder="__('common.search')" wire:model.live.debounce.400ms="search" clearable icon="o-magnifying-glass" class="input-sm w-full sm:max-w-xs" />
                    <x-form.search-select wire:model.live="categoryFilter" :options="$categories" :placeholder="__('fields.category')"
                        class="input-sm w-full sm:w-44" />
                    <x-form.search-select wire:model.live="brandFilter" :options="$brands" :placeholder="__('fields.brand')"
                        class="input-sm w-full sm:w-44" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_image', $row)
                @php($media = $row->getFirstMediaUrl('images', 'thumb'))
                <div class="avatar">
                    <div class="bg-base-content/10 h-10 w-10 overflow-hidden rounded-md">
                        @if ($media)
                            <img src="{{ $media }}" class="size-full object-cover" alt="">
                        @else
                            <div class="flex size-full items-center justify-center text-base-content/30">
                                <x-ui.icon name="o-cube" class="size-5" />
                            </div>
                        @endif
                    </div>
                </div>
            @endscope
            @scope('cell_name', $row)
                <div>
                    <div class="text-sm opacity-50">{{ $row->sku }}</div>
                    <div class="font-medium">{{ $row->name }}</div>
                </div>
            @endscope
            @scope('cell_category', $row)
                <div class="flex items-center">
                    <span class="badge badge-primary badge-soft me-2 rounded-full p-1">
                        <span class="icon-[tabler--tag] size-3.5"></span>
                    </span>
                    {{ $row->category?->name ?? '—' }}
                </div>
            @endscope
            @scope('cell_selling_price', $row)
                <span class="tabular-nums">{{ \App\Support\Money::format($row->selling_price) }}</span>
            @endscope
            @scope('cell_is_active', $row)
                <x-ui.badge :value="$row->is_active ? __('common.active') : __('common.inactive')"
                    class="{{ $row->is_active ? 'badge-success badge-soft' : 'badge-ghost badge-soft' }}" />
            @endscope
            @scope('actions', $row)
                @if ($this->canManage())
                    <x-ui.button icon="o-pencil" wire:click.stop="edit({{ $row->id }})" class="btn-text btn-circle btn-sm" tooltip="{{ __('common.edit') }}" />
                    <x-ui.button icon="o-trash" wire:click.stop="confirmDelete({{ $row->id }})"
                        class="btn-text btn-circle btn-sm text-error" tooltip="{{ __('common.delete') }}" />
                @endif
            @endscope
        </x-ui.table>
    </x-ui.card>

    <x-ui.modal wire:model="showModal" :title="$editingId ? __('common.edit') : __('common.create')" separator box-class="max-w-3xl">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.input :label="__('fields.name')" wire:model="name" />
            <x-ui.input :label="__('fields.sku')" wire:model="sku" :placeholder="__('common.none')" />
            <x-form.search-select :label="__('fields.category')" wire:model="category_id" :options="$categories" placeholder="—" />
            <x-form.search-select :label="__('fields.brand')" wire:model="brand_id" :options="$brands" placeholder="—" />
            <x-ui.input :label="__('fields.cost_price')" wire:model="cost_price" type="number" step="0.01" />
            <x-ui.input :label="__('fields.selling_price')" wire:model="selling_price" type="number" step="0.01" />
            <x-ui.input :label="__('fields.unit')" wire:model="unit" />
            <x-ui.input :label="__('fields.reorder_level')" wire:model="reorder_level" type="number" />
            <x-ui.input :label="__('fields.barcode')" wire:model="barcode" />
            <x-ui.file :label="__('fields.image')" wire:model="image" accept="image/*" />
            <div class="sm:col-span-2">
                <x-ui.textarea :label="__('fields.description')" wire:model="description" rows="2" />
            </div>
            <x-ui.toggle :label="__('common.active')" wire:model="is_active" />
            <x-ui.toggle :label="__('fields.has_variants')" wire:model.live="has_variants" />
        </div>

        @if ($has_variants)
            <div class="mt-5 space-y-3 rounded-xl border border-base-300 p-4">
                <div class="flex items-center justify-between">
                    <span class="font-medium">{{ __('fields.variants') }}</span>
                    <x-ui.button :label="__('common.create')" icon="o-plus" class="btn-sm" wire:click="addVariant" />
                </div>
                @foreach ($variants as $i => $variant)
                    <div class="grid items-end gap-2 sm:grid-cols-12" wire:key="variant-{{ $i }}">
                        <x-ui.input :label="__('fields.sku')" wire:model="variants.{{ $i }}.sku" class="sm:col-span-3" />
                        <x-ui.input :label="__('fields.size')" wire:model="variants.{{ $i }}.size" class="sm:col-span-2" />
                        <x-ui.input :label="__('fields.color')" wire:model="variants.{{ $i }}.color" class="sm:col-span-3" />
                        <x-ui.input :label="__('fields.selling_price')" wire:model="variants.{{ $i }}.selling_price" type="number" step="0.01" class="sm:col-span-3" />
                        <x-ui.button icon="o-trash" wire:click="removeVariant({{ $i }})" class="btn-ghost btn-sm text-error sm:col-span-1" />
                    </div>
                @endforeach
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" class="btn-text btn-sm" wire:click="$set('showModal', false)" />
            <x-ui.button :label="__('common.save')" class="btn-primary btn-sm" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.delete-confirm-modal />
</div>
