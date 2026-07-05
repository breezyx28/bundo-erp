<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import StatCard from '@/components/StatCard.vue';
import { useTrans } from '@/composables/useTrans';
import { useMoney } from '@/composables/useMoney';
import { chartBase } from '@/composables/useChartBase';

const props = defineProps({
    kpis: { type: Object, required: true },
    rate: { type: Number, default: 0 },
    currencies: { type: Object, default: () => ({}) },
});

const { t } = useTrans();
const { currency, toggle, conv, fmt } = useMoney(props.currencies, props.rate);

const now = new Date().toLocaleDateString(undefined, {
    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
});

const moneyAxis = (val) => `${currency.value === 'USD' ? '$' : 'SDG'} ${Number(val).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;

const trendSeries = computed(() => [
    { name: t('dashboard.revenue'), data: (props.kpis.trend?.revenue ?? []).map(conv) },
    { name: t('dashboard.expenses'), data: (props.kpis.trend?.expenses ?? []).map(conv) },
]);
const trendOptions = computed(() => {
    const base = chartBase();
    return {
        ...base,
        chart: { ...base.chart, type: 'area', height: 280 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.04 } },
        xaxis: { categories: props.kpis.trend?.labels ?? [], labels: { style: { fontSize: '11px' } } },
        yaxis: { labels: { formatter: moneyAxis, style: { fontSize: '11px' } } },
    };
});

const agingSeries = computed(() => {
    const a = props.kpis.aging ?? {};
    return [conv(a.current ?? 0), conv(a.d30 ?? 0), conv(a.d60 ?? 0), conv(a.d90 ?? 0)];
});
const agingOptions = computed(() => {
    const base = chartBase();
    return {
        ...base,
        chart: { ...base.chart, type: 'donut', height: 280 },
        labels: [t('debts.bucket.current'), t('debts.bucket.d30'), t('debts.bucket.d60'), t('debts.bucket.d90')],
        plotOptions: { pie: { donut: { size: '68%' } } },
    };
});

const productsSeries = computed(() => [
    { name: t('dashboard.qty_sold'), data: (props.kpis.top_products ?? []).map((p) => p.value) },
]);
const productsOptions = computed(() => {
    const base = chartBase();
    return {
        ...base,
        chart: { ...base.chart, type: 'bar', height: 280 },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '58%' } },
        xaxis: { categories: (props.kpis.top_products ?? []).map((p) => p.name), labels: { style: { fontSize: '11px' } } },
    };
});

const brandsSeries = computed(() => (props.kpis.top_brands ?? []).map((b) => conv(b.value)));
const brandsOptions = computed(() => {
    const base = chartBase();
    return {
        ...base,
        chart: { ...base.chart, type: 'donut', height: 280 },
        labels: (props.kpis.top_brands ?? []).map((b) => b.name),
        plotOptions: { pie: { donut: { size: '68%' } } },
    };
});

function refresh() {
    router.post(route('dashboard.refresh'), {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('nav.dashboard')">
        <Head :title="t('nav.dashboard')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-highlighted">{{ t('nav.dashboard') }}</h1>
                    <p class="text-sm text-muted">{{ now }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <UButton :label="currency" icon="i-heroicons-currency-dollar" color="neutral" variant="soft" size="sm" @click="toggle" />
                    <UButton icon="i-heroicons-arrow-path" color="neutral" variant="ghost" size="sm" @click="refresh" />
                </div>
            </div>

            <div class="responsive-stat-grid">
                <StatCard :title="t('dashboard.revenue_month')" :value="fmt(kpis.revenue.month)" icon="i-heroicons-arrow-trending-up" icon-class="text-success" :hint="t('dashboard.year') + ': ' + fmt(kpis.revenue.year)" />
                <StatCard :title="t('dashboard.expenses_month')" :value="fmt(kpis.expenses.month)" icon="i-heroicons-arrow-trending-down" icon-class="text-error" :hint="t('dashboard.year') + ': ' + fmt(kpis.expenses.year)" />
                <StatCard :title="t('dashboard.profit_month')" :value="fmt(kpis.profit.month)" icon="i-heroicons-banknotes" icon-class="text-primary" :hint="t('dashboard.year') + ': ' + fmt(kpis.profit.year)" />
                <StatCard :title="t('dashboard.outstanding')" :value="fmt(kpis.outstanding)" icon="i-heroicons-credit-card" icon-class="text-warning" :hint="t('dashboard.low_stock') + ': ' + kpis.low_stock" />
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <UCard class="lg:col-span-2">
                    <template #header><span class="font-medium">{{ t('dashboard.trend') }}</span></template>
                    <apexchart type="area" height="280" :options="trendOptions" :series="trendSeries" />
                </UCard>
                <UCard>
                    <template #header><span class="font-medium">{{ t('dashboard.aging') }}</span></template>
                    <apexchart type="donut" height="280" :options="agingOptions" :series="agingSeries" />
                </UCard>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <UCard>
                    <template #header><span class="font-medium">{{ t('dashboard.top_products') }}</span></template>
                    <apexchart type="bar" height="280" :options="productsOptions" :series="productsSeries" />
                </UCard>
                <UCard>
                    <template #header><span class="font-medium">{{ t('dashboard.top_brands') }}</span></template>
                    <apexchart type="donut" height="280" :options="brandsOptions" :series="brandsSeries" />
                </UCard>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <UCard class="lg:col-span-2">
                    <template #header><span class="font-medium">{{ t('dashboard.recent') }}</span></template>
                    <div class="divide-y divide-default">
                        <div v-for="(tr, index) in kpis.recent" :key="index" class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-2">
                                <UBadge :color="tr.type === 'sale' ? 'success' : 'info'" variant="subtle" size="sm" :label="t('dashboard.' + tr.type)" />
                                <span class="font-medium">{{ tr.number }}</span>
                                <span class="text-sm text-muted">{{ tr.party }}</span>
                            </div>
                            <div class="text-end">
                                <div class="font-medium tabular-nums">{{ fmt(tr.amount) }}</div>
                                <div class="text-xs text-dimmed">{{ tr.date }}</div>
                            </div>
                        </div>
                        <div v-if="!kpis.recent.length" class="py-4 text-center text-muted">{{ t('common.no_results') }}</div>
                    </div>
                </UCard>
                <UCard>
                    <template #header><span class="font-medium">{{ t('dashboard.shipping') }}</span></template>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg border border-default p-4">
                            <div class="text-sm text-muted">{{ t('shipping.status.pending') }}</div>
                            <div class="mt-1 text-2xl font-semibold text-warning tabular-nums">{{ kpis.shipping.pending }}</div>
                        </div>
                        <div class="rounded-lg border border-default p-4">
                            <div class="text-sm text-muted">{{ t('shipping.status.delivered') }}</div>
                            <div class="mt-1 text-2xl font-semibold text-success tabular-nums">{{ kpis.shipping.delivered }}</div>
                        </div>
                    </div>
                    <UButton :label="t('dashboard.view_reports')" color="neutral" variant="soft" block class="mt-4" :to="route('reports.index')" as="a" />
                </UCard>
            </div>
        </div>
    </AppLayout>
</template>
