<?php

use App\Services\Reporting\DashboardService;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Dashboard')] class extends Component
{
    use UiToast;

    public string $currency = 'SDG';

    /** @var array<string, mixed> */
    public array $kpis = [];

    public function mount(): void
    {
        $this->kpis = app(DashboardService::class)->kpis();
    }

    public function with(): array
    {
        return ['charts' => $this->charts()];
    }

    public function rate(): float
    {
        return (float) ($this->kpis['rate'] ?? config('money.default_exchange_rate'));
    }

    /** Convert a base-currency (SDG) figure to the selected display currency. */
    public function conv(float $sdg): float
    {
        $rate = $this->rate();

        return $this->currency === 'USD' && $rate > 0 ? round($sdg / $rate, 2) : round($sdg, 2);
    }

    public function fmt(float $sdg): string
    {
        return Money::format($this->conv($sdg), $this->currency);
    }

    public function toggleCurrency(): void
    {
        $this->currency = $this->currency === 'SDG' ? 'USD' : 'SDG';
        $this->dispatch('dash-charts', charts: $this->charts());
    }

    public function refresh(): void
    {
        $service = app(DashboardService::class);
        $service->refresh();
        $this->kpis = $service->kpis();
        $this->dispatch('dash-charts', charts: $this->charts());
        $this->success(__('dashboard.refreshed'));
    }

    /**
     * Chart-ready series for the active currency.
     *
     * @return array<string, mixed>
     */
    public function charts(): array
    {
        $trend = $this->kpis['trend'] ?? ['labels' => [], 'revenue' => [], 'expenses' => []];
        $aging = $this->kpis['aging'] ?? ['current' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0];

        return [
            'currency' => $this->currency,
            'trend' => [
                'labels' => $trend['labels'],
                'revenue' => array_map(fn ($v) => $this->conv((float) $v), $trend['revenue']),
                'expenses' => array_map(fn ($v) => $this->conv((float) $v), $trend['expenses']),
            ],
            'products' => [
                'labels' => array_map(fn ($p) => $p['name'], $this->kpis['top_products'] ?? []),
                'values' => array_map(fn ($p) => $p['value'], $this->kpis['top_products'] ?? []),
            ],
            'brands' => [
                'labels' => array_map(fn ($b) => $b['name'], $this->kpis['top_brands'] ?? []),
                'values' => array_map(fn ($b) => $this->conv((float) $b['value']), $this->kpis['top_brands'] ?? []),
            ],
            'aging' => [
                'labels' => [__('debts.bucket.current'), __('debts.bucket.d30'), __('debts.bucket.d60'), __('debts.bucket.d90')],
                'values' => [$this->conv((float) $aging['current']), $this->conv((float) $aging['d30']), $this->conv((float) $aging['d60']), $this->conv((float) $aging['d90'])],
            ],
        ];
    }
}; ?>

