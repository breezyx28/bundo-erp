<?php

use App\Models\Branch;
use App\Services\Modules\ModuleManager;
use App\Services\Settings\SettingsManager;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Onboarding')] class extends Component
{
    use UiToast, WithFileUploads;

    public int $step = 1;

    public string $company_name = '';

    public string $branch_name = '';

    public string $branch_code = '';

    public string $branch_phone = '';

    public string $branch_address = '';

    public string $locale = 'ar';

    public string $timezone = 'Africa/Khartoum';

    public string $currency = 'SDG';

    public string $exchange_rate = '600';

    public string $primary_color = '#39C6A0';

    public string $secondary_color = '#228C70';

    /** @var array<string,bool> */
    public array $moduleToggles = [];

    public $logo;

    public function mount(ModuleManager $modules): void
    {
        $tenant = Auth::user()?->tenant;
        $branch = Auth::user()?->defaultBranch;

        $this->company_name = $tenant?->name ?? '';
        $this->primary_color = $tenant?->primary_color ?? '#39C6A0';
        $this->secondary_color = $tenant?->secondary_color ?? '#228C70';
        $this->locale = (string) data_get($tenant?->settings, 'locale', 'ar');
        $this->timezone = (string) data_get($tenant?->settings, 'timezone', 'Africa/Khartoum');
        $this->branch_name = $branch?->name ?? '';
        $this->branch_code = $branch?->code ?? '';
        $this->branch_phone = (string) ($branch?->phone ?? '');
        $this->branch_address = (string) ($branch?->address ?? '');

        foreach ($modules->catalogForTenant($tenant?->id) as $module) {
            $this->moduleToggles[$module['key']] = $module['enabled'];
        }
    }

    public function next(): void
    {
        if ($this->step === 1) {
            $this->validate(['locale' => 'required|in:ar,en']);
        }

        if ($this->step === 2) {
            $this->validate([
                'company_name' => 'required|string|max:120',
                'primary_color' => 'required|string|max:7',
                'secondary_color' => 'required|string|max:7',
            ]);
        }

        if ($this->step === 3) {
            $this->validate([
                'branch_name' => 'required|string|max:120',
                'branch_code' => 'required|string|max:10',
            ]);
        }

        if ($this->step === 4) {
            $this->validate([
                'timezone' => 'required|string|max:60',
                'currency' => 'required|in:SDG,USD',
                'exchange_rate' => 'required|numeric|min:0',
            ]);
        }

        $this->step = min(6, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function finish(
        TenantProvisioningService $provisioning,
        SettingsManager $settings,
        ModuleManager $modules,
    ): void {
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            return;
        }

        $logoPath = $this->logo ? $this->logo->store('tenants/'.$tenant->id, 'public') : null;

        $provisioning->updateBranding($tenant, [
            'name' => $this->company_name,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'logo' => $logoPath,
        ]);

        $tenant->update([
            'settings' => array_merge($tenant->settings ?? [], [
                'locale' => $this->locale,
                'timezone' => $this->timezone,
            ]),
        ]);

        $branch = Auth::user()?->defaultBranch ?? Branch::where('tenant_id', $tenant->id)->first();
        if ($branch) {
            $branch->update([
                'name' => $this->branch_name,
                'code' => strtoupper($this->branch_code),
                'phone' => $this->branch_phone ?: null,
                'address' => $this->branch_address ?: null,
                'primary_color' => $this->primary_color,
                'secondary_color' => $this->secondary_color,
            ]);
        }

        $settings->set('company_name', $this->company_name);
        $settings->set('locale', $this->locale);
        $settings->set('timezone', $this->timezone);
        $settings->set('default', $this->currency, group: 'currency');
        $settings->set('exchange_rate', (float) $this->exchange_rate, group: 'currency', type: 'float');

        $provisioning->syncModules($tenant, $this->moduleToggles);
        $modules->flush();
        $provisioning->completeOnboarding($tenant);

        session(['locale' => $this->locale]);
        $this->success(__('onboarding.completed'));
        $this->step = 6;
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-6">
    <x-ui.card class="p-8">
        @if ($step === 1)
            <div class="space-y-4 text-center">
                <h1 class="text-2xl font-bold">{{ __('onboarding.welcome') }}</h1>
                <p class="text-base-content/60">{{ __('onboarding.welcome_sub') }}</p>
                <x-ui.select :label="__('settings.locale')" wire:model="locale" :options="[['id' => 'ar', 'name' => 'العربية'], ['id' => 'en', 'name' => 'English']]" class="mx-auto max-w-xs" />
            </div>
        @elseif ($step === 2)
            <h2 class="mb-4 text-xl font-semibold">{{ __('onboarding.business_title') }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input :label="__('settings.company_name')" wire:model="company_name" class="md:col-span-2" />
                <x-ui.input type="color" :label="__('onboarding.primary_color')" wire:model="primary_color" />
                <x-ui.input type="color" :label="__('onboarding.secondary_color')" wire:model="secondary_color" />
                <x-ui.file :label="__('settings.logo')" wire:model="logo" accept="image/*" class="md:col-span-2" />
            </div>
        @elseif ($step === 3)
            <h2 class="mb-4 text-xl font-semibold">{{ __('onboarding.branch_title') }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input :label="__('fields.name')" wire:model="branch_name" />
                <x-ui.input :label="__('branches.code')" wire:model="branch_code" />
                <x-ui.input :label="__('branches.phone')" wire:model="branch_phone" />
                <x-ui.textarea :label="__('branches.address')" wire:model="branch_address" class="md:col-span-2" />
            </div>
        @elseif ($step === 4)
            <h2 class="mb-4 text-xl font-semibold">{{ __('onboarding.system_title') }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.input :label="__('onboarding.timezone')" wire:model="timezone" />
                <x-ui.select :label="__('onboarding.currency')" wire:model="currency" :options="[['id' => 'SDG', 'name' => 'SDG'], ['id' => 'USD', 'name' => 'USD']]" />
                <x-ui.input :label="__('onboarding.exchange_rate')" wire:model="exchange_rate" type="number" step="0.01" />
            </div>
        @elseif ($step === 5)
            <h2 class="mb-4 text-xl font-semibold">{{ __('onboarding.modules_title') }}</h2>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($moduleToggles as $key => $enabled)
                    <x-ui.checkbox :label="$key" wire:model="moduleToggles.{{ $key }}" />
                @endforeach
            </div>
        @else
            <div class="space-y-4 text-center">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-success/15 text-success">
                    <x-ui.icon name="o-check" class="size-8" />
                </div>
                <h2 class="text-2xl font-bold">{{ __('onboarding.done_title') }}</h2>
                <p class="text-base-content/60">{{ __('onboarding.done_sub') }}</p>
                <x-ui.button :label="__('onboarding.go_dashboard')" link="{{ route('dashboard') }}" class="btn-primary" wire:navigate />
            </div>
        @endif

        @if ($step < 6)
            <div class="mt-8 flex justify-between border-t border-base-300 pt-6">
                @if ($step > 1)
                    <x-ui.button :label="__('onboarding.back')" wire:click="back" class="btn-ghost" />
                @else
                    <span></span>
                @endif

                @if ($step < 5)
                    <x-ui.button :label="__('onboarding.next')" wire:click="next" class="btn-primary" spinner="next" />
                @else
                    <x-ui.button :label="__('onboarding.finish')" wire:click="finish" class="btn-primary" spinner="finish" />
                @endif
            </div>
        @endif
    </x-ui.card>

    @if ($step < 6)
        <div class="flex justify-center gap-2">
            @foreach (range(1, 5) as $i)
                <span @class(['size-2 rounded-full', 'bg-primary' => $i <= $step, 'bg-base-300' => $i > $step])></span>
            @endforeach
        </div>
    @endif
</div>
