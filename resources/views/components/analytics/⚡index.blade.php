<?php

use App\Services\Analytics\AnalyticsService;
use App\Support\Money;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Traits\UiToast;

new #[Layout('components.layouts.app')] #[Title('Analytics & Predictions')] #[Lazy] class extends Component
{
    use UiToast;

    public function placeholder(): \Illuminate\Contracts\View\View
    {
        return view('components.ui.page-skeleton');
    }

    public string $currency = 'SDG';

    public string $tab = 'forecast';

    public function rate(): float
    {
        return (float) config('money.default_exchange_rate');
    }

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
        $this->dispatch('analytics-charts', charts: $this->charts(app(AnalyticsService::class)));
    }

    public function refresh(AnalyticsService $service): void
    {
        $service->refresh();
        $this->dispatch('analytics-charts', charts: $this->charts($service));
        $this->success(__('analytics.refreshed'));
    }

    /**
     * @return array<string, mixed>
     */
    public function charts(AnalyticsService $service): array
    {
        $forecast = $service->salesForecast();
        $ranking = $service->branchRanking();

        return [
            'currency' => $this->currency,
            'forecast' => [
                'labels' => $forecast['labels'],
                'actual' => array_map(fn ($v) => $v === null ? null : $this->conv((float) $v), $forecast['actual']),
                'forecast' => array_map(fn ($v) => $v === null ? null : $this->conv((float) $v), $forecast['forecast']),
            ],
            'ranking' => [
                'labels' => array_map(fn ($r) => $r['branch'], $ranking),
                'values' => array_map(fn ($r) => $this->conv((float) $r['profit']), $ranking),
            ],
        ];
    }

    public function with(): array
    {
        $service = app(AnalyticsService::class);

        return [
            'forecast' => $service->salesForecast(),
            'products' => $service->productPerformance(),
            'customers' => $service->customerAnalysis(),
            'inventory' => $service->inventoryOptimization(),
            'ranking' => $service->branchRanking(),
            'charts' => $this->charts($service),
            'tabs' => [
                ['id' => 'forecast', 'name' => __('analytics.tab_forecast')],
                ['id' => 'products', 'name' => __('analytics.tab_products')],
                ['id' => 'customers', 'name' => __('analytics.tab_customers')],
                ['id' => 'inventory', 'name' => __('analytics.tab_inventory')],
                ['id' => 'branches', 'name' => __('analytics.tab_branches')],
            ],
            'bestSellerHeaders' => [
                ['key' => 'name', 'label' => __('analytics.product')],
                ['key' => 'qty', 'label' => __('analytics.qty'), 'class' => 'text-end'],
                ['key' => 'revenue', 'label' => __('analytics.revenue'), 'class' => 'text-end'],
            ],
            'slowMoverHeaders' => [
                ['key' => 'name', 'label' => __('analytics.product')],
                ['key' => 'stock', 'label' => __('analytics.in_stock'), 'class' => 'text-end'],
                ['key' => 'sold', 'label' => __('analytics.sold_90d'), 'class' => 'text-end'],
            ],
            'customerHeaders' => [
                ['key' => 'name', 'label' => __('analytics.customer')],
                ['key' => 'clv', 'label' => __('analytics.lifetime_value'), 'class' => 'text-end'],
                ['key' => 'orders', 'label' => __('analytics.orders'), 'class' => 'text-end'],
                ['key' => 'last_order', 'label' => __('analytics.last_order')],
                ['key' => 'segment', 'label' => __('analytics.segment')],
            ],
            'inventoryHeaders' => [
                ['key' => 'name', 'label' => __('analytics.product')],
                ['key' => 'stock', 'label' => __('analytics.in_stock'), 'class' => 'text-end'],
                ['key' => 'daily', 'label' => __('analytics.daily_demand'), 'class' => 'text-end'],
                ['key' => 'days_left', 'label' => __('analytics.days_left'), 'class' => 'text-end'],
                ['key' => 'stockout', 'label' => __('analytics.stockout_date')],
                ['key' => 'reorder', 'label' => __('analytics.suggested_reorder'), 'class' => 'text-end'],
            ],
            'branchHeaders' => [
                ['key' => 'rank', 'label' => '#'],
                ['key' => 'branch', 'label' => __('analytics.branch')],
                ['key' => 'revenue', 'label' => __('analytics.revenue'), 'class' => 'text-end'],
                ['key' => 'profit', 'label' => __('analytics.profit'), 'class' => 'text-end'],
                ['key' => 'outstanding', 'label' => __('analytics.outstanding'), 'class' => 'text-end'],
            ],
        ];
    }

    public function segmentColor(string $segment): string
    {
        return match ($segment) {
            'loyal' => 'badge-success',
            'active' => 'badge-info',
            'at_risk' => 'badge-warning',
            'churned' => 'badge-error',
            default => 'badge-ghost',
        };
    }
}; ?>

