<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['name', 'products_count', 'is_active'];

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $query = Category::query()
            ->with('parent')
            ->withCount('products')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"));
        $this->applySort($query, $request, $this->sortable, 'id', 'desc');

        return Inertia::render('Categories/Index', [
            'categories' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                    'parent' => $category->parent?->name,
                    'products_count' => $category->products_count,
                    'description' => $category->description,
                    'is_active' => (bool) $category->is_active,
                ]),
            'parents' => Category::query()->roots()->orderBy('name')->get(['id', 'name']),
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

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        $this->toastSuccess(__('common.created'));

        return redirect()->route('categories.index');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        $this->toastWarning(__('common.deleted'));

        return redirect()->route('categories.index');
    }
}
