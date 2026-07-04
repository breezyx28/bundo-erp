<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTableFilters;
use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Models\StockLocation;
use App\Services\Branch\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    use AppliesTableFilters, InteractsWithToast;

    /** @var array<int, string> */
    protected array $sortable = ['name', 'code', 'phone', 'is_active'];

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $query = Branch::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"));
        $this->applySort($query, $request, $this->sortable, 'id', 'desc');

        return Inertia::render('Branches/Index', [
            'branches' => $query
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Branch $branch) => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'address' => $branch->address,
                    'phone' => $branch->phone,
                    'email' => $branch->email,
                    'primary_color' => $branch->primary_color,
                    'secondary_color' => $branch->secondary_color,
                    'is_active' => (bool) $branch->is_active,
                ]),
            'sortOptions' => [
                ['value' => 'name', 'label' => __('fields.name')],
                ['value' => 'code', 'label' => __('branches.code')],
                ['value' => 'phone', 'label' => __('branches.phone')],
            ],
            'filters' => [
                'search' => $search,
                ...$this->tableFilterState($request, $this->sortable),
            ],
        ]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $tenantId = app(BranchContext::class)->currentTenantId();

        $branch = Branch::create($request->validated() + ['tenant_id' => $tenantId]);

        StockLocation::create([
            'branch_id' => $branch->id,
            'name' => 'Main Store',
            'code' => 'MAIN',
            'type' => 'store',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->toastSuccess(__('branches.saved'));

        return redirect()->route('branches.index');
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $tenantId = app(BranchContext::class)->currentTenantId();
        $data = $request->validated();

        if (! $data['is_active'] && Branch::where('tenant_id', $tenantId)->where('is_active', true)->count() <= 1) {
            $this->toastError(__('branches.cannot_delete_last'));

            return redirect()->route('branches.index');
        }

        $branch->update($data);

        $this->toastSuccess(__('branches.saved'));

        return redirect()->route('branches.index');
    }
}
