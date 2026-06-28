<?php

use App\Services\Settings\SettingsManager;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Settings')] class extends Component
{
    use UiToast, WithFileUploads;

    public string $tab = 'general';

    public string $company_name = '';

    public string $locale = 'ar';

    public string $timezone = 'Africa/Khartoum';

    public string $currency = 'SDG';

    public string $exchange_rate = '600';

    public string $invoice_prefix = 'INV-';

    public string $invoice_footer = '';

    public string $primary_color = '#39C6A0';

    public string $secondary_color = '#228C70';

    public $logo;

    public function mount(SettingsManager $settings): void
    {
        $this->authorize('settings.manage');

        $this->company_name = (string) ($settings->get('company_name', config('app.name')) ?? config('app.name'));
        $this->locale = (string) $settings->get('locale', 'ar');
        $this->timezone = (string) $settings->get('timezone', 'Africa/Khartoum');
        $this->currency = (string) $settings->get('default', 'SDG', group: 'currency');
        $this->exchange_rate = (string) $settings->get('exchange_rate', '600', group: 'currency');
        $this->invoice_prefix = (string) $settings->get('prefix', 'INV-', group: 'invoice');
        $this->invoice_footer = (string) $settings->get('footer', '', group: 'invoice');

        $tenant = Auth::user()?->tenant;
        $this->primary_color = $tenant?->primary_color ?? '#39C6A0';
        $this->secondary_color = $tenant?->secondary_color ?? '#228C70';
    }

    public function saveGeneral(SettingsManager $settings): void
    {
        $this->authorize('settings.manage');
        $this->validate([
            'company_name' => 'required|string|max:120',
            'locale' => 'required|in:ar,en',
            'timezone' => 'required|string|max:60',
        ]);

        $settings->set('company_name', $this->company_name);
        $settings->set('locale', $this->locale);
        $settings->set('timezone', $this->timezone);

        Auth::user()?->tenant?->update([
            'name' => $this->company_name,
            'settings' => array_merge(Auth::user()->tenant->settings ?? [], [
                'locale' => $this->locale,
                'timezone' => $this->timezone,
            ]),
        ]);

        $this->success(__('settings.saved'));
    }

    public function saveCurrency(SettingsManager $settings): void
    {
        $this->authorize('settings.manage');
        $this->validate([
            'currency' => 'required|in:SDG,USD',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        $settings->set('default', $this->currency, group: 'currency');
        $settings->set('exchange_rate', (float) $this->exchange_rate, group: 'currency', type: 'float');

        $this->success(__('settings.saved'));
    }

    public function saveBranding(TenantProvisioningService $provisioning): void
    {
        $this->authorize('settings.manage');
        $this->validate([
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'logo' => 'nullable|image|max:2048',
        ]);

        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            return;
        }

        $logoPath = $this->logo ? $this->logo->store('tenants/'.$tenant->id, 'public') : null;

        $provisioning->updateBranding($tenant, [
            'name' => $tenant->name,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'logo' => $logoPath,
        ]);

        $this->success(__('settings.saved'));
    }

    public function saveInvoice(SettingsManager $settings): void
    {
        $this->authorize('settings.manage');
        $this->validate([
            'invoice_prefix' => 'required|string|max:20',
            'invoice_footer' => 'nullable|string|max:500',
        ]);

        $settings->set('prefix', $this->invoice_prefix, group: 'invoice');
        $settings->set('footer', $this->invoice_footer, group: 'invoice');

        $this->success(__('settings.saved'));
    }
}; ?>

<div class="space-y-6">
    <x-ui.tabs wire:model="tab">
        <x-ui.tab name="general" :label="__('settings.general')">
            <x-ui.card>
                <div class="grid max-w-2xl gap-4">
                    <x-ui.input :label="__('settings.company_name')" wire:model="company_name" />
                    <x-ui.select :label="__('settings.locale')" wire:model="locale" :options="[['id' => 'ar', 'name' => 'العربية'], ['id' => 'en', 'name' => 'English']]" />
                    <x-ui.input :label="__('settings.timezone')" wire:model="timezone" />
                    <x-ui.button :label="__('common.save')" wire:click="saveGeneral" class="btn-primary w-fit" spinner="saveGeneral" />
                </div>
            </x-ui.card>
        </x-ui.tab>

        <x-ui.tab name="branding" :label="__('settings.branding')">
            <x-ui.card>
                <div class="grid max-w-2xl gap-4">
                    <x-ui.input type="color" :label="__('settings.primary_color')" wire:model="primary_color" />
                    <x-ui.input type="color" :label="__('settings.secondary_color')" wire:model="secondary_color" />
                    <x-ui.file :label="__('settings.logo')" wire:model="logo" accept="image/*" />
                    <x-ui.button :label="__('common.save')" wire:click="saveBranding" class="btn-primary w-fit" spinner="saveBranding" />
                </div>
            </x-ui.card>
        </x-ui.tab>

        <x-ui.tab name="currency" :label="__('settings.currency')">
            <x-ui.card>
                <div class="grid max-w-2xl gap-4">
                    <x-ui.select :label="__('settings.default_currency')" wire:model="currency" :options="[['id' => 'SDG', 'name' => 'SDG'], ['id' => 'USD', 'name' => 'USD']]" />
                    <x-ui.input :label="__('settings.exchange_rate')" wire:model="exchange_rate" type="number" step="0.01" />
                    <x-ui.button :label="__('common.save')" wire:click="saveCurrency" class="btn-primary w-fit" spinner="saveCurrency" />
                </div>
            </x-ui.card>
        </x-ui.tab>

        <x-ui.tab name="invoice" :label="__('settings.invoice')">
            <x-ui.card>
                <div class="grid max-w-2xl gap-4">
                    <x-ui.input :label="__('settings.invoice_prefix')" wire:model="invoice_prefix" />
                    <x-ui.textarea :label="__('settings.invoice_footer')" wire:model="invoice_footer" />
                    <x-ui.button :label="__('common.save')" wire:click="saveInvoice" class="btn-primary w-fit" spinner="saveInvoice" />
                </div>
            </x-ui.card>
        </x-ui.tab>
    </x-ui.tabs>
</div>