<div class="space-y-6" x-data="dashCharts(@js($charts))">
    <x-ui.header :title="__('nav.dashboard')" :subtitle="now()->translatedFormat('l, d F Y')" separator>
        <x-slot:actions>
            <x-ui.button :label="$currency" icon="o-currency-dollar" wire:click="toggleCurrency" class="btn-soft btn-sm" />
            <x-ui.button icon="o-arrow-path" wire:click="refresh" spinner="refresh" class="btn-text btn-circle btn-sm" tooltip="{{ __('dashboard.refresh') }}" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Primary KPIs --}}
    <x-ui.stats-group>
        <x-ui.stats-row>
            <x-slot:first>
                <x-ui.stat :title="__('dashboard.revenue_month')" :value="$this->fmt($kpis['revenue']['month'])"
                    :description="__('dashboard.year') . ': ' . $this->fmt($kpis['revenue']['year'])" icon="o-arrow-trending-up" color="text-success" />
            </x-slot:first>
            <x-slot:second>
                <x-ui.stat :title="__('dashboard.expenses_month')" :value="$this->fmt($kpis['expenses']['month'])"
                    :description="__('dashboard.year') . ': ' . $this->fmt($kpis['expenses']['year'])" icon="o-arrow-trending-down" color="text-error" />
            </x-slot:second>
        </x-ui.stats-row>
        <x-ui.stats-break />
        <x-ui.stats-row>
            <x-slot:first>
                <x-ui.stat :title="__('dashboard.profit_month')" :value="$this->fmt($kpis['profit']['month'])"
                    :description="__('dashboard.year') . ': ' . $this->fmt($kpis['profit']['year'])" icon="o-banknotes" color="text-primary" />
            </x-slot:first>
            <x-slot:second>
                <x-ui.stat :title="__('dashboard.outstanding')" :value="$this->fmt($kpis['outstanding'])"
                    :description="__('dashboard.low_stock') . ': ' . $kpis['low_stock']" icon="o-credit-card" color="text-warning" />
            </x-slot:second>
        </x-ui.stats-row>
    </x-ui.stats-group>

    {{-- Charts --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.card :title="__('dashboard.trend')" class="lg:col-span-2">
            <div wire:ignore x-ref="trend" class="min-h-72 w-full"></div>
        </x-ui.card>
        <x-ui.card :title="__('dashboard.aging')">
            <div wire:ignore x-ref="aging" class="min-h-72 w-full"></div>
        </x-ui.card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card :title="__('dashboard.top_products')">
            <div wire:ignore x-ref="products" class="min-h-72 w-full"></div>
        </x-ui.card>
        <x-ui.card :title="__('dashboard.top_brands')">
            <div wire:ignore x-ref="brands" class="min-h-72 w-full"></div>
        </x-ui.card>
    </div>

    {{-- Recent + shipping --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <x-ui.card :title="__('dashboard.recent')" class="lg:col-span-2">
            <div class="divide-y divide-base-300">
                @forelse ($kpis['recent'] as $t)
                    <div class="flex items-center justify-between py-2">
                        <div class="flex items-center gap-2">
                            <x-ui.badge :value="__('dashboard.' . $t['type'])" class="{{ $t['type'] === 'sale' ? 'badge-success' : 'badge-info' }} badge-sm" />
                            <span class="font-medium">{{ $t['number'] }}</span>
                            <span class="text-sm text-base-content/60">{{ $t['party'] }}</span>
                        </div>
                        <div class="text-end">
                            <div class="tabular-nums font-medium">{{ $this->fmt($t['amount']) }}</div>
                            <div class="text-xs text-base-content/50">{{ $t['date'] }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-4 text-center text-base-content/50">{{ __('common.no_results') }}</div>
                @endforelse
            </div>
        </x-ui.card>
        <x-ui.card :title="__('dashboard.shipping')">
            <x-ui.stats-group compact>
                <x-ui.stats-row>
                    <x-slot:first>
                        <x-ui.stat :title="__('shipping.status.pending')" :value="(string) $kpis['shipping']['pending']" icon="o-clock" color="text-warning" />
                    </x-slot:first>
                    <x-slot:second>
                        <x-ui.stat :title="__('shipping.status.delivered')" :value="(string) $kpis['shipping']['delivered']" icon="o-check-badge" color="text-success" />
                    </x-slot:second>
                </x-ui.stats-row>
            </x-ui.stats-group>
            <a href="{{ route('reports.index') }}" class="btn btn-soft btn-sm mt-4 w-full">{{ __('dashboard.view_reports') }}</a>
        </x-ui.card>
    </div>
</div>

@script
<script>
    Alpine.data('dashCharts', (initial) => ({
        charts: {},
        money(val) {
            const sym = initial.currency === 'USD' ? '$' : 'SDG';
            return sym + ' ' + Number(val).toLocaleString(undefined, { maximumFractionDigits: 0 });
        },
        base() {
            const primary = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#228c70';
            const secondary = getComputedStyle(document.documentElement).getPropertyValue('--color-secondary').trim() || '#1a6f59';
            return {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, animations: { speed: 350 } },
                colors: [primary, '#b91c1c', secondary, '#b45309', '#2563eb'],
                grid: { borderColor: 'rgba(29, 42, 42, 0.08)', strokeDashArray: 4 },
                dataLabels: { enabled: false },
                legend: { position: 'bottom', fontSize: '11px', markers: { size: 4 } },
                stroke: { width: 2, curve: 'smooth' },
            };
        },
        init() {
            this.$nextTick(() => {
                if (typeof ApexCharts === 'undefined' || !this.$refs.trend) {
                    return;
                }
                this.renderAll(initial);
            });
            Livewire.on('dash-charts', (payload) => {
                const data = payload.charts ?? (Array.isArray(payload) ? payload[0]?.charts : null);
                if (data) {
                    this.updateAll(data);
                }
            });
        },
        renderAll(d) {
            if (!d?.trend) {
                return;
            }
            const base = this.base();
            this.charts.trend = new ApexCharts(this.$refs.trend, {
                ...base,
                chart: { ...base.chart, type: 'area', height: 280, fontFamily: 'inherit' },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.04 } },
                series: [
                    { name: '{{ __('dashboard.revenue') }}', data: d.trend.revenue ?? [] },
                    { name: '{{ __('dashboard.expenses') }}', data: d.trend.expenses ?? [] },
                ],
                xaxis: { categories: d.trend.labels ?? [], labels: { style: { fontSize: '11px' } } },
                yaxis: { labels: { formatter: (v) => this.money(v), style: { fontSize: '11px' } } },
            });
            this.charts.aging = new ApexCharts(this.$refs.aging, {
                ...base,
                chart: { ...base.chart, type: 'donut', height: 280 },
                labels: d.aging?.labels ?? [],
                series: d.aging?.values ?? [],
                plotOptions: { pie: { donut: { size: '68%' } } },
            });
            this.charts.products = new ApexCharts(this.$refs.products, {
                ...base,
                chart: { ...base.chart, type: 'bar', height: 280 },
                plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '58%' } },
                series: [{ name: '{{ __('dashboard.qty_sold') }}', data: d.products?.values ?? [] }],
                xaxis: { categories: d.products?.labels ?? [], labels: { style: { fontSize: '11px' } } },
            });
            this.charts.brands = new ApexCharts(this.$refs.brands, {
                ...base,
                chart: { ...base.chart, type: 'donut', height: 280 },
                labels: d.brands?.labels ?? [],
                series: d.brands?.values ?? [],
                plotOptions: { pie: { donut: { size: '68%' } } },
            });
            Object.values(this.charts).forEach((c) => c?.render());
        },
        updateAll(d) {
            if (!this.charts.trend || !d) {
                return;
            }
            this.charts.trend.updateOptions({ xaxis: { categories: d.trend.labels } });
            this.charts.trend.updateSeries([
                { name: '{{ __('dashboard.revenue') }}', data: d.trend.revenue },
                { name: '{{ __('dashboard.expenses') }}', data: d.trend.expenses },
            ]);
            this.charts.aging.updateOptions({ labels: d.aging.labels });
            this.charts.aging.updateSeries(d.aging.values);
            this.charts.products.updateOptions({ xaxis: { categories: d.products.labels } });
            this.charts.products.updateSeries([{ name: '{{ __('dashboard.qty_sold') }}', data: d.products.values }]);
            this.charts.brands.updateOptions({ labels: d.brands.labels });
            this.charts.brands.updateSeries(d.brands.values);
        },
    }));
</script>
@endscript
