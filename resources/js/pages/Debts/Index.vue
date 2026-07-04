<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import StatCard from '@/components/StatCard.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';
import { useTableColumns } from '@/composables/useTableColumns';
import { numericHeader } from '@/utils/tableHeaders';

const props = defineProps({
    summary: { type: Object, required: true },
    aging: { type: Object, required: true },
    payablesSummary: { type: Object, default: () => ({}) },
    payables: { type: Object, default: () => ({ data: [] }) },
    performance: { type: Object, required: true },
    methodOptions: { type: Array, default: () => [] },
    statement: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters } = useTableFilters('debts.index', {
    search: props.filters.search ?? '',
    overdue: props.filters.overdue ?? false,
    perf_from: props.filters.perf_from ?? '',
    perf_to: props.filters.perf_to ?? '',
    supplier_search: props.filters.supplier_search ?? '',
});

const agingHeaders = [
    { key: 'customer', label: t('nav.customers') },
    numericHeader('current', t('debts.bucket.current')),
    numericHeader('d30', t('debts.bucket.d30')),
    numericHeader('d60', t('debts.bucket.d60')),
    numericHeader('d90', t('debts.bucket.d90')),
    numericHeader('total', t('purchasing.total')),
];

const payableHeaders = [
    { key: 'supplier', label: t('nav.suppliers') },
    numericHeader('current', t('debts.bucket.current')),
    numericHeader('d30', t('debts.bucket.d30')),
    numericHeader('d60', t('debts.bucket.d60')),
    numericHeader('d90', t('debts.bucket.d90')),
    numericHeader('total', t('purchasing.total')),
];

const {
    visibleHeaders: agingVisibleHeaders,
    columnOptions: agingColumnOptions,
    toggle: toggleAgingColumn,
} = useTableColumns('debts.index.receivables', agingHeaders);
const {
    visibleHeaders: payableVisibleHeaders,
    columnOptions: payableColumnOptions,
    toggle: togglePayableColumn,
} = useTableColumns('debts.index.payables', payableHeaders);

const agingPrintOpen = ref(false);
const payablesPrintOpen = ref(false);
const agingPrintRows = computed(() => props.aging?.data ?? props.aging ?? []);
const payablesPrintRows = computed(() => props.payables?.data ?? []);

const tabItems = [
    { value: 'receivables', label: t('debts.receivables'), icon: 'i-heroicons-arrow-down-left' },
    { value: 'payables', label: t('debts.payables'), icon: 'i-heroicons-arrow-up-right' },
];
const activeTab = ref('receivables');

const methodItems = computed(() => props.methodOptions);

// Collect
const today = new Date().toISOString().slice(0, 10);
const collectOpen = ref(false);
const collectCustomerId = ref(null);
const collectCustomerName = ref('');
const collectForm = useForm({
    collect_amount: 0,
    collect_method: 'cash',
    collect_date: today,
    collect_reference: '',
});
function openCollect(row) {
    collectCustomerId.value = row.customer_id;
    collectCustomerName.value = row.customer;
    collectForm.reset();
    collectForm.clearErrors();
    collectForm.collect_amount = row.total_raw;
    collectForm.collect_date = today;
    collectOpen.value = true;
}
function submitCollect() {
    collectForm.post(route('debts.collect', collectCustomerId.value), {
        preserveScroll: true,
        onSuccess: () => {
            collectOpen.value = false;
        },
    });
}

// Statement
const statementOpen = ref(false);
function openStatement(row) {
    router.reload({
        only: ['statement'],
        data: { statement: row.customer_id },
        onSuccess: () => {
            if (props.statement) {
                statementOpen.value = true;
            }
        },
    });
}
function remind(invoiceId) {
    router.post(route('debts.remind', invoiceId), {}, {
        preserveScroll: true,
        only: ['statement', 'flash'],
        data: { statement: props.statement?.customer_id },
    });
}
</script>

<template>
    <AppLayout :title="t('nav.debts')">
        <Head :title="t('nav.debts')" />

        <div class="space-y-6">
            <h1 class="text-xl font-semibold text-highlighted">{{ t('nav.debts') }}</h1>

            <UTabs v-model="activeTab" :items="tabItems" :content="false" />

            <div v-show="activeTab === 'receivables'" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <StatCard :title="t('debts.outstanding')" :value="summary.total" icon="i-heroicons-banknotes" icon-class="text-primary" />
                    <StatCard :title="t('debts.bucket.current')" :value="summary.current" icon="i-heroicons-check-circle" icon-class="text-success" />
                    <StatCard :title="t('debts.bucket.d30')" :value="summary.d30" icon="i-heroicons-clock" icon-class="text-warning" />
                    <StatCard :title="t('debts.bucket.d60')" :value="summary.d60" icon="i-heroicons-exclamation-triangle" icon-class="text-warning" />
                    <StatCard :title="t('debts.bucket.d90')" :value="summary.d90" icon="i-heroicons-fire" icon-class="text-error" />
                </div>

                <UCard>
                    <template #header>
                        <span class="font-medium">{{ t('debts.aging') }}</span>
                    </template>
                    <DataTable :headers="agingVisibleHeaders" :rows="aging" :query="filters" actions row-key="customer_id">
                        <template #toolbar>
                            <TableToolbar
                                :filters="filters"
                                :column-options="agingColumnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="toggleAgingColumn"
                                @print="agingPrintOpen = true"
                            >
                                <template #filters>
                                    <UInput
                                        v-model="filters.search"
                                        icon="i-heroicons-magnifying-glass"
                                        :placeholder="t('common.search')"
                                        class="w-full sm:max-w-xs"
                                    />
                                    <UCheckbox v-model="filters.overdue" :label="t('debts.overdue_only')" />
                                </template>
                            </TableToolbar>
                        </template>

                        <template #cell-customer="{ row }">
                            <div class="font-medium">{{ row.customer }}</div>
                            <div v-if="row.oldest_days > 0" class="text-xs text-dimmed">
                                {{ t('debts.oldest', { days: row.oldest_days }) }}
                            </div>
                        </template>
                        <template #cell-d30="{ value }"><span class="text-warning">{{ value }}</span></template>
                        <template #cell-d60="{ value }"><span class="text-warning">{{ value }}</span></template>
                        <template #cell-d90="{ value }"><span class="text-error">{{ value }}</span></template>
                        <template #cell-total="{ value }"><span class="font-semibold">{{ value }}</span></template>

                        <template #actions="{ row }">
                            <UButton icon="i-heroicons-document-text" color="neutral" variant="ghost" size="sm" @click="openStatement(row)" />
                            <UButton v-if="canManage" icon="i-heroicons-banknotes" color="primary" variant="ghost" size="sm" @click="openCollect(row)" />
                        </template>
                    </DataTable>
                </UCard>

                <UCard>
                    <template #header>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="font-medium">{{ t('debts.performance') }}</span>
                            <div class="flex items-center gap-2">
                                <UInput v-model="filters.perf_from" type="date" class="w-40" />
                                <UInput v-model="filters.perf_to" type="date" class="w-40" />
                            </div>
                        </div>
                    </template>
                    <div class="grid gap-4 lg:grid-cols-3">
                        <div class="rounded-lg border border-default p-4">
                            <div class="text-sm text-muted">{{ t('debts.collected') }}</div>
                            <div class="mt-1 text-2xl font-semibold text-success tabular-nums">{{ performance.total }}</div>
                            <div class="text-xs text-muted">{{ t('debts.payments_count', { count: performance.count }) }}</div>
                        </div>
                        <div class="lg:col-span-2">
                            <div class="mb-2 text-sm font-medium">{{ t('debts.by_branch') }}</div>
                            <div class="space-y-1">
                                <div
                                    v-for="(b, index) in performance.by_branch"
                                    :key="index"
                                    class="flex items-center justify-between rounded-lg border border-default px-3 py-2 text-sm"
                                >
                                    <span>{{ b.branch }} <span class="text-dimmed">· {{ b.count }}</span></span>
                                    <span class="font-medium tabular-nums">{{ b.total }}</span>
                                </div>
                                <div v-if="!performance.by_branch.length" class="text-sm text-muted">{{ t('common.no_results') }}</div>
                            </div>
                        </div>
                    </div>
                </UCard>
            </div>

            <div v-show="activeTab === 'payables'" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <StatCard :title="t('debts.payables_total')" :value="payablesSummary.total" icon="i-heroicons-banknotes" icon-class="text-primary" />
                    <StatCard :title="t('debts.bucket.current')" :value="payablesSummary.current" icon="i-heroicons-check-circle" icon-class="text-success" />
                    <StatCard :title="t('debts.bucket.d30')" :value="payablesSummary.d30" icon="i-heroicons-clock" icon-class="text-warning" />
                    <StatCard :title="t('debts.bucket.d60')" :value="payablesSummary.d60" icon="i-heroicons-exclamation-triangle" icon-class="text-warning" />
                    <StatCard :title="t('debts.bucket.d90')" :value="payablesSummary.d90" icon="i-heroicons-fire" icon-class="text-error" />
                </div>

                <UCard>
                    <template #header>
                        <span class="font-medium">{{ t('debts.supplier_aging') }}</span>
                    </template>
                    <DataTable :headers="payableVisibleHeaders" :rows="payables" :query="filters" row-key="supplier_id">
                        <template #toolbar>
                            <TableToolbar
                                :filters="filters"
                                :column-options="payableColumnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="togglePayableColumn"
                                @print="payablesPrintOpen = true"
                            >
                                <template #filters>
                                    <UInput
                                        v-model="filters.supplier_search"
                                        icon="i-heroicons-magnifying-glass"
                                        :placeholder="t('common.search')"
                                        class="w-full sm:max-w-xs"
                                    />
                                </template>
                            </TableToolbar>
                        </template>

                        <template #cell-supplier="{ row }">
                            <div class="font-medium">{{ row.supplier }}</div>
                            <div v-if="row.oldest_days > 0" class="text-xs text-dimmed">
                                {{ t('debts.oldest', { days: row.oldest_days }) }}
                            </div>
                        </template>
                        <template #cell-d30="{ value }"><span class="text-warning">{{ value }}</span></template>
                        <template #cell-d60="{ value }"><span class="text-warning">{{ value }}</span></template>
                        <template #cell-d90="{ value }"><span class="text-error">{{ value }}</span></template>
                        <template #cell-total="{ value }"><span class="font-semibold">{{ value }}</span></template>
                    </DataTable>
                </UCard>
            </div>
        </div>

        <FormModal
            v-model:open="collectOpen"
            :title="t('debts.collect') + ' — ' + collectCustomerName"
            width="sm:max-w-lg"
        >
            <div class="grid gap-4">
                <UFormField :label="t('purchasing.amount')" :error="collectForm.errors.collect_amount" :hint="t('debts.collect_hint')">
                    <UInput v-model="collectForm.collect_amount" type="number" step="0.01" min="0.01" class="w-full" />
                </UFormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('purchasing.method')" :error="collectForm.errors.collect_method">
                        <USelectMenu v-model="collectForm.collect_method" :items="methodItems" value-key="value" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('purchasing.payment_date')" :error="collectForm.errors.collect_date">
                        <UInput v-model="collectForm.collect_date" type="date" class="w-full" />
                    </UFormField>
                </div>
                <UFormField :label="t('purchasing.reference')" :error="collectForm.errors.collect_reference">
                    <UInput v-model="collectForm.collect_reference" class="w-full" />
                </UFormField>
            </div>
            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('debts.collect')" :loading="collectForm.processing" @click="submitCollect()" />
            </template>
        </FormModal>

        <USlideover v-model:open="statementOpen" :title="statement?.customer" :description="t('debts.statement')">
            <template #body>
                <div v-if="statement" class="space-y-2">
                    <div
                        v-for="inv in statement.invoices"
                        :key="inv.id"
                        class="rounded-lg border border-default p-3"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <a :href="inv.print_url" target="_blank" class="font-medium text-primary hover:underline">{{ inv.invoice_number }}</a>
                                <div class="text-xs text-muted">
                                    {{ t('sales.due_date') }}: {{ inv.due_date ?? '—' }}
                                    <span v-if="inv.days_overdue > 0"> · <span class="text-error">{{ t('debts.days_overdue', { days: inv.days_overdue }) }}</span></span>
                                </div>
                            </div>
                            <span class="font-semibold tabular-nums text-error">{{ inv.balance }}</span>
                        </div>
                        <div v-if="canManage" class="mt-2 flex items-center gap-2">
                            <UButton v-if="inv.whatsapp_url" :label="t('debts.whatsapp')" icon="i-heroicons-chat-bubble-left-right" color="success" variant="ghost" size="xs" :to="inv.whatsapp_url" as="a" target="_blank" />
                            <UButton :label="t('debts.log_reminder')" icon="i-heroicons-bell" color="neutral" variant="ghost" size="xs" @click="remind(inv.id)" />
                            <span v-if="inv.last_reminder" class="self-center text-xs text-dimmed">{{ t('debts.reminded_at', { time: inv.last_reminder }) }}</span>
                        </div>
                    </div>
                    <div v-if="!statement.invoices.length" class="py-6 text-center text-muted">{{ t('common.no_results') }}</div>
                </div>
            </template>
        </USlideover>

        <TablePrintModal
            v-model:open="agingPrintOpen"
            :title="t('debts.aging')"
            :headers="agingVisibleHeaders"
            :rows="agingPrintRows"
        />
        <TablePrintModal
            v-model:open="payablesPrintOpen"
            :title="t('debts.supplier_aging')"
            :headers="payableVisibleHeaders"
            :rows="payablesPrintRows"
        />
    </AppLayout>
</template>