<div class="space-y-6" x-data="analyticsCharts(@js($charts))">
    <x-ui.header :title="__('nav.analytics')" :subtitle="__('analytics.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-ui.button :label="$currency" icon="o-currency-dollar" wire:click="toggleCurrency" class="btn-outline" />
            <x-ui.button icon="o-arrow-path" wire:click="refresh" spinner="refresh" class="btn-ghost" tooltip="{{ __('analytics.refresh') }}" />
        </x-slot:actions>
    </x-ui.header>

    {{-- Headline projection KPIs --}}
    <x-ui.stats-group>
        <x-ui.stats-row>
            <x-slot:first>
                <x-ui.stat :title="__('analytics.next_month')" :value="$this->fmt((float) ($forecast['forecast'][count($forecast['forecast']) - 6] ?? 0))"
                    :description="__('analytics.projected_revenue')" icon="o-presentation-chart-line" color="text-primary" />
            </x-slot:first>
            <x-slot:second>
                <x-ui.stat :title="__('analytics.trend_growth')" :value="number_format($forecast['growth'], 1) . '%'"
                    :description="__('analytics.per_month')" :icon="$forecast['growth'] >= 0 ? 'o-arrow-trending-up' : 'o-arrow-trending-down'"
                    :color="$forecast['growth'] >= 0 ? 'text-success' : 'text-error'" />
            </x-slot:second>
        </x-ui.stats-row>
        <x-ui.stats-break />
        <x-ui.stats-row single>
            <x-ui.stat :title="__('analytics.reorder_alerts')" :value="(string) count($inventory)"
                :description="__('analytics.products_at_risk')" icon="o-exclamation-triangle" color="text-warning" />
        </x-ui.stats-row>
    </x-ui.stats-group>

    <x-ui.tabs wire:model="tab">
        {{-- Sales forecast --}}
        <x-ui.tab name="forecast" :label="__('analytics.tab_forecast')" icon="o-chart-bar">
            <x-ui.card :title="__('analytics.sales_forecast')" :subtitle="__('analytics.forecast_hint')">
                <div wire:ignore x-ref="forecast"></div>
            </x-ui.card>
        </x-ui.tab>

        {{-- Product performance --}}
        <x-ui.tab name="products" :label="__('analytics.tab_products')" icon="o-cube">
            <div class="grid gap-4 lg:grid-cols-2">
                <x-ui.card :title="__('analytics.best_sellers')">
                    <x-ui.table :headers="$bestSellerHeaders" :rows="$products['best']" show-empty-text :empty-text="__('common.no_results')">
                        @scope('cell_name', $row)
                            <span class="font-medium">{{ $row['name'] }}</span>
                        @endscope
                        @scope('cell_qty', $row)
                            <span class="text-end tabular-nums">{{ number_format($row['qty']) }}</span>
                        @endscope
                        @scope('cell_revenue', $row)
                            <span class="text-end tabular-nums">{{ $this->fmt($row['revenue']) }}</span>
                        @endscope
                    </x-ui.table>
                </x-ui.card>
                <x-ui.card :title="__('analytics.slow_movers')" :subtitle="__('analytics.slow_hint')">
                    <x-ui.table :headers="$slowMoverHeaders" :rows="$products['slow']" show-empty-text :empty-text="__('common.no_results')">
                        @scope('cell_name', $row)
                            <span class="font-medium">{{ $row['name'] }}</span>
                        @endscope
                        @scope('cell_stock', $row)
                            <span class="text-end tabular-nums">{{ number_format($row['stock']) }}</span>
                        @endscope
                        @scope('cell_sold', $row)
                            <span class="text-end tabular-nums {{ $row['sold'] == 0 ? 'text-error' : '' }}">{{ number_format($row['sold']) }}</span>
                        @endscope
                    </x-ui.table>
                </x-ui.card>
            </div>
        </x-ui.tab>

        {{-- Customer analysis --}}
        <x-ui.tab name="customers" :label="__('analytics.tab_customers')" icon="o-users">
            <x-ui.card :title="__('analytics.customer_value')" :subtitle="__('analytics.customer_hint')">
                <x-ui.table :headers="$customerHeaders" :rows="$customers" show-empty-text :empty-text="__('common.no_results')">
                    @scope('cell_name', $row)
                        <span class="font-medium">{{ $row['name'] }}</span>
                    @endscope
                    @scope('cell_clv', $row)
                        <span class="text-end tabular-nums text-success">{{ $this->fmt($row['clv']) }}</span>
                    @endscope
                    @scope('cell_orders', $row)
                        <span class="text-end tabular-nums">{{ $row['orders'] }}</span>
                    @endscope
                    @scope('cell_last_order', $row)
                        <span class="text-sm text-base-content/60">{{ $row['last_order'] }} <span class="text-xs">({{ trans_choice('analytics.days_ago', $row['days_since'], ['count' => $row['days_since']]) }})</span></span>
                    @endscope
                    @scope('cell_segment', $row)
                        <x-ui.badge :value="__('analytics.segment_' . $row['segment'])" class="{{ $this->segmentColor($row['segment']) }} badge-sm" />
                    @endscope
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab>

        {{-- Inventory optimization --}}
        <x-ui.tab name="inventory" :label="__('analytics.tab_inventory')" icon="o-archive-box">
            <x-ui.card :title="__('analytics.reorder_suggestions')" :subtitle="__('analytics.reorder_hint')">
                <x-ui.table :headers="$inventoryHeaders" :rows="$inventory" show-empty-text :empty-text="__('analytics.no_reorder_needed')">
                    @scope('cell_name', $row)
                        <span class="font-medium">{{ $row['name'] }}</span>
                    @endscope
                    @scope('cell_stock', $row)
                        <span class="text-end tabular-nums">{{ number_format($row['stock']) }}</span>
                    @endscope
                    @scope('cell_daily', $row)
                        <span class="text-end tabular-nums">{{ number_format($row['daily'], 2) }}</span>
                    @endscope
                    @scope('cell_days_left', $row)
                        <span class="text-end tabular-nums {{ $row['days_left'] <= 14 ? 'text-error font-semibold' : 'text-warning' }}">{{ $row['days_left'] }}</span>
                    @endscope
                    @scope('cell_stockout', $row)
                        <span class="text-sm text-base-content/60">{{ $row['stockout'] }}</span>
                    @endscope
                    @scope('cell_reorder', $row)
                        <span class="text-end tabular-nums font-semibold text-primary">{{ number_format($row['reorder']) }}</span>
                    @endscope
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab>

        {{-- Branch ranking --}}
        <x-ui.tab name="branches" :label="__('analytics.tab_branches')" icon="o-building-storefront">
            <div class="grid gap-4 lg:grid-cols-3">
                <x-ui.card :title="__('analytics.profit_ranking')" class="lg:col-span-1">
                    <div wire:ignore x-ref="ranking"></div>
                </x-ui.card>
                <x-ui.card :title="__('analytics.branch_scorecard')" class="lg:col-span-2">
                    <x-ui.table :headers="$branchHeaders" :rows="$ranking" show-empty-text :empty-text="__('common.no_results')">
                        @scope('cell_rank', $row)
                            <x-ui.badge :value="(string) $row['rank']" class="{{ $row['rank'] === 1 ? 'badge-success' : 'badge-ghost' }} badge-sm" />
                        @endscope
                        @scope('cell_branch', $row)
                            <span class="font-medium">{{ $row['branch'] }}</span>
                        @endscope
                        @scope('cell_revenue', $row)
                            <span class="text-end tabular-nums">{{ $this->fmt($row['revenue']) }}</span>
                        @endscope
                        @scope('cell_profit', $row)
                            <span class="text-end tabular-nums font-semibold {{ $row['profit'] >= 0 ? 'text-success' : 'text-error' }}">{{ $this->fmt($row['profit']) }}</span>
                        @endscope
                        @scope('cell_outstanding', $row)
                            <span class="text-end tabular-nums text-warning">{{ $this->fmt($row['outstanding']) }}</span>
                        @endscope
                    </x-ui.table>
                </x-ui.card>
            </div>
        </x-ui.tab>
    </x-ui.tabs>
