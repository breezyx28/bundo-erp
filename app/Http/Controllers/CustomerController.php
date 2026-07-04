<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['name', 'phone', 'type'];

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');
        $type = (string) $request->string('type');

        $query = Customer::query()
            ->with('branchBalances')
            ->search($search)
            ->when($type, fn ($q) => $q->where('type', $type));
        $this->applySort($query, $request, $this->sortable, 'id', 'desc');

        return Inertia::render('Customers/Index', [
            'customers' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'type' => $customer->type,
                    'credit_limit' => (float) $customer->credit_limit,
                    'opening_balance' => (float) $customer->opening_balance,
                    'notes' => $customer->notes,
                    'is_active' => (bool) $customer->is_active,
                    'balance' => Money::format($customer->currentBalance()),
                    'badges' => collect($customer->badges())->map(fn ($badge) => [
                        'label' => __('badges.'.$badge['label']),
                        'color' => str_replace('badge-', '', $badge['color']),
                    ])->all(),
                ]),
            'sortOptions' => [
                ['value' => 'name', 'label' => __('fields.name')],
                ['value' => 'phone', 'label' => __('fields.phone')],
                ['value' => 'type', 'label' => __('fields.type')],
            ],
            'filters' => [
                'search' => $search,
                'type' => $type ?: null,
                ...$this->tableFilterState($request, $this->sortable),
            ],
            'canManage' => Gate::allows('customers.create'),
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        $this->toastSuccess(__('common.created'));

        return redirect()->route('customers.index');
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('customers.index');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('customers.delete');

        $customer->delete();

        $this->toastWarning(__('common.deleted'));

        return redirect()->route('customers.index');
    }
}
