<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['name', 'products_count', 'is_active'];

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $query = Brand::query()
            ->withCount('products')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"));
        $this->applySort($query, $request, $this->sortable, 'id', 'desc');

        return Inertia::render('Brands/Index', [
            'brands' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Brand $brand) => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'products_count' => $brand->products_count,
                    'description' => $brand->description,
                    'is_active' => (bool) $brand->is_active,
                ]),
            'sortOptions' => [
                ['value' => 'name', 'label' => __('fields.name')],
                ['value' => 'products_count', 'label' => __('nav.products')],
            ],
            'filters' => [
                'search' => $search,
                ...$this->tableFilterState($request, $this->sortable),
            ],
        ]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        Brand::create($request->validated());

        $this->toastSuccess(__('common.created'));

        return redirect()->route('brands.index');
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('brands.index');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        $this->toastWarning(__('common.deleted'));

        return redirect()->route('brands.index');
    }
}