</div>

@script
<script>
    Alpine.data('analyticsCharts', (initial) => ({
        charts: {},
        base() {
            const primary = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#228c70';
            const secondary = getComputedStyle(document.documentElement).getPropertyValue('--color-secondary').trim() || '#1a6f59';
            return {
                chart: { fontFamily: 'inherit', toolbar: { show: false }, animations: { speed: 350 } },
                colors: [primary, secondary, '#2563eb', '#b45309', '#b91c1c'],
                grid: { borderColor: 'rgba(29, 42, 42, 0.08)', strokeDashArray: 4 },
                dataLabels: { enabled: false },
                legend: { position: 'bottom', fontSize: '11px', markers: { size: 4 } },
                stroke: { width: 2, curve: 'smooth' },
            };
        },
        init() {
            this.renderAll(initial);
            Livewire.on('analytics-charts', (payload) => {
                const data = payload.charts ?? (Array.isArray(payload) ? payload[0].charts : payload);
                this.updateAll(data);
            });
        },
        renderAll(d) {
            this.charts.forecast = new ApexCharts(this.$refs.forecast, {
                ...this.base(),
                chart: { ...this.base().chart, type: 'line', height: 340 },
                stroke: { curve: 'smooth', width: [3, 3], dashArray: [0, 6] },
                markers: { size: 0 },
                series: [
                    { name: '{{ __('analytics.actual') }}', data: d.forecast.actual },
                    { name: '{{ __('analytics.forecast') }}', data: d.forecast.forecast },
                ],
                xaxis: { categories: d.forecast.labels },
            });
            if (this.$refs.ranking) {
                this.charts.ranking = new ApexCharts(this.$refs.ranking, {
                    ...this.base(),
                    chart: { ...this.base().chart, type: 'bar', height: 340 },
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true } },
                    legend: { show: false },
                    series: [{ name: '{{ __('analytics.profit') }}', data: d.ranking.values }],
                    xaxis: { categories: d.ranking.labels },
                });
            }
            Object.values(this.charts).forEach((c) => c.render());
        },
        updateAll(d) {
            this.charts.forecast?.updateOptions({ xaxis: { categories: d.forecast.labels } });
            this.charts.forecast?.updateSeries([
                { name: '{{ __('analytics.actual') }}', data: d.forecast.actual },
                { name: '{{ __('analytics.forecast') }}', data: d.forecast.forecast },
            ]);
            this.charts.ranking?.updateOptions({ xaxis: { categories: d.ranking.labels } });
            this.charts.ranking?.updateSeries([{ name: '{{ __('analytics.profit') }}', data: d.ranking.values }]);
        },
    }));
</script>
@endscript
