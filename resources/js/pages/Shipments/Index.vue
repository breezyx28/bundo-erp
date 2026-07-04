<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';
import { useTableColumns } from '@/composables/useTableColumns';

const props = defineProps({
    shipments: { type: Object, required: true },
    companyOptions: { type: Array, default: () => [] },
    invoiceOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    modeOptions: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
    report: { type: Object, required: true },
    deliveredStatus: { type: String, default: 'delivered' },
    detail: { type: Object, default: null },
    returnOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters, toggleSort } = useTableFilters('shipments.index', {
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
});

const headers = [
    { key: 'tracking_number', label: t('shipping.tracking'), sortable: true },
    { key: 'customer', label: t('nav.customers') },
    { key: 'route', label: t('shipping.route') },
    { key: 'company', label: t('shipping.company') },
    { key: 'status', label: t('common.status'), sortable: true },
    { key: 'shipping_cost', label: t('shipping.shipping_cost'), align: 'end', sortable: true },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('shipments.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.shipments.data ?? []).map((row) => ({
        ...row,
        tracking_number: row.tracking_number ?? '—',
        customer: row.customer ?? '—',
        route: `${row.dispatch_city} → ${row.delivery_city}`,
        company: row.company ?? '—',
        status: t('shipping.status.' + row.status),
    })),
);

const statusItems = computed(() => [{ label: t('common.all'), value: '' }, ...props.statusOptions]);
const companyItems = computed(() => props.companyOptions.map((c) => ({ label: c.name, value: c.id })));
const invoiceItems = computed(() => props.invoiceOptions.map((i) => ({ label: i.name, value: i.id })));
const returnItems = computed(() => props.returnOptions.map((p) => ({ label: p.name, value: p.id })));

const statusColor = (s) => ({
    delivered: 'success',
    returned: 'error',
    in_transit: 'info',
    handed_to_logistics: 'info',
    arrived: 'primary',
    processing: 'warning',
}[s] ?? 'neutral');

const returnStatusColor = (s) => (s === 'processed' ? 'success' : s === 'rejected' ? 'error' : 'warning');

// Create
const formOpen = ref(false);
const form = useForm({
    sales_invoice_id: null,
    logistics_company_id: null,
    dispatch_city: '',
    delivery_city: '',
    number_of_boxes: 1,
    shipping_cost: 0,
    cost_mode: 'per_invoice',
    tracking_number: '',
    waybill_number: '',
    notes: '',
});
function openCreate() {
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}
function submit() {
    form.post(route('shipments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    });
}

// Advance / deliver
const deliverOpen = ref(false);
const deliverId = ref(null);
const deliverForm = useForm({ pod: null });
function advance(row) {
    if (row.next_status === props.deliveredStatus) {
        deliverId.value = row.id;
        deliverForm.reset();
        deliverForm.clearErrors();
        deliverOpen.value = true;
        return;
    }
    router.post(route('shipments.advance', row.id), {}, { preserveScroll: true });
}
function onPodChange(event) {
    deliverForm.pod = event.target.files[0] ?? null;
}
function submitDeliver() {
    deliverForm.post(route('shipments.deliver', deliverId.value), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            deliverOpen.value = false;
        },
    });
}

// Return
const returnOpen = ref(false);
const returnShipmentId = ref(null);
const returnForm = useForm({
    return_product_id: null,
    return_quantity: 1,
    return_reason: '',
});
function openReturn(row) {
    returnShipmentId.value = row.id;
    returnForm.reset();
    returnForm.clearErrors();
    router.reload({
        only: ['returnOptions'],
        data: { return_shipment: row.id },
        onSuccess: () => {
            returnOpen.value = true;
        },
    });
}
function submitReturn() {
    returnForm.post(route('shipments.return', returnShipmentId.value), {
        preserveScroll: true,
        onSuccess: () => {
            returnOpen.value = false;
        },
    });
}

// Detail
const detailOpen = ref(false);
function openDetail(id) {
    router.reload({
        only: ['detail'],
        data: { detail: id },
        onSuccess: () => {
            if (props.detail) {
                detailOpen.value = true;
            }
        },
    });
}
function refreshDetail() {
    router.reload({ only: ['detail'], data: { detail: props.detail?.id } });
}
function processReturn(id) {
    router.post(route('shipments.return.process', id), {}, {
        preserveScroll: true,
        onSuccess: refreshDetail,
    });
}
function rejectReturn(id) {
    router.post(route('shipments.return.reject', id), {}, {
        preserveScroll: true,
        onSuccess: refreshDetail,
    });
}
</script>

