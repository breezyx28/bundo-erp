<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Services\Settings\SettingsManager;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    use InteractsWithToast;

    public function index(SettingsManager $settings): Response
    {
        Gate::authorize('settings.manage');

        $tenant = Auth::user()?->tenant;

        return Inertia::render('Settings/Index', [
            'settings' => [
                'company_name' => (string) ($settings->get('company_name', config('app.name')) ?? config('app.name')),
                'locale' => (string) $settings->get('locale', 'ar'),
                'timezone' => (string) $settings->get('timezone', 'Africa/Khartoum'),
                'currency' => (string) $settings->get('default', 'SDG', group: 'currency'),
                'exchange_rate' => (string) $settings->get('exchange_rate', '600', group: 'currency'),
                'invoice_prefix' => (string) $settings->get('prefix', 'INV-', group: 'invoice'),
                'invoice_footer' => (string) $settings->get('footer', '', group: 'invoice'),
                'primary_color' => $tenant?->primary_color ?? '#39C6A0',
                'secondary_color' => $tenant?->secondary_color ?? '#228C70',
            ],
        ]);
    }

    public function saveGeneral(Request $request, SettingsManager $settings): RedirectResponse
    {
        Gate::authorize('settings.manage');

        $data = $request->validate([
            'company_name' => 'required|string|max:120',
            'locale' => 'required|in:ar,en',
            'timezone' => 'required|string|max:60',
        ]);

        $settings->set('company_name', $data['company_name']);
        $settings->set('locale', $data['locale']);
        $settings->set('timezone', $data['timezone']);

        Auth::user()?->tenant?->update([
            'name' => $data['company_name'],
            'settings' => array_merge(Auth::user()->tenant->settings ?? [], [
                'locale' => $data['locale'],
                'timezone' => $data['timezone'],
            ]),
        ]);

        $this->toastSuccess(__('settings.saved'));

        return redirect()->route('settings.index');
    }

    public function saveCurrency(Request $request, SettingsManager $settings): RedirectResponse
    {
        Gate::authorize('settings.manage');

        $data = $request->validate([
            'currency' => 'required|in:SDG,USD',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        $settings->set('default', $data['currency'], group: 'currency');
        $settings->set('exchange_rate', (float) $data['exchange_rate'], group: 'currency', type: 'float');

        $this->toastSuccess(__('settings.saved'));

        return redirect()->route('settings.index');
    }

    public function saveBranding(Request $request, TenantProvisioningService $provisioning): RedirectResponse
    {
        Gate::authorize('settings.manage');

        $data = $request->validate([
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'logo' => 'nullable|image|max:2048',
        ]);

        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            return redirect()->route('settings.index');
        }

        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('tenants/'.$tenant->id, 'public')
            : null;

        $provisioning->updateBranding($tenant, [
            'name' => $tenant->name,
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'logo' => $logoPath,
        ]);

        $this->toastSuccess(__('settings.saved'));

        return redirect()->route('settings.index');
    }

    public function saveInvoice(Request $request, SettingsManager $settings): RedirectResponse
    {
        Gate::authorize('settings.manage');

        $data = $request->validate([
            'invoice_prefix' => 'required|string|max:20',
            'invoice_footer' => 'nullable|string|max:500',
        ]);

        $settings->set('prefix', $data['invoice_prefix'], group: 'invoice');
        $settings->set('footer', $data['invoice_footer'] ?? '', group: 'invoice');

        $this->toastSuccess(__('settings.saved'));

        return redirect()->route('settings.index');
    }
}
