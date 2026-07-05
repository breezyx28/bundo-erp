<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Services\Settings\SettingsManager;
use App\Services\Tenancy\TenantProvisioningService;
use App\Support\InvoiceDesign;
use App\Support\TenantMoney;
use Illuminate\Contracts\View\View;
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
                'invoice_design' => InvoiceDesign::currentKey(),
                'primary_color' => $tenant?->primary_color ?? '#39C6A0',
                'secondary_color' => $tenant?->secondary_color ?? '#228C70',
            ],
            'invoiceDesigns' => InvoiceDesign::options(),
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
            'invoice_design' => 'required|in:'.implode(',', InvoiceDesign::keys()),
        ]);

        $settings->set('prefix', $data['invoice_prefix'], group: 'invoice');
        $settings->set('footer', $data['invoice_footer'] ?? '', group: 'invoice');
        $settings->set('design', $data['invoice_design'], group: 'invoice');

        $this->toastSuccess(__('settings.saved'));

        return redirect()->route('settings.index');
    }

    public function previewInvoice(string $design): View
    {
        Gate::authorize('settings.manage');

        abort_unless(array_key_exists($design, InvoiceDesign::all()), 404);

        return view(InvoiceDesign::view($design), $this->previewData());
    }

    /** @return array{invoice: object, tenant: ?\App\Models\Tenant, print: bool} */
    protected function previewData(): array
    {
        $branch = (object) [
            'name' => __('nav.branches'),
            'phone' => '+249 123 456 789',
        ];
        $customer = (object) [
            'name' => __('sales.walk_in'),
            'phone' => '+249 987 654 321',
        ];
        $product = (object) ['name' => __('fields.name')];
        $items = collect([
            (object) [
                'product' => $product,
                'variant' => null,
                'quantity' => 2,
                'unit_price' => 1500.0,
                'total' => 3000.0,
            ],
        ]);

        $invoice = (object) [
            'invoice_number' => 'INV-0001',
            'invoice_date' => now(),
            'due_date' => null,
            'sale_type' => 'cash',
            'payment_status' => 'paid',
            'exchange_rate' => TenantMoney::exchangeRate(),
            'total_amount' => 3000.0,
            'discount_amount' => 0.0,
            'net_amount' => 3000.0,
            'net_amount_usd' => 3000.0 / max(TenantMoney::exchangeRate(), 1),
            'paid_amount' => 3000.0,
            'balance' => 0.0,
            'notes' => null,
            'branch' => $branch,
            'customer' => $customer,
            'items' => $items,
        ];

        return [
            'invoice' => $invoice,
            'tenant' => Auth::user()?->tenant,
            'print' => false,
        ];
    }
}