<template>
    <AppLayout :title="t('nav.shipping')">
        <Head :title="t('nav.shipping')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">{{ t('nav.shipping') }}</h1>
                <UButton v-if="canManage" :label="t('shipping.new')" icon="i-heroicons-plus" @click="openCreate()" />
            </div>

            <UCard>
                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="font-medium">{{ t('shipping.report') }}</span>
                        <div class="flex items-center gap-2">
                            <UInput v-model="filters.from" type="date" class="w-40" />
                            <UInput v-model="filters.to" type="date" class="w-40" />
                        </div>
                    </div>
                </template>
                <div class="grid gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-default p-4">
                        <div class="text-sm text-muted">{{ t('shipping.total_shipments') }}</div>
                        <div class="mt-1 text-2xl font-semibold tabular-nums">{{ report.total }}</div>
                    </div>
                    <div class="rounded-lg border border-default p-4">
                        <div class="text-sm text-muted">{{ t('shipping.shipping_cost') }}</div>
                        <div class="mt-1 text-2xl font-semibold text-error tabular-nums">{{ report.shipping_cost }}</div>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="mb-2 text-sm font-medium">{{ t('shipping.by_status') }}</div>
                        <div class="flex flex-wrap gap-1">
                            <UBadge
                                v-for="(count, status) in report.by_status"
                                :key="status"
                                :color="statusColor(status)"
                                variant="subtle"
                                size="sm"
                                :label="t('shipping.status.' + status) + ': ' + count"
                            />
                            <span v-if="!Object.keys(report.by_status).length" class="text-sm text-muted">{{ t('common.no_results') }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <div class="mb-1 text-sm font-medium">{{ t('shipping.top_cities') }}</div>
                        <div v-for="(c, index) in report.top_cities" :key="index" class="flex justify-between text-sm">
                            <span>{{ c.city }}</span><span class="text-muted">{{ c.count }}</span>
                        </div>
                        <span v-if="!report.top_cities.length" class="text-sm text-muted">—</span>
                    </div>
                    <div>
                        <div class="mb-1 text-sm font-medium">{{ t('shipping.top_companies') }}</div>
                        <div v-for="(c, index) in report.top_companies" :key="index" class="flex justify-between text-sm">
                            <span>{{ c.company }}</span><span class="text-muted">{{ c.count }}</span>
                        </div>
                        <span v-if="!report.top_companies.length" class="text-sm text-muted">—</span>
                    </div>
                </div>
            </UCard>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="shipments"
                    :query="filters"
                    :sort="filters.sort"
                    :direction="filters.direction"
                    actions
                    @sort="toggleSort"
                >
                    <template #toolbar>
                        <TableToolbar
                            :filters="filters"
                            :sort-options="sortOptions"
                            :column-options="columnOptions"
                            :date-range="false"
                            :search-placeholder="t('common.search')"
                            @toggle-column="toggleColumn"
                            @print="printOpen = true"
                        >
                            <template #filters>
                                <USelectMenu v-model="filters.status" :items="statusItems" value-key="value" class="w-full sm:w-48" />
                            </template>
                        </TableToolbar>
                    </template>

                    <template #cell-tracking_number="{ row }">
                        <div class="font-medium">{{ row.tracking_number ?? '—' }}</div>
                        <div class="text-xs text-dimmed">{{ row.invoice_number }}</div>
                    </template>
                    <template #cell-route="{ row }">
                        <span class="text-sm">{{ row.dispatch_city }} → {{ row.delivery_city }}</span>
                    </template>
                    <template #cell-status="{ value }">
                        <UBadge :color="statusColor(value)" variant="subtle" :label="t('shipping.status.' + value)" />
                    </template>
                    <template #cell-shipping_cost="{ value }">
                        <span class="tabular-nums">{{ value }}</span>
                    </template>

                    <template #actions="{ row }">
                        <UButton icon="i-heroicons-eye" color="neutral" variant="ghost" size="sm" @click="openDetail(row.id)" />
                        <template v-if="canManage && !row.is_final">
                            <UButton
                                v-if="row.next_status"
                                :label="t('shipping.status.' + row.next_status)"
                                icon="i-heroicons-arrow-right"
                                color="primary"
                                variant="ghost"
                                size="sm"
                                @click="advance(row)"
                            />
                            <UButton icon="i-heroicons-arrow-uturn-left" color="error" variant="ghost" size="sm" @click="openReturn(row)" />
                        </template>
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal v-model:open="formOpen" :title="t('shipping.new')" width="sm:max-w-2xl">
            <div class="grid gap-4">
                <UFormField :label="t('sales.invoice')" :error="form.errors.sales_invoice_id">
                    <USelectMenu v-model="form.sales_invoice_id" :items="invoiceItems" value-key="value" searchable class="w-full" />
                </UFormField>
                <UFormField :label="t('shipping.company')" :error="form.errors.logistics_company_id">
                    <USelectMenu v-model="form.logistics_company_id" :items="companyItems" value-key="value" searchable class="w-full" />
                </UFormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('shipping.dispatch_city')" :error="form.errors.dispatch_city">
                        <UInput v-model="form.dispatch_city" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('shipping.delivery_city')" :error="form.errors.delivery_city">
                        <UInput v-model="form.delivery_city" class="w-full" />
                    </UFormField>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <UFormField :label="t('shipping.boxes')" :error="form.errors.number_of_boxes">
                        <UInput v-model="form.number_of_boxes" type="number" min="0" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('shipping.shipping_cost')" :error="form.errors.shipping_cost">
                        <UInput v-model="form.shipping_cost" type="number" step="0.01" min="0" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('shipping.cost_mode')" :error="form.errors.cost_mode">
                        <USelectMenu v-model="form.cost_mode" :items="modeOptions" value-key="value" class="w-full" />
                    </UFormField>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('shipping.tracking')" :error="form.errors.tracking_number">
                        <UInput v-model="form.tracking_number" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('shipping.waybill')" :error="form.errors.waybill_number">
                        <UInput v-model="form.waybill_number" class="w-full" />
                    </UFormField>
                </div>
                <UFormField :label="t('fields.notes')" :error="form.errors.notes">
                    <UTextarea v-model="form.notes" :rows="2" class="w-full" />
                </UFormField>
            </div>
            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>

        <FormModal v-model:open="deliverOpen" :title="t('shipping.mark_delivered')" width="sm:max-w-lg">
            <UFormField :label="t('shipping.pod')" :error="deliverForm.errors.pod" :hint="t('shipping.pod_hint')">
                <input
                    type="file"
                    accept="image/*"
                    class="block w-full text-sm text-muted file:me-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
                    @change="onPodChange"
                />
            </UFormField>
            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('shipping.confirm_delivery')" :loading="deliverForm.processing" @click="submitDeliver()" />
            </template>
        </FormModal>

        <FormModal v-model:open="returnOpen" :title="t('shipping.return')" width="sm:max-w-lg">
            <div class="grid gap-4">
                <UFormField :label="t('nav.products')" :error="returnForm.errors.return_product_id">
                    <USelectMenu v-model="returnForm.return_product_id" :items="returnItems" value-key="value" searchable :placeholder="t('common.none')" class="w-full" />
                </UFormField>
                <UFormField :label="t('inventory.quantity')" :error="returnForm.errors.return_quantity">
                    <UInput v-model="returnForm.return_quantity" type="number" min="1" class="w-full" />
                </UFormField>
                <UFormField :label="t('shipping.reason')" :error="returnForm.errors.return_reason">
                    <UInput v-model="returnForm.return_reason" class="w-full" />
                </UFormField>
            </div>
            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('shipping.register_return')" :loading="returnForm.processing" @click="submitReturn()" />
            </template>
        </FormModal>

        <USlideover v-model:open="detailOpen" :title="detail?.tracking_number ?? t('nav.shipping')" :description="detail?.invoice_number">
            <template #body>
                <div v-if="detail" class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <UBadge :color="statusColor(detail.status)" variant="subtle" :label="t('shipping.status.' + detail.status)" />
                        <UBadge color="neutral" variant="subtle" :label="t('shipping.mode.' + detail.cost_mode)" />
                        <span class="ms-auto text-sm text-muted">{{ detail.customer }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><span class="text-muted">{{ t('shipping.route') }}:</span> {{ detail.dispatch_city }} → {{ detail.delivery_city }}</div>
                        <div><span class="text-muted">{{ t('shipping.company') }}:</span> {{ detail.company }}</div>
                        <div><span class="text-muted">{{ t('shipping.boxes') }}:</span> {{ detail.number_of_boxes }}</div>
                        <div><span class="text-muted">{{ t('shipping.shipping_cost') }}:</span> {{ detail.shipping_cost }}</div>
                        <div><span class="text-muted">{{ t('shipping.waybill') }}:</span> {{ detail.waybill_number ?? '—' }}</div>
                        <div><span class="text-muted">{{ t('shipping.value') }}:</span> {{ detail.shipment_value }}</div>
                    </div>

                    <div v-if="detail.pod_url">
                        <div class="mb-1 text-sm font-medium">{{ t('shipping.pod') }}</div>
                        <img :src="detail.pod_url" class="max-h-48 rounded-lg border border-default" alt="" />
                    </div>

                    <div>
                        <div class="mb-2 text-sm font-medium">{{ t('shipping.returns') }}</div>
                        <div v-for="r in detail.returns" :key="r.id" class="mb-2 rounded-lg border border-default p-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium">{{ r.product }}</div>
                                    <div class="text-xs text-muted">{{ t('inventory.quantity') }}: {{ r.quantity }} · {{ r.reason }}</div>
                                </div>
                                <UBadge :color="returnStatusColor(r.status)" variant="subtle" size="sm" :label="t('shipping.return_status.' + r.status)" />
                            </div>
                            <div v-if="canManage && ['pending', 'approved'].includes(r.status)" class="mt-2 flex gap-2">
                                <UButton :label="t('shipping.process_return')" icon="i-heroicons-check" color="success" variant="ghost" size="xs" @click="processReturn(r.id)" />
                                <UButton :label="t('common.reject')" icon="i-heroicons-x-mark" color="error" variant="ghost" size="xs" @click="rejectReturn(r.id)" />
                            </div>
                        </div>
                        <span v-if="!detail.returns.length" class="text-sm text-muted">{{ t('common.no_results') }}</span>
                    </div>
                </div>
            </template>
        </USlideover>

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.shipping')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
