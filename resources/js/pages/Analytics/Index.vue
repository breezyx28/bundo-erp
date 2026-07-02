<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import StatCard from '@/components/StatCard.vue';
import { useTrans } from '@/composables/useTrans';
import { useMoney } from '@/composables/useMoney';
import { chartBase } from '@/composables/useChartBase';

const props = defineProps({
    forecast: { type: Object, required: true },
    products: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    inventory: { type: Array, default: () => [] },
    ranking: { type: Array, default: () => [] },
    rate: { type: Number, default: 0 },
    currencies: { type: Object, default: () => ({}) },
});

const { t } = useTrans();
const { currency, toggle, conv, fmt } = useMoney(props.currencies, props.rate);

const tab = ref('forecast');
const tabItems = computed(() => [
    { value: 'forecast', label: t('analytics.tab_forecast'), icon: 'i-heroicons-chart-bar' },
    { value: 'products', label: t('analytics.tab_products'), icon: 'i-heroicons-cube' },
    { value: 'customers', label: t('analytics.tab_customers'), icon: 'i-heroicons-users' },
    { value: 'inventory', label: t('analytics.tab_inventory'), icon: 'i-heroicons-archive-box' },
    { value: 'branches', label: t('analytics.tab_branches'), icon: 'i-heroicons-building-storefront' },
]);

const nextMonth = computed(() => {
    const list = props.forecast.forecast ?? [];
    return list[list.length - 6] ?? 0;
});

// Charts
const forecastSeries = computed(() => [
    { name: t('analytics.actual'), data: (props.forecast.actual ?? []).map((v) => (v === null ? null : conv(v))) },
    { name: t('analytics.forecast'), data: (props.forecast.forecast ?? []).map((v) => (v === null ? null : conv(v))) },
]);
const forecastOptions = computed(() => {
    const base = chartBase();
    return {
        ...base,
        chart: { ...base.chart, type: 'line', height: 340 },
        stroke: { curve: 'smooth', width: [3, 3], dashArray: [0, 6] },
        markers: { size: 0 },
        xaxis: { categories: props.forecast.labels ?? [] },
    };
});

const rankingSeries = computed(() => [
    { name: t('analytics.profit'), data: props.ranking.map((r) => conv(r.profit)) },
]);
const rankingOptions = computed(() => {
    const base = chartBase();
    return {
        ...base,
        chart: { ...base.chart, type: 'bar', height: 340 },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true } },
        legend: { show: false },
        xaxis: { categories: props.ranking.map((r) => r.branch) },
    };
});

const bestSellerHeaders = [
    { key: 'name', label: t('analytics.product') },
    { key: 'qty', label: t('analytics.qty'), class: 'text-end' },
    { key: 'revenue', label: t('analytics.revenue'), class: 'text-end' },
];
const slowMoverHeaders = [
    { key: 'name', label: t('analytics.product') },
    { key: 'stock', label: t('analytics.in_stock'), class: 'text-end' },
    { key: 'sold', label: t('analytics.sold_90d'), class: 'text-end' },
];
const customerHeaders = [
    { key: 'name', label: t('analytics.customer') },
    { key: 'clv', label: t('analytics.lifetime_value'), class: 'text-end' },
    { key: 'orders', label: t('analytics.orders'), class: 'text-end' },
    { key: 'last_order', label: t('analytics.last_order') },
    { key: 'segment', label: t('analytics.segment') },
];
const inventoryHeaders = [
    { key: 'name', label: t('analytics.product') },
    { key: 'stock', label: t('analytics.in_stock'), class: 'text-end' },
    { key: 'daily', label: t('analytics.daily_demand'), class: 'text-end' },
    { key: 'days_left', label: t('analytics.days_left'), class: 'text-end' },
    { key: 'stockout', label: t('analytics.stockout_date') },
    { key: 'reorder', label: t('analytics.suggested_reorder'), class: 'text-end' },
];
const branchHeaders = [
    { key: 'rank', label: '#' },
    { key: 'branch', label: t('analytics.branch') },
    { key: 'revenue', label: t('analytics.revenue'), class: 'text-end' },
    { key: 'profit', label: t('analytics.profit'), class: 'text-end' },
    { key: 'outstanding', label: t('analytics.outstanding'), class: 'text-end' },
];

const segmentColor = (s) => ({
    loyal: 'success', active: 'info', at_risk: 'warning', churned: 'error',
}[s] ?? 'neutral');

