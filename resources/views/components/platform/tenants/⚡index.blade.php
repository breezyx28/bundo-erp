<?php

use App\Models\Tenant;
use App\Services\Modules\ModuleManager;
use App\Services\Tenancy\PlatformMetricsService;
use App\Services\Tenancy\TenantContext;
use App\Services\Tenancy\TenantProvisioningService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Tenants')] class extends Component
{
    use UiToast, WithFileUploads, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $domain = '';

    public string $primary_color = '#39C6A0';

    public string $secondary_color = '#228C70';

    public bool $is_active = true;

    public string $locale = 'ar';

    public string $timezone = 'Africa/Khartoum';

    public string $currency = 'SDG';

    public string $exchange_rate = '600';

    public string $branch_name = '';

    public string $branch_code = '';

    public string $admin_name = '';

    public string $admin_email = '';

    public string $admin_password = '';

    /** @var array<string,bool> */
    public array $moduleToggles = [];

    public $logo;

    public function mount(ModuleManager $modules): void
    {
        foreach ($modules->catalogForTenant(null) as $module) {
            $this->moduleToggles[$module['key']] = $module['enabled'];
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(PlatformMetricsService $metrics): array
    {
        $query = Tenant::query()
            ->withCount(['branches', 'users'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at');

        return [
            'tenants' => $query->paginate(10),
            'headers' => [
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'domain', 'label' => __('platform.domain')],
                ['key' => 'branches_count', 'label' => __('nav.branches'), 'class' => 'text-end'],
                ['key' => 'users_count', 'label' => __('nav.users'), 'class' => 'text-end'],
                ['key' => 'is_active', 'label' => __('common.status')],
            ],
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $tenant = Tenant::findOrFail($id);
        $this->editingId = $tenant->id;
        $this->name = $tenant->name;
        $this->domain = (string) $tenant->domain;
        $this->primary_color = $tenant->primary_color;
        $this->secondary_color = $tenant->secondary_color;
        $this->is_active = (bool) $tenant->is_active;
        $this->locale = (string) data_get($tenant->settings, 'locale', 'ar');
        $this->timezone = (string) data_get($tenant->settings, 'timezone', 'Africa/Khartoum');

        foreach (app(ModuleManager::class)->catalogForTenant($tenant->id) as $module) {
            $this->moduleToggles[$module['key']] = $module['enabled'];
        }

        $this->showModal = true;
    }

    public function save(TenantProvisioningService $provisioning, ModuleManager $modules): void
    {
        $rules = [
            'name' => 'required|string|max:120',
            'domain' => ['nullable', 'string', 'max:120', Rule::unique('tenants', 'domain')->ignore($this->editingId)],
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'locale' => 'required|in:ar,en',
            'timezone' => 'required|string|max:60',
            'currency' => 'required|in:SDG,USD',
            'exchange_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:2048',
        ];

        if (! $this->editingId) {
            $rules += [
                'branch_name' => 'required|string|max:120',
                'branch_code' => 'required|string|max:10',
                'admin_name' => 'required|string|max:120',
                'admin_email' => ['required', 'email', Rule::unique('users', 'email')],
                'admin_password' => 'required|string|min:8',
            ];
        }

        $this->validate($rules);

        if ($this->editingId) {
            $tenant = Tenant::findOrFail($this->editingId);
            $logoPath = $this->logo ? $this->logo->store('tenants/'.$tenant->id, 'public') : null;

            $provisioning->updateBranding($tenant, [
                'name' => $this->name,
                'domain' => $this->domain ?: null,
                'primary_color' => $this->primary_color,
                'secondary_color' => $this->secondary_color,
                'logo' => $logoPath,
                'is_active' => $this->is_active,
            ]);

            $tenant->update([
                'settings' => array_merge($tenant->settings ?? [], [
                    'locale' => $this->locale,
                    'timezone' => $this->timezone,
                ]),
            ]);

            $provisioning->syncModules($tenant, $this->moduleToggles);
            $modules->flush();

            $this->success(__('platform.tenant_updated'));
        } else {
            $tenant = $provisioning->create([
                'name' => $this->name,
                'domain' => $this->domain ?: null,
                'primary_color' => $this->primary_color,
                'secondary_color' => $this->secondary_color,
                'locale' => $this->locale,
                'timezone' => $this->timezone,
                'currency' => $this->currency,
                'exchange_rate' => (float) $this->exchange_rate,
                'modules' => $this->moduleToggles,
                'branch' => [
                    'name' => $this->branch_name,
                    'code' => $this->branch_code,
                ],
                'admin' => [
                    'name' => $this->admin_name,
                    'email' => $this->admin_email,
                    'password' => $this->admin_password,
                ],
            ]);

            if ($this->logo) {
                $tenant->update(['logo' => $this->logo->store('tenants/'.$tenant->id, 'public')]);
            }

            $this->success(__('platform.tenant_created'));
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function enterTenant(int $tenantId, TenantContext $context): void
    {
        $context->setTenant($tenantId);
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function toggleActive(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update(['is_active' => ! $tenant->is_active]);
        $this->success(__('common.updated'));
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'domain', 'logo',
            'branch_name', 'branch_code', 'admin_name', 'admin_email', 'admin_password',
        ]);
        $this->primary_color = '#39C6A0';
        $this->secondary_color = '#228C70';
        $this->is_active = true;
        $this->locale = 'ar';
        $this->timezone = 'Africa/Khartoum';
        $this->currency = 'SDG';
        $this->exchange_rate = '600';
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('platform.tenants')" separator progress-indicator>
        <x-slot:actions>
            <x-ui.button :label="__('platform.add_tenant')" wire:click="create" class="btn-primary btn-sm" icon="o-plus" />
        </x-slot:actions>
    </x-ui.header>

    <x-ui.card class="relative overflow-hidden">
        <x-ui.table-loading />
        <x-ui.table :headers="$headers" :rows="$tenants" striped with-pagination>
            <x-slot:toolbar>
                <x-ui.table-filters>
                    <x-ui.input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('common.search')" class="input-sm w-full sm:max-w-xs" />
                </x-ui.table-filters>
            </x-slot:toolbar>
            @scope('cell_is_active', $tenant)
                <x-ui.badge :value="$tenant->is_active ? __('common.active') : __('common.inactive')" :class="$tenant->is_active ? 'badge-success' : 'badge-ghost'" />
            @endscope
            @scope('actions', $tenant)
                <x-ui.button icon="o-arrow-right-end-on-rectangle" wire:click.stop="enterTenant({{ $tenant->id }})" class="btn-text btn-circle btn-sm" spinner />
                <x-ui.button icon="o-pencil-square" wire:click.stop="edit({{ $tenant->id }})" class="btn-text btn-circle btn-sm" />
                <x-ui.button :icon="$tenant->is_active ? 'o-pause' : 'o-play'" wire:click.stop="toggleActive({{ $tenant->id }})" class="btn-text btn-circle btn-sm" spinner />
            @endscope
        </x-ui.table>
    </x-ui.card>

    <x-ui.modal wire:model="showModal" :title="$editingId ? __('platform.edit_tenant') : __('platform.add_tenant')" class="backdrop-blur">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input :label="__('fields.name')" wire:model="name" />
            <x-ui.input :label="__('platform.domain')" wire:model="domain" />
            <x-ui.input type="color" :label="__('settings.primary_color')" wire:model="primary_color" />
            <x-ui.input type="color" :label="__('settings.secondary_color')" wire:model="secondary_color" />
            <x-ui.select :label="__('settings.locale')" wire:model="locale" :options="[['id' => 'ar', 'name' => 'العربية'], ['id' => 'en', 'name' => 'English']]" />
            <x-ui.input :label="__('settings.timezone')" wire:model="timezone" />
            <x-ui.file :label="__('settings.logo')" wire:model="logo" accept="image/*" />
            <x-ui.checkbox :label="__('common.active')" wire:model="is_active" />

            @if (! $editingId)
                <div class="md:col-span-2 border-t border-base-300 pt-4">
                    <p class="mb-3 text-sm font-semibold">{{ __('platform.first_branch') }}</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input :label="__('fields.name')" wire:model="branch_name" />
                        <x-ui.input :label="__('platform.branch_code')" wire:model="branch_code" />
                    </div>
                </div>
                <div class="md:col-span-2 border-t border-base-300 pt-4">
                    <p class="mb-3 text-sm font-semibold">{{ __('platform.admin_user') }}</p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-ui.input :label="__('fields.name')" wire:model="admin_name" />
                        <x-ui.input :label="__('auth.email')" wire:model="admin_email" type="email" />
                        <x-ui.password :label="__('users.password')" wire:model="admin_password" />
                        <x-ui.input :label="__('settings.default_currency')" wire:model="currency" />
                        <x-ui.input :label="__('settings.exchange_rate')" wire:model="exchange_rate" type="number" step="0.01" />
                    </div>
                </div>
            @endif

            <div class="md:col-span-2 border-t border-base-300 pt-4">
                <p class="mb-3 text-sm font-semibold">{{ __('platform.modules') }}</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($moduleToggles as $key => $enabled)
                        <x-ui.checkbox :label="$key" wire:model="moduleToggles.{{ $key }}" />
                    @endforeach
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-ui.button :label="__('common.cancel')" wire:click="$set('showModal', false)" />
            <x-ui.button :label="__('common.save')" wire:click="save" class="btn-primary" spinner="save" />
        </x-slot:actions>
    </x-ui.modal>
</div>
