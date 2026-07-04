<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\ExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['name', 'is_operational', 'expenses_count', 'is_active'];

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $query = ExpenseCategory::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('expenses');
        $this->applySort($query, $request, $this->sortable, 'id', 'desc');

        return Inertia::render('ExpenseCategories/Index', [
            'categories' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (ExpenseCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'is_operational' => (bool) $category->is_operational,
                    'is_active' => (bool) $category->is_active,
                    'expenses_count' => $category->expenses_count,
                ]),
            'sortOptions' => [
                ['value' => 'name', 'label' => __('fields.name')],
                ['value' => 'expenses_count', 'label' => __('nav.expenses')],
            ],
            'filters' => [
                'search' => $search,
                ...$this->tableFilterState($request, $this->sortable),
            ],
            'canManage' => Gate::allows('expenses.update'),
        ]);
    }

    public function store(ExpenseCategoryRequest $request): RedirectResponse
    {
        ExpenseCategory::create($request->validated() + ['tenant_id' => Auth::user()->tenant_id]);

        $this->toastSuccess(__('common.created'));

        return redirect()->route('expense-categories.index');
    }

    public function update(ExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->update($request->validated());

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('expense-categories.index');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        Gate::authorize('expenses.delete');

        $expenseCategory->delete();

        $this->toastWarning(__('common.deleted'));

        return redirect()->route('expense-categories.index');
    }
}
