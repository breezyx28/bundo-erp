<?php

use App\Services\Tenancy\PlatformMetricsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.app')] #[Title('Platform')] class extends Component
{
    /** @var array<string, mixed> */
    public array $summary = [];

    /** @var list<array<string, mixed>> */
    public array $tenants = [];

    /** @var array<string, mixed> */
    public array $health = [];

    public function mount(PlatformMetricsService $metrics): void
    {
        $this->summary = $metrics->summary();
        $this->tenants = array_slice($metrics->tenantRows(), 0, 8);
        $this->health = $metrics->health();
    }
}; ?>

<div class="space-y-6">
    <x-ui.stats-group>
        <x-ui.stats-row>
            <x-slot:first>
                <x-ui.stat :title="__('platform.total_tenants')" :value="(string) $summary['tenants']" icon="o-building-office-2" />
            </x-slot:first>
            <x-slot:second>
                <x-ui.stat :title="__('platform.active_tenants')" :value="(string) $summary['active_tenants']" icon="o-check-badge" color="text-success" />
            </x-slot:second>
        </x-ui.stats-row>
        <x-ui.stats-break />
        <x-ui.stats-row>
            <x-slot:first>
                <x-ui.stat :title="__('platform.total_branches')" :value="(string) $summary['branches']" icon="o-building-storefront" />
            </x-slot:first>
            <x-slot:second>
                <x-ui.stat :title="__('platform.total_users')" :value="(string) $summary['users']" icon="o-user-group" />
            </x-slot:second>
        </x-ui.stats-row>
        <x-ui.stats-break />
        <x-ui.stats-row single>
            <x-ui.stat :title="__('platform.new_tenants_30d')" :value="(string) $summary['recent_tenants']" icon="o-sparkles" color="text-primary" />
        </x-ui.stats-row>
    </x-ui.stats-group>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card :title="__('platform.tenant_list')" class="lg:col-span-2">
            <x-slot:menu>
                <x-ui.button :label="__('platform.add_tenant')" link="{{ route('platform.tenants') }}" class="btn-primary btn-sm" icon="o-plus" />
            </x-slot:menu>
            <x-ui.table :headers="[
                ['key' => 'name', 'label' => __('fields.name')],
                ['key' => 'branches', 'label' => __('nav.branches'), 'class' => 'text-end'],
                ['key' => 'users', 'label' => __('nav.users'), 'class' => 'text-end'],
                ['key' => 'is_active', 'label' => __('common.status')],
            ]" :rows="$tenants" striped>
                @scope('cell_is_active', $row)
                    <x-ui.badge :value="$row['is_active'] ? __('common.active') : __('common.inactive')" :class="$row['is_active'] ? 'badge-success' : 'badge-ghost'" />
                @endscope
            </x-ui.table>
        </x-ui.card>

        <x-ui.card :title="__('platform.system_health')">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-base-content/60">{{ __('platform.database') }}</dt>
                    <dd><x-ui.badge :value="$health['db'] ? __('platform.healthy') : __('platform.unhealthy')" :class="$health['db'] ? 'badge-success' : 'badge-error'" /></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-base-content/60">{{ __('platform.queue') }}</dt>
                    <dd class="font-medium">{{ $health['queue'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-base-content/60">{{ __('platform.cache') }}</dt>
                    <dd class="font-medium">{{ $health['cache'] }}</dd>
                </div>
            </dl>
        </x-ui.card>
    </div>
</div>
