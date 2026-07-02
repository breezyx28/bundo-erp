<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        return Inertia::render('Suppliers/Index', [
            'suppliers' => Supplier::query()
                ->search($search)
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Supplier $supplier) => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'contact_person' => $supplier->contact_person,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'address' => $supplier->address,
                    'tax_number' => $supplier->tax_number,
                    'opening_balance' => (float) $supplier->opening_balance,
                    'notes' => $supplier->notes,
                    'is_active' => (bool) $supplier->is_active,
                ]),
            'filters' => ['search' => $search],
            'canManage' => Gate::allows('suppliers.create'),
        ]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        $this->toastSuccess(__('common.created'));

        return redirect()->route('suppliers.index');
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('suppliers.index');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('suppliers.delete');

        $supplier->delete();

        $this->toastWarning(__('common.deleted'));

        return redirect()->route('suppliers.index');
    }
}
