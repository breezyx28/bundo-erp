<x-layouts.app :title="__('nav.dashboard')">
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-base-content">{{ __('common.welcome') }} 👋</h2>
            <p class="text-sm text-base-content/60">{{ config('app.name') }} ERP — {{ __('nav.dashboard') }}</p>
        </div>

        <x-ui.stats-group>
            <x-ui.stats-row>
                <x-slot:first>
                    <x-ui.stat title="Total Revenue" value="—" icon="o-arrow-trending-up" />
                </x-slot:first>
                <x-slot:second>
                    <x-ui.stat title="Net Profit" value="—" icon="o-banknotes" />
                </x-slot:second>
            </x-ui.stats-row>
            <x-ui.stats-break />
            <x-ui.stats-row>
                <x-slot:first>
                    <x-ui.stat title="Outstanding Debt" value="—" icon="o-credit-card" />
                </x-slot:first>
                <x-slot:second>
                    <x-ui.stat title="Low Stock Items" value="—" icon="o-exclamation-triangle" />
                </x-slot:second>
            </x-ui.stats-row>
        </x-ui.stats-group>

        <x-ui.card>
            <p class="text-base-content/70">
                Foundation ready. Modules will appear here as each phase is delivered.
            </p>
        </x-ui.card>
    </div>
</x-layouts.app>
