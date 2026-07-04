<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['name', 'sku', 'selling_price', 'is_active', 'created_at'];

    public function index(Request $request, FormSelectCatalog $catalog): Response
    {
        $search = (string) $request->string('search');
        $categoryFilter = $request->integer('category');
        $brandFilter = $request->integer('brand');

        $query = Product::query()
            ->with(['category', 'brand', 'media', 'variants'])
            ->search($search)
            ->when($categoryFilter, fn ($q) => $q->where('category_id', $categoryFilter))
            ->when($brandFilter, fn ($q) => $q->where('brand_id', $brandFilter));
        $this->applyDateRange($query, $request, 'created_at');
        $this->applySort($query, $request, $this->sortable, 'id', 'desc');

        return Inertia::render('Products/Index', [
            'products' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'category_id' => $product->category_id,
                    'brand_id' => $product->brand_id,
                    'category' => $product->category?->name,
                    'unit' => $product->unit,
                    'cost_price' => (float) $product->cost_price,
                    'selling_price' => (float) $product->selling_price,
                    'selling_price_formatted' => Money::format($product->selling_price),
                    'reorder_level' => $product->reorder_level,
                    'description' => $product->description,
                    'is_active' => (bool) $product->is_active,
                    'has_variants' => (bool) $product->has_variants,
                    'image' => $product->getFirstMediaUrl('images', 'thumb') ?: null,
                    'variants' => $product->variants->map(fn ($variant) => [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'size' => $variant->options['size'] ?? '',
                        'color' => $variant->options['color'] ?? '',
                        'cost_price' => (float) $variant->cost_price,
                        'selling_price' => (float) $variant->selling_price,
                    ])->all(),
                ]),
            'sortOptions' => [
                ['value' => 'name', 'label' => __('fields.name')],
                ['value' => 'sku', 'label' => __('fields.sku')],
                ['value' => 'selling_price', 'label' => __('fields.selling_price')],
                ['value' => 'created_at', 'label' => __('sales.date')],
            ],
            'filters' => [
                'search' => $search,
                'category' => $categoryFilter ?: null,
                'brand' => $brandFilter ?: null,
                ...$this->tableFilterState($request, $this->sortable),
            ],
            'categories' => $catalog->categories(),
            'brands' => $catalog->brands(),
            'canManage' => Gate::allows('products.create'),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $product = Product::create(collect($data)->except(['image', 'variants'])->toArray());

        $this->syncVariants($product, (bool) $request->boolean('has_variants'), $request->input('variants', []));
        $this->syncImage($product, $request);

        $this->toastSuccess(__('common.created'));

        return redirect()->route('products.index');
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $product->update(collect($data)->except(['image', 'variants'])->toArray());

        $this->syncVariants($product, (bool) $request->boolean('has_variants'), $request->input('variants', []));
        $this->syncImage($product, $request);

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('products.delete');

        $product->delete();

        $this->toastWarning(__('common.deleted'));

        return redirect()->route('products.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    protected function syncVariants(Product $product, bool $hasVariants, array $variants): void
    {
        if (! $hasVariants) {
            $product->variants()->delete();

            return;
        }

        $keepIds = [];

        foreach ($variants as $variant) {
            $model = $product->variants()->updateOrCreate(
                ['id' => $variant['id'] ?? null],
                [
                    'sku' => $variant['sku'],
                    'options' => array_filter([
                        'size' => $variant['size'] ?? null,
                        'color' => $variant['color'] ?? null,
                    ]),
                    'cost_price' => $variant['cost_price'] ?? 0,
                    'selling_price' => $variant['selling_price'] ?? 0,
                    'is_active' => true,
                ],
            );
            $keepIds[] = $model->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->delete();
    }

    protected function syncImage(Product $product, Request $request): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $file = $request->file('image');

        $product->clearMediaCollection('images');
        $product->addMedia($file->getRealPath())
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection('images');
    }
}
