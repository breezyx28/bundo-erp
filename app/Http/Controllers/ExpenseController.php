<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PurchaseOrder;
use App\Services\Expenses\ExpenseService;
use App\Support\DateRange;
use App\Support\FormSelectCatalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['expense_date', 'amount'];

    public function index(Request $request, ExpenseService $service, FormSelectCatalog $catalog): Response
    {
        $search = (string) $request->string('search');
        $categoryFilter = $request->integer('category');
        $from = (string) ($request->string('from') ?: now()->startOfMonth()->toDateString());
        $to = (string) ($request->string('to') ?: now()->toDateString());

        $report = $service->report($from, $to);

        $query = Expense::query()
            ->search($search)
            ->when($categoryFilter, fn ($q) => $q->where('expense_category_id', $categoryFilter))
            ->whereBetween('expense_date', DateRange::bounds($from, $to))
            ->with(['category:id,name']);
        $this->applySort($query, $request, $this->sortable, 'expense_date', 'desc');

        return Inertia::render('Expenses/Index', [
            'expenses' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Expense $expense) => [
                    'id' => $expense->id,
                    'expense_date' => $expense->expense_date?->format('Y-m-d'),
                    'category' => $expense->category?->name,
                    'expense_category_id' => $expense->expense_category_id,
                    'description' => $expense->description,
                    'amount' => (float) $expense->amount,
                    'amount_formatted' => Money::format($expense->amount),
                    'payment_method' => $expense->payment_method,
                    'receipt_number' => $expense->receipt_number,
                    'receipt_url' => $expense->receipt_image ? Storage::url($expense->receipt_image) : null,
                    'is_linked' => $expense->isLinked(),
                    'linked' => $expense->reference_type === PurchaseOrder::class,
                    'purchase_order_id' => $expense->reference_type === PurchaseOrder::class ? $expense->reference_id : null,
                ]),
            'report' => [
                'total' => Money::format($report['total']),
                'count' => $report['count'],
                'by_category' => collect($report['by_category'])->map(fn ($row) => [
                    'category' => $row['category'],
                    'count' => $row['count'],
                    'total' => Money::format($row['total']),
                ])->all(),
            ],
            'categoryOptions' => ExpenseCategory::query()->active()->orderBy('name')->get(['id', 'name']),
            'methodOptions' => collect(['cash', 'bank_transfer', 'check'])
                ->map(fn ($m) => ['value' => $m, 'label' => __('purchasing.methods.'.$m)])->all(),
            'poOptions' => $catalog->purchaseOrders(),
            'sortOptions' => [
                ['value' => 'expense_date', 'label' => __('sales.date')],
                ['value' => 'amount', 'label' => __('purchasing.amount')],
            ],
            'filters' => [
                'search' => $search,
                'category' => $categoryFilter ?: null,
                'from' => $from,
                'to' => $to,
                'sort' => in_array($request->string('sort')->toString(), $this->sortable, true) ? $request->string('sort')->toString() : null,
                'direction' => strtolower((string) $request->string('direction')) === 'asc' ? 'asc' : 'desc',
            ],
            'canManage' => Gate::allows('expenses.create'),
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $this->persist($request->validated(), $request, new Expense);

        $this->toastSuccess(__('common.created'));

        return redirect()->route('expenses.index');
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->persist($request->validated(), $request, $expense);

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('expenses.index');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('expenses.delete');

        $expense->delete();

        $this->toastWarning(__('common.deleted'));

        return redirect()->route('expenses.index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data, Request $request, Expense $expense): void
    {
        $linked = (bool) ($data['linked'] ?? false);

        $expense->fill([
            'tenant_id' => Auth::user()->tenant_id,
            'expense_category_id' => $data['expense_category_id'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'],
            'receipt_number' => $data['receipt_number'] ?? null ?: null,
            'recorded_by' => Auth::id(),
            'reference_type' => $linked ? PurchaseOrder::class : null,
            'reference_id' => $linked ? ($data['purchase_order_id'] ?? null) : null,
        ]);

        if ($request->hasFile('receipt')) {
            $expense->receipt_image = $request->file('receipt')->store('expenses', 'public');
        }

        $expense->save();
    }
}
