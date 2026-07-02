<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        return Inertia::render('Categories/Index', [
            'categories' => Category::query()
                ->with('parent')
                ->withCount('products')
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
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
            'filters' => ['search' => $search],
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
