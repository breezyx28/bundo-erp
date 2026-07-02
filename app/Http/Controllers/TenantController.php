<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\TenantRequest;
use App\Models\Tenant;
use App\Services\Modules\ModuleManager;
use App\Services\Tenancy\TenantContext;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request, ModuleManager $modules): Response
    {
        $search = (string) $request->string('search');

        return Inertia::render('Platform/Tenants/Index', [
            'tenants' => Tenant::query()
                ->withCount(['branches', 'users'])
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orderByDesc('created_at')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Tenant $tenant) => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'branches_count' => $tenant->branches_count,
                    'users_count' => $tenant->users_count,
                    'is_active' => (bool) $tenant->is_active,
                    'primary_color' => $tenant->primary_color,
                    'secondary_color' => $tenant->secondary_color,
                    'locale' => (string) data_get($tenant->settings, 'locale', 'ar'),
                    'timezone' => (string) data_get($tenant->settings, 'timezone', 'Africa/Khartoum'),
                    'moduleToggles' => $modules->catalogForTenant($tenant->id)
                        ->mapWithKeys(fn ($module) => [$module['key'] => $module['enabled']])
                        ->all(),
                ]),
            'filters' => ['search' => $search],
            'moduleCatalog' => $modules->catalogForTenant(null)
                ->map(fn ($module) => ['key' => $module['key'], 'name' => $module['name'], 'enabled' => $module['enabled']])
                ->all(),
        ]);
    }

    public function store(TenantRequest $request, TenantProvisioningService $provisioning): RedirectResponse
    {
        $data = $request->validated();

        $tenant = $provisioning->create([
            'name' => $data['name'],
            'domain' => $data['domain'] ?: null,
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'locale' => $data['locale'],
            'timezone' => $data['timezone'],
            'currency' => $data['currency'],
            'exchange_rate' => (float) $data['exchange_rate'],
            'modules' => $data['moduleToggles'] ?? [],
            'branch' => [
                'name' => $data['branch_name'],
                'code' => $data['branch_code'],
            ],
            'admin' => [
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
            ],
        ]);

        if ($request->hasFile('logo')) {
            $tenant->update(['logo' => $request->file('logo')->store('tenants/'.$tenant->id, 'public')]);
        }

        $this->toastSuccess(__('platform.tenant_created'));

        return redirect()->route('platform.tenants');
    }

    public function update(TenantRequest $request, Tenant $tenant, TenantProvisioningService $provisioning, ModuleManager $modules): RedirectResponse
    {
        $data = $request->validated();

        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('tenants/'.$tenant->id, 'public')
            : null;

        $provisioning->updateBranding($tenant, [
            'name' => $data['name'],
            'domain' => $data['domain'] ?: null,
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'logo' => $logoPath,
            'is_active' => $data['is_active'] ?? false,
        ]);

        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'locale' => $data['locale'],
                'timezone' => $data['timezone'],
            ]),
        ]);

        $provisioning->syncModules($tenant, $data['moduleToggles'] ?? []);
        $modules->flush();

        $this->toastSuccess(__('platform.tenant_updated'));

        return redirect()->route('platform.tenants');
    }

    public function enter(Tenant $tenant, TenantContext $context): RedirectResponse
    {
        $context->setTenant($tenant->id);

        return redirect()->route('dashboard');
    }

    public function toggleActive(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        $this->toastSuccess(__('common.updated'));

        return redirect()->route('platform.tenants');
    }
}