function refresh() {
    router.post(route('analytics.refresh'), {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('nav.analytics')">
        <Head :title="t('nav.analytics')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-highlighted">{{ t('nav.analytics') }}</h1>
                    <p class="text-sm text-muted">{{ t('analytics.subtitle') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <UButton :label="currency" icon="i-heroicons-currency-dollar" color="neutral" variant="outline" size="sm" @click="toggle" />
                    <UButton icon="i-heroicons-arrow-path" color="neutral" variant="ghost" size="sm" @click="refresh" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <StatCard :title="t('analytics.next_month')" :value="fmt(nextMonth)" icon="i-heroicons-presentation-chart-line" icon-class="text-primary" :hint="t('analytics.projected_revenue')" />
                <StatCard
                    :title="t('analytics.trend_growth')"
                    :value="forecast.growth.toFixed(1) + '%'"
                    :icon="forecast.growth >= 0 ? 'i-heroicons-arrow-trending-up' : 'i-heroicons-arrow-trending-down'"
                    :icon-class="forecast.growth >= 0 ? 'text-success' : 'text-error'"
                    :hint="t('analytics.per_month')"
                />
                <StatCard :title="t('analytics.reorder_alerts')" :value="inventory.length" icon="i-heroicons-exclamation-triangle" icon-class="text-warning" :hint="t('analytics.products_at_risk')" />
            </div>

            <UTabs v-model="tab" :items="tabItems" value-key="value" />

            <div v-if="tab === 'forecast'">
                <UCard>
                    <template #header>
                        <div>
                            <div class="font-medium">{{ t('analytics.sales_forecast') }}</div>
                            <div class="text-sm text-muted">{{ t('analytics.forecast_hint') }}</div>
                        </div>
                    </template>
                    <apexchart type="line" height="340" :options="forecastOptions" :series="forecastSeries" />
                </UCard>
            </div>

            <div v-else-if="tab === 'products'" class="grid gap-4 lg:grid-cols-2">
                <UCard>
                    <template #header><span class="font-medium">{{ t('analytics.best_sellers') }}</span></template>
                    <DataTable :headers="bestSellerHeaders" :rows="products.best" row-key="name">
                        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
                        <template #cell-qty="{ value }"><span class="tabular-nums">{{ value.toLocaleString() }}</span></template>
                        <template #cell-revenue="{ row }"><span class="tabular-nums">{{ fmt(row.revenue) }}</span></template>
                    </DataTable>
                </UCard>
                <UCard>
                    <template #header>
                        <div>
                            <div class="font-medium">{{ t('analytics.slow_movers') }}</div>
                            <div class="text-sm text-muted">{{ t('analytics.slow_hint') }}</div>
                        </div>
                    </template>
                    <DataTable :headers="slowMoverHeaders" :rows="products.slow" row-key="name">
                        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
                        <template #cell-stock="{ value }"><span class="tabular-nums">{{ value.toLocaleString() }}</span></template>
                        <template #cell-sold="{ value }"><span class="tabular-nums" :class="value === 0 ? 'text-error' : ''">{{ value.toLocaleString() }}</span></template>
                    </DataTable>
                </UCard>
            </div>

            <div v-else-if="tab === 'customers'">
                <UCard>
                    <template #header>
                        <div>
                            <div class="font-medium">{{ t('analytics.customer_value') }}</div>
                            <div class="text-sm text-muted">{{ t('analytics.customer_hint') }}</div>
                        </div>
                    </template>
                    <DataTable :headers="customerHeaders" :rows="customers" row-key="name">
                        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
                        <template #cell-clv="{ row }"><span class="tabular-nums text-success">{{ fmt(row.clv) }}</span></template>
                        <template #cell-orders="{ value }"><span class="tabular-nums">{{ value }}</span></template>
                        <template #cell-last_order="{ row }">
                            <span class="text-sm text-muted">{{ row.last_order }}
                                <span class="text-xs">({{ t('analytics.days_ago', { count: row.days_since }) }})</span>
                            </span>
                        </template>
                        <template #cell-segment="{ row }">
                            <UBadge :color="segmentColor(row.segment)" variant="subtle" size="sm" :label="t('analytics.segment_' + row.segment)" />
                        </template>
                    </DataTable>
                </UCard>
            </div>

            <div v-else-if="tab === 'inventory'">
                <UCard>
                    <template #header>
                        <div>
                            <div class="font-medium">{{ t('analytics.reorder_suggestions') }}</div>
                            <div class="text-sm text-muted">{{ t('analytics.reorder_hint') }}</div>
                        </div>
                    </template>
                    <DataTable :headers="inventoryHeaders" :rows="inventory" row-key="name">
                        <template #empty>{{ t('analytics.no_reorder_needed') }}</template>
                        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
                        <template #cell-stock="{ value }"><span class="tabular-nums">{{ value.toLocaleString() }}</span></template>
                        <template #cell-daily="{ value }"><span class="tabular-nums">{{ Number(value).toFixed(2) }}</span></template>
                        <template #cell-days_left="{ value }"><span class="tabular-nums" :class="value <= 14 ? 'font-semibold text-error' : 'text-warning'">{{ value }}</span></template>
                        <template #cell-stockout="{ value }"><span class="text-sm text-muted">{{ value }}</span></template>
                        <template #cell-reorder="{ value }"><span class="font-semibold tabular-nums text-primary">{{ value.toLocaleString() }}</span></template>
                    </DataTable>
                </UCard>
            </div>

            <div v-else-if="tab === 'branches'" class="grid gap-4 lg:grid-cols-3">
                <UCard class="lg:col-span-1">
                    <template #header><span class="font-medium">{{ t('analytics.profit_ranking') }}</span></template>
                    <apexchart type="bar" height="340" :options="rankingOptions" :series="rankingSeries" />
                </UCard>
                <UCard class="lg:col-span-2">
                    <template #header><span class="font-medium">{{ t('analytics.branch_scorecard') }}</span></template>
                    <DataTable :headers="branchHeaders" :rows="ranking" row-key="branch">
                        <template #cell-rank="{ row }">
                            <UBadge :color="row.rank === 1 ? 'success' : 'neutral'" variant="subtle" size="sm" :label="String(row.rank)" />
                        </template>
                        <template #cell-branch="{ row }"><span class="font-medium">{{ row.branch }}</span></template>
                        <template #cell-revenue="{ row }"><span class="tabular-nums">{{ fmt(row.revenue) }}</span></template>
                        <template #cell-profit="{ row }"><span class="font-semibold tabular-nums" :class="row.profit >= 0 ? 'text-success' : 'text-error'">{{ fmt(row.profit) }}</span></template>
                        <template #cell-outstanding="{ row }"><span class="tabular-nums text-warning">{{ fmt(row.outstanding) }}</span></template>
                    </DataTable>
                </UCard>
            </div>
        </div>
    </AppLayout>
</template>
