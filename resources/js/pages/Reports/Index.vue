<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';
import { useTableColumns } from '@/composables/useTableColumns';
import { useMoney } from '@/composables/useMoney';

const props = defineProps({
    type: { type: String, default: 'pnl' },
    pnl: { type: Object, default: null },
    cashflow: { type: Object, default: null },
    branches: { type: Array, default: null },
    typeOptions: { type: Array, default: () => [] },
    rate: { type: Number, default: 0 },
    currencies: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const { t } = useTrans();
const { currency, toggle, fmt } = useMoney(props.currencies, props.rate);
const { filters } = useTableFilters('reports.index', {
    type: props.filters.type ?? 'pnl',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const printOpen = ref(false);
const typeItems = computed(() => props.typeOptions);

const activeType = computed(() => {
    const value = filters.type;

    if (typeof value === 'string' && value) {
        return value;
    }

    if (value && typeof value === 'object' && 'value' in value) {
        return value.value;
    }

    return props.type || 'pnl';
});

const branchHeaders = [
    { key: 'branch', label: t('reports.branch') },
    { key: 'revenue', label: t('reports.revenue'), align: 'end' },
    { key: 'cogs', label: t('reports.cogs'), align: 'end' },
    { key: 'expenses', label: t('reports.expenses'), align: 'end' },
    { key: 'profit', label: t('reports.net_profit'), align: 'end' },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('reports.index.branches', branchHeaders);

const branchRows = computed(() => ({
    data: props.branches ?? [],
}));

const printRows = computed(() =>
    (props.branches ?? []).map((row) => ({
        ...row,
        revenue: fmt(row.revenue),
        cogs: fmt(row.cogs),
        expenses: fmt(row.expenses),
        profit: fmt(row.profit),
    })),
);

const exportUrl = (format) =>
    route('reports.export', {
        format,
        type: activeType.value,
        from: filters.from,
        to: filters.to,
        currency: currency.value,
    });

const hasReportContent = computed(() =>
    props.pnl !== null || props.cashflow !== null || props.branches !== null,
);
</script>

<template>
    <AppLayout :title="t('nav.reports')">
        <Head :title="t('nav.reports')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">{{ t('nav.reports') }}</h1>
                <div class="flex items-center gap-2">
                    <UButton :label="currency" icon="i-heroicons-currency-dollar" color="neutral" variant="soft" size="sm" @click="toggle" />
                    <UButton label="CSV" icon="i-heroicons-table-cells" color="neutral" variant="ghost" size="sm" :to="exportUrl('csv')" as="a" target="_blank" />
                    <UButton label="PDF" icon="i-heroicons-document-arrow-down" color="neutral" variant="ghost" size="sm" :to="exportUrl('pdf')" as="a" target="_blank" />
                </div>
            </div>

            <UCard>
                <template #header>
                    <TableToolbar
                        :filters="filters"
                        :column-options="activeType === 'branches' ? columnOptions : []"
                        :date-range="false"
                        :search="false"
                        @toggle-column="toggleColumn"
                        @print="printOpen = true"
                    >
                        <template #filters>
                            <USelectMenu v-model="filters.type" :items="typeItems" value-key="value" class="w-full sm:w-56" />
                            <UInput v-model="filters.from" type="date" class="w-full sm:w-40" />
                            <UInput v-model="filters.to" type="date" class="w-full sm:w-40" />
                        </template>
                    </TableToolbar>
                </template>

                <div class="mb-4 text-sm text-muted">{{ filters.from }} → {{ filters.to }}</div>

                <div v-if="!hasReportContent" class="py-10 text-center text-sm text-muted">
                    {{ t('reports.no_data') }}
                </div>

                <div v-else-if="pnl" class="mx-auto max-w-xl space-y-2">
                    <div class="flex justify-between border-b border-muted py-2"><span>{{ t('reports.revenue') }}</span><span class="font-medium tabular-nums text-success">{{ fmt(pnl.revenue) }}</span></div>
                    <div class="flex justify-between border-b border-muted py-2"><span>{{ t('reports.cogs') }}</span><span class="tabular-nums">- {{ fmt(pnl.cogs) }}</span></div>
                    <div class="flex justify-between border-b border-default py-2 font-semibold"><span>{{ t('reports.gross_profit') }}</span><span class="tabular-nums">{{ fmt(pnl.gross_profit) }}</span></div>
                    <div class="flex justify-between border-b border-muted py-2"><span>{{ t('reports.expenses') }}</span><span class="tabular-nums text-error">- {{ fmt(pnl.expenses) }}</span></div>
                    <div class="flex justify-between border-t-2 border-inverted/70 py-2 text-lg font-bold"><span>{{ t('reports.net_profit') }}</span><span class="tabular-nums" :class="pnl.net_profit >= 0 ? 'text-success' : 'text-error'">{{ fmt(pnl.net_profit) }}</span></div>

                    <div v-if="pnl.expense_breakdown && pnl.expense_breakdown.length" class="mt-6">
                        <div class="mb-2 text-sm font-medium">{{ t('reports.expense_breakdown') }}</div>
                        <div v-for="(row, index) in pnl.expense_breakdown" :key="index" class="flex justify-between py-1 text-sm">
                            <span>{{ row.category }}</span><span class="tabular-nums">{{ fmt(row.total) }}</span>
                        </div>
                    </div>
                </div>

                <div v-else-if="cashflow" class="mx-auto max-w-xl space-y-2">
                    <div class="flex justify-between border-b border-muted py-2"><span>{{ t('reports.cash_in') }}</span><span class="tabular-nums text-success">{{ fmt(cashflow.cash_in) }}</span></div>
                    <div class="flex justify-between border-b border-muted py-2"><span>{{ t('reports.cash_out_payments') }}</span><span class="tabular-nums text-error">- {{ fmt(cashflow.cash_out_payments) }}</span></div>
                    <div class="flex justify-between border-b border-muted py-2"><span>{{ t('reports.cash_out_expenses') }}</span><span class="tabular-nums text-error">- {{ fmt(cashflow.cash_out_expenses) }}</span></div>
                    <div class="flex justify-between border-t-2 border-inverted/70 py-2 text-lg font-bold"><span>{{ t('reports.net_cash') }}</span><span class="tabular-nums" :class="cashflow.net >= 0 ? 'text-success' : 'text-error'">{{ fmt(cashflow.net) }}</span></div>
                </div>

                <DataTable
                    v-else-if="branches !== null"
                    :headers="visibleHeaders"
                    :rows="branchRows"
                    row-key="branch"
                >
                    <template #cell-branch="{ row }"><span class="font-medium">{{ row.branch }}</span></template>
                    <template #cell-revenue="{ row }"><span class="text-success">{{ fmt(row.revenue) }}</span></template>
                    <template #cell-cogs="{ row }">{{ fmt(row.cogs) }}</template>
                    <template #cell-expenses="{ row }"><span class="text-error">{{ fmt(row.expenses) }}</span></template>
                    <template #cell-profit="{ row }"><span class="font-semibold" :class="row.profit >= 0 ? 'text-success' : 'text-error'">{{ fmt(row.profit) }}</span></template>
                </DataTable>
            </UCard>
        </div>

        <TablePrintModal
            v-if="branches !== null"
            v-model:open="printOpen"
            :title="t('reports.branch_comparison')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
