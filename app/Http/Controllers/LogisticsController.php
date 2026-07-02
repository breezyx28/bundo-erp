<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\LogisticsCompanyRequest;
use App\Models\LogisticsCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LogisticsController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        return Inertia::render('Logistics/Index', [
            'companies' => LogisticsCompany::query()
                ->search($search)
                ->withCount('shipments')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (LogisticsCompany $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'email' => $c->email,
                    'contact_person' => $c->contact_person,
                    'address' => $c->address,
                    'rating' => $c->rating,
                    'notes' => $c->notes,
                    'is_active' => $c->is_active,
                    'shipments_count' => $c->shipments_count,
                ]),
            'filters' => [
                'search' => $search,
            ],
            'canManage' => Gate::allows('shipping.manage'),
        ]);
    }

    public function store(LogisticsCompanyRequest $request): RedirectResponse
    {
        LogisticsCompany::create($request->validated() + ['tenant_id' => Auth::user()->tenant_id]);

        $this->toastSuccess(__('common.created'));

        return redirect()->route('logistics.index');
    }

    public function update(LogisticsCompanyRequest $request, LogisticsCompany $logistic): RedirectResponse
    {
        $logistic->update($request->validated());

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('logistics.index');
    }

    public function destroy(LogisticsCompany $logistic): RedirectResponse
    {
        Gate::authorize('shipping.manage');

        $logistic->delete();

        $this->toastWarning(__('common.deleted'));

        return redirect()->route('logistics.index');
    }
}
