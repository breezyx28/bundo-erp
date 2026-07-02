<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Models\Branch;
use App\Services\Modules\ModuleManager;
use App\Services\Settings\SettingsManager;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    use InteractsWithToast;

    public function index(ModuleManager $modules): Response
    {
        $tenant = Auth::user()?->tenant;
        $branch = Auth::user()?->defaultBranch;

        $moduleToggles = [];

        foreach ($modules->catalogForTenant($tenant?->id) as $module) {
            $moduleToggles[] = ['key' => $module['key'], 'enabled' => (bool) $module['enabled']];
        }

        return Inertia::render('Onboarding/Index', [
            'defaults' => [
                'company_name' => $tenant?->name ?? '',
                'primary_color' => $tenant?->primary_color ?? '#39C6A0',
                'secondary_color' => $tenant?->secondary_color ?? '#228C70',
                'locale' => (string) data_get($tenant?->settings, 'locale', 'ar'),
                'timezone' => (string) data_get($tenant?->settings, 'timezone', 'Africa/Khartoum'),
                'branch_name' => $branch?->name ?? '',
                'branch_code' => $branch?->code ?? '',
                'branch_phone' => (string) ($branch?->phone ?? ''),
                'branch_address' => (string) ($branch?->address ?? ''),
                'currency' => 'SDG',
                'exchange_rate' => '600',
            ],
            'modules' => $moduleToggles,
        ]);
    }

    public function finish(
        Request $request,
        TenantProvisioningService $provisioning,
        SettingsManager $settings,
        ModuleManager $modules,
    ): RedirectResponse {
        $data = $request->validate([
            'company_name' => 'required|string|max:120',
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'locale' => 'required|in:ar,en',
            'timezone' => 'required|string|max:60',
            'currency' => 'required|in:SDG,USD',
            'exchange_rate' => 'required|numeric|min:0',
            'branch_name' => 'required|string|max:120',
            'branch_code' => 'required|string|max:10',
            'branch_phone' => 'nullable|string|max:50',
            'branch_address' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:4096',
            'moduleToggles' => 'array',
        ]);

        $tenant = Auth::user()?->tenant;

        if (! $tenant) {
            return redirect()->route('dashboard');
        }

        $logoPath = $request->hasFile('logo') ? $request->file('logo')->store('tenants/'.$tenant->id, 'public') : null;

        $provisioning->updateBranding($tenant, [
            'name' => $data['company_name'],
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'logo' => $logoPath,
        ]);

        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'locale' => $data['locale'],
                'timezone' => $data['timezone'],
            ]),
        ]);

        $branch = Auth::user()?->defaultBranch ?? Branch::where('tenant_id', $tenant->id)->first();

        if ($branch) {
            $branch->update([
                'name' => $data['branch_name'],
                'code' => strtoupper($data['branch_code']),
                'phone' => $data['branch_phone'] ?? null ?: null,
                'address' => $data['branch_address'] ?? null ?: null,
                'primary_color' => $data['primary_color'],
                'secondary_color' => $data['secondary_color'],
            ]);
        }

        $settings->set('company_name', $data['company_name']);
        $settings->set('locale', $data['locale']);
        $settings->set('timezone', $data['timezone']);
        $settings->set('default', $data['currency'], group: 'currency');
        $settings->set('exchange_rate', (float) $data['exchange_rate'], group: 'currency', type: 'float');

        $toggles = collect($data['moduleToggles'] ?? [])
            ->mapWithKeys(fn ($t) => [$t['key'] => (bool) ($t['enabled'] ?? false)])
            ->all();

        $provisioning->syncModules($tenant, $toggles);
        $modules->flush();
        $provisioning->completeOnboarding($tenant);

        session(['locale' => $data['locale']]);

        $this->toastSuccess(__('onboarding.completed'));

        return redirect()->route('dashboard');
    }
}
