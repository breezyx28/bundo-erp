<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import StatCard from '@/components/StatCard.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useMoney } from '@/composables/useMoney';
import { useTableColumns } from '@/composables/useTableColumns';
import { chartBase } from '@/composables/useChartBase';
import { numericHeader, textHeader } from '@/utils/tableHeaders';

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
    textHeader('name', t('analytics.product')),
    numericHeader('qty', t('analytics.qty')),
    numericHeader('revenue', t('analytics.revenue')),
];
const slowMoverHeaders = [
    textHeader('name', t('analytics.product')),
    numericHeader('stock', t('analytics.in_stock')),
    numericHeader('sold', t('analytics.sold_90d')),
];
const customerHeaders = [
    textHeader('name', t('analytics.customer')),
    numericHeader('clv', t('analytics.lifetime_value')),
    numericHeader('orders', t('analytics.orders')),
    textHeader('last_order', t('analytics.last_order')),
    textHeader('segment', t('analytics.segment')),
];
const inventoryHeaders = [
    textHeader('name', t('analytics.product')),
    numericHeader('stock', t('analytics.in_stock')),
    numericHeader('daily', t('analytics.daily_demand')),
    numericHeader('days_left', t('analytics.days_left')),
    textHeader('stockout', t('analytics.stockout_date')),
    numericHeader('reorder', t('analytics.suggested_reorder')),
];
const branchHeaders = [
    textHeader('rank', '#'),
    textHeader('branch', t('analytics.branch')),
    numericHeader('revenue', t('analytics.revenue')),
    numericHeader('profit', t('analytics.profit')),
    numericHeader('outstanding', t('analytics.outstanding')),
];

function clientTable(storageKey, headers) {
    const { visibleHeaders, columnOptions, toggle } = useTableColumns(storageKey, headers);
    const printOpen = ref(false);
    const tableFilters = ref({ search: '' });

    return { visibleHeaders, columnOptions, toggle, printOpen, tableFilters };
}

const bestSellers = clientTable('analytics.best_sellers', bestSellerHeaders);
const slowMovers = clientTable('analytics.slow_movers', slowMoverHeaders);
const customersTable = clientTable('analytics.customers', customerHeaders);
const inventoryTable = clientTable('analytics.inventory', inventoryHeaders);
const branchesTable = clientTable('analytics.branches', branchHeaders);

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
                    <DataTable :headers="bestSellers.visibleHeaders" :rows="products.best" row-key="name">
                        <template #toolbar>
                            <TableToolbar
                                :filters="bestSellers.tableFilters"
                                :column-options="bestSellers.columnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="bestSellers.toggle"
                                @print="bestSellers.printOpen = true"
                            />
                        </template>
                        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
                        <template #cell-qty="{ value }">{{ value.toLocaleString() }}</template>
                        <template #cell-revenue="{ row }">{{ fmt(row.revenue) }}</template>
                    </DataTable>
                </UCard>
                <UCard>
                    <template #header>
                        <div>
                            <div class="font-medium">{{ t('analytics.slow_movers') }}</div>
                            <div class="text-sm text-muted">{{ t('analytics.slow_hint') }}</div>
                        </div>
                    </template>
                    <DataTable :headers="slowMovers.visibleHeaders" :rows="products.slow" row-key="name">
                        <template #toolbar>
                            <TableToolbar
                                :filters="slowMovers.tableFilters"
                                :column-options="slowMovers.columnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="slowMovers.toggle"
                                @print="slowMovers.printOpen = true"
                            />
                        </template>
                        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
                        <template #cell-stock="{ value }">{{ value.toLocaleString() }}</template>
                        <template #cell-sold="{ value }"><span :class="value === 0 ? 'text-error' : ''">{{ value.toLocaleString() }}</span></template>
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
                    <DataTable :headers="customersTable.visibleHeaders" :rows="customers" row-key="name">
                        <template #toolbar>
                            <TableToolbar
                                :filters="customersTable.tableFilters"
                                :column-options="customersTable.columnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="customersTable.toggle"
                                @print="customersTable.printOpen = true"
                            />
                        </template>
                        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
                        <template #cell-clv="{ row }"><span class="text-success">{{ fmt(row.clv) }}</span></template>
                        <template #cell-orders="{ value }">{{ value }}</template>
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
                    <DataTable :headers="inventoryTable.visibleHeaders" :rows="inventory" row-key="name">
                        <template #toolbar>
                            <TableToolbar
                                :filters="inventoryTable.tableFilters"
                                :column-options="inventoryTable.columnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="inventoryTable.toggle"
                                @print="inventoryTable.printOpen = true"
                            />
                        </template>
                        <template #empty>{{ t('analytics.no_reorder_needed') }}</template>
                        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
                        <template #cell-stock="{ value }">{{ value.toLocaleString() }}</template>
                        <template #cell-daily="{ value }">{{ Number(value).toFixed(2) }}</template>
                        <template #cell-days_left="{ value }"><span :class="value <= 14 ? 'font-semibold text-error' : 'text-warning'">{{ value }}</span></template>
                        <template #cell-stockout="{ value }"><span class="text-sm text-muted">{{ value }}</span></template>
                        <template #cell-reorder="{ value }"><span class="font-semibold text-primary">{{ value.toLocaleString() }}</span></template>
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
                    <DataTable :headers="branchesTable.visibleHeaders" :rows="ranking" row-key="branch">
                        <template #toolbar>
                            <TableToolbar
                                :filters="branchesTable.tableFilters"
                                :column-options="branchesTable.columnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="branchesTable.toggle"
                                @print="branchesTable.printOpen = true"
                            />
                        </template>
                        <template #cell-rank="{ row }">
                            <UBadge :color="row.rank === 1 ? 'success' : 'neutral'" variant="subtle" size="sm" :label="String(row.rank)" />
                        </template>
                        <template #cell-branch="{ row }"><span class="font-medium">{{ row.branch }}</span></template>
                        <template #cell-revenue="{ row }">{{ fmt(row.revenue) }}</template>
                        <template #cell-profit="{ row }"><span class="font-semibold" :class="row.profit >= 0 ? 'text-success' : 'text-error'">{{ fmt(row.profit) }}</span></template>
                        <template #cell-outstanding="{ row }"><span class="text-warning">{{ fmt(row.outstanding) }}</span></template>
                    </DataTable>
                </UCard>
            </div>
        </div>

        <TablePrintModal v-model:open="bestSellers.printOpen" :title="t('analytics.best_sellers')" :headers="bestSellers.visibleHeaders" :rows="products.best" />
        <TablePrintModal v-model:open="slowMovers.printOpen" :title="t('analytics.slow_movers')" :headers="slowMovers.visibleHeaders" :rows="products.slow" />
        <TablePrintModal v-model:open="customersTable.printOpen" :title="t('analytics.customer_value')" :headers="customersTable.visibleHeaders" :rows="customers" />
        <TablePrintModal v-model:open="inventoryTable.printOpen" :title="t('analytics.reorder_suggestions')" :headers="inventoryTable.visibleHeaders" :rows="inventory" />
        <TablePrintModal v-model:open="branchesTable.printOpen" :title="t('analytics.branch_scorecard')" :headers="branchesTable.visibleHeaders" :rows="ranking" />
    </AppLayout>
</template>
