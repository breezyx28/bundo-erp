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
import { useOpenCreateQuery } from '@/composables/useOpenCreateQuery';
import { useFormDraft, useDraftQueryRestore } from '@/composables/useFormDraft';

const props = defineProps({
    orders: { type: Object, required: true },
    supplierOptions: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    methodOptions: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
    editing: { type: Object, default: null },
    receiveDetail: { type: Object, default: null },
    detail: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    canCreate: { type: Boolean, default: false },
    canReceive: { type: Boolean, default: false },
    canPay: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters, toggleSort } = useTableFilters('purchases.index', {
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const headers = [
    { key: 'po_number', label: t('purchasing.po_number'), sortable: true },
    { key: 'supplier', label: t('nav.suppliers') },
    { key: 'order_date', label: t('purchasing.order_date'), sortable: true },
    { key: 'total_amount', label: t('purchasing.total'), align: 'end', sortable: true },
    { key: 'order_status', label: t('purchasing.order_status') },
    { key: 'payment_status', label: t('purchasing.payment_status') },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('purchases.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.orders.data ?? []).map((row) => ({
        ...row,
        supplier: row.supplier ?? '—',
        order_status: t('purchasing.status.' + row.order_status),
        payment_status: t('sales.pay.' + row.payment_status),
    })),
);

const statusItems = computed(() => [
    { label: t('common.all'), value: '' },
    ...props.statusOptions,
]);
const supplierItems = computed(() => props.supplierOptions.map((s) => ({ label: s.name, value: s.id })));
const productItems = computed(() => props.productOptions.map((p) => ({ label: p.name, value: p.id })));
const methodItems = computed(() => props.methodOptions);

const orderStatusColor = (s) => ({
    draft: 'neutral', ordered: 'info', partial: 'warning', received: 'success', cancelled: 'error',
}[s] ?? 'neutral');
const paymentStatusColor = (s) => ({
    unpaid: 'error', partial: 'warning', paid: 'success',
}[s] ?? 'neutral');

// Create / edit
const today = new Date().toISOString().slice(0, 10);
const formOpen = ref(false);
const editingId = ref(null);
const activeDraftKey = ref('purchases.create');
const activeDraftLabel = ref(t('purchasing.new_po'));
const form = useForm({
    supplier_id: null,
    order_date: today,
    expected_delivery_date: null,
    payment_due_date: null,
    notes: '',
    items: [],
});

const purchaseDraft = useFormDraft({
    key: activeDraftKey,
    label: activeDraftLabel,
    routeName: 'purchases.index',
    form,
    active: formOpen,
    getSnapshot: () => ({ ...form.data(), editingId: editingId.value }),
    onApply: (data) => {
        editingId.value = data.editingId ?? null;
        const { editingId: _id, ...rest } = data;
        Object.keys(rest).forEach((field) => {
            if (field in form) {
                form[field] = rest[field];
            }
        });
    },
    isEmpty: () => !form.items.some((item) => item.product_id),
});

useDraftQueryRestore('purchases', (key) => {
    activeDraftKey.value = key;
    activeDraftLabel.value = key.includes('.edit:')
        ? `${t('purchasing.new_po')} (#${key.split(':')[1]})`
        : t('purchasing.new_po');
    if (purchaseDraft.restoreDraft(true)) {
        formOpen.value = true;
    }
});

function resetItems() {
    form.items = [{ product_id: null, quantity: 1, cost_per_unit: 0 }];
}
function addItem() {
    form.items.push({ product_id: null, quantity: 1, cost_per_unit: 0 });
}
function removeItem(index) {
    form.items.splice(index, 1);
}

function openCreate() {
    editingId.value = null;
    activeDraftKey.value = 'purchases.create';
    activeDraftLabel.value = t('purchasing.new_po');
    form.reset();
    form.clearErrors();
    form.order_date = today;
    resetItems();
    purchaseDraft.restoreDraft(false);
    formOpen.value = true;
}

useOpenCreateQuery(openCreate, () => props.canCreate);

function openEdit(id) {
    router.reload({
        only: ['editing'],
        data: { editing: id },
        onSuccess: () => {
            if (!props.editing) {
                return;
            }
            editingId.value = props.editing.id;
            activeDraftKey.value = `purchases.edit:${props.editing.id}`;
            activeDraftLabel.value = `${t('purchasing.new_po')} (#${props.editing.id})`;
            form.clearErrors();
            form.supplier_id = props.editing.supplier_id;
            form.order_date = props.editing.order_date;
            form.expected_delivery_date = props.editing.expected_delivery_date;
            form.payment_due_date = props.editing.payment_due_date;
            form.notes = props.editing.notes ?? '';
            form.items = props.editing.items.length
                ? props.editing.items.map((i) => ({ ...i }))
                : [{ product_id: null, quantity: 1, cost_per_unit: 0 }];
            purchaseDraft.restoreDraft(false);
            formOpen.value = true;
        },
    });
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            purchaseDraft.clearDraft();
            formOpen.value = false;
        },
    };
    if (editingId.value) {
        form.put(route('purchases.update', editingId.value), options);
    } else {
        form.post(route('purchases.store'), options);
    }
}

function place(id) {
    router.post(route('purchases.place', id), {}, { preserveScroll: true });
}
function cancelOrder(id) {
    router.post(route('purchases.cancel', id), {}, { preserveScroll: true });
}

// Receive
const receiveOpen = ref(false);
const receiveForm = useForm({ quantities: {} });
function openReceive(id) {
    router.reload({
        only: ['receiveDetail'],
        data: { receive: id },
        onSuccess: () => {
            if (!props.receiveDetail) {
                return;
            }
            const quantities = {};
            for (const item of props.receiveDetail.items) {
                quantities[item.id] = item.outstanding;
            }
            receiveForm.quantities = quantities;
            receiveForm.clearErrors();
            receiveOpen.value = true;
        },
    });
}
function submitReceive() {
    receiveForm.post(route('purchases.receive', props.receiveDetail.id), {
        preserveScroll: true,
        onSuccess: () => {
            receiveOpen.value = false;
        },
    });
}

// Payment
const paymentOpen = ref(false);
const payId = ref(null);
const payForm = useForm({
    pay_amount: 0,
    pay_method: 'cash',
    pay_date: today,
    pay_reference: '',
});
function openPayment(row) {
    payId.value = row.id;
    payForm.reset();
    payForm.clearErrors();
    payForm.pay_amount = row.outstanding;
    payForm.pay_date = today;
    paymentOpen.value = true;
}
function submitPayment() {
    payForm.post(route('purchases.payment', payId.value), {
        preserveScroll: true,
        onSuccess: () => {
            paymentOpen.value = false;
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
</script>

<template>
    <AppLayout :title="t('nav.purchases')">
        <Head :title="t('nav.purchases')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">{{ t('nav.purchases') }}</h1>
                <UButton
                    v-if="canCreate"
                    :label="t('purchasing.new_po')"
                    icon="i-heroicons-plus"
                    @click="openCreate()"
                />
            </div>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="orders"
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
                            @toggle-column="toggleColumn"
                            @print="printOpen = true"
                        >
                            <template #filters>
                                <USelectMenu v-model="filters.status" :items="statusItems" value-key="value" class="w-full sm:w-40" />
                            </template>
                        </TableToolbar>
                    </template>

                    <template #cell-order_date="{ value }">
                        <span class="text-xs">{{ value }}</span>
                    </template>
                    <template #cell-total_amount="{ value }">
                        <span class="font-medium tabular-nums">{{ value }}</span>
                    </template>
                    <template #cell-order_status="{ value }">
                        <UBadge :color="orderStatusColor(value)" variant="subtle" :label="t('purchasing.status.' + value)" />
                    </template>
                    <template #cell-payment_status="{ value }">
                        <UBadge :color="paymentStatusColor(value)" variant="subtle" :label="t('purchasing.pay.' + value)" />
                    </template>

                    <template #actions="{ row }">
                        <UButton icon="i-heroicons-eye" color="neutral" variant="ghost" size="sm" @click="openDetail(row.id)" />
                        <UButton v-if="row.is_editable && canCreate" icon="i-heroicons-pencil-square" color="neutral" variant="ghost" size="sm" @click="openEdit(row.id)" />
                        <UButton v-if="row.order_status === 'draft' && canCreate" icon="i-heroicons-check-circle" color="info" variant="ghost" size="sm" @click="place(row.id)" />
                        <UButton v-if="row.is_receivable && canReceive" icon="i-heroicons-arrow-down-tray" color="success" variant="ghost" size="sm" @click="openReceive(row.id)" />
                        <UButton v-if="row.payment_status !== 'paid' && row.order_status !== 'cancelled' && canPay" icon="i-heroicons-banknotes" color="primary" variant="ghost" size="sm" @click="openPayment(row)" />
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal
            v-model:open="formOpen"
            :title="editingId ? t('purchasing.edit_po') : t('purchasing.new_po')"
            width="sm:max-w-3xl"
        >
            <div class="grid gap-4">
                <div class="responsive-stat-grid">
                    <UFormField :label="t('nav.suppliers')" :error="form.errors.supplier_id">
                        <USelectMenu v-model="form.supplier_id" :items="supplierItems" value-key="value" searchable class="w-full" />
                    </UFormField>
                    <UFormField :label="t('purchasing.order_date')" :error="form.errors.order_date">
                        <UInput v-model="form.order_date" type="date" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('purchasing.expected_delivery')" :error="form.errors.expected_delivery_date">
                        <UInput v-model="form.expected_delivery_date" type="date" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('purchasing.payment_due')" :error="form.errors.payment_due_date" :hint="t('purchasing.payment_due_hint')">
                        <UInput v-model="form.payment_due_date" type="date" class="w-full" />
                    </UFormField>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">{{ t('purchasing.items') }}</span>
                        <UButton size="xs" icon="i-heroicons-plus" :label="t('purchasing.add_item')" variant="ghost" @click="addItem" />
                    </div>
                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="flex items-end gap-2"
                    >
                        <USelectMenu
                            v-model="item.product_id"
                            :items="productItems"
                            value-key="value"
                            searchable
                            :placeholder="t('nav.products')"
                            class="flex-1"
                        />
                        <UInput v-model="item.quantity" type="number" min="1" class="w-24" :placeholder="t('purchasing.qty')" />
                        <UInput v-model="item.cost_per_unit" type="number" step="0.01" min="0" class="w-32" :placeholder="t('fields.cost_price')" />
                        <UButton icon="i-heroicons-trash" color="error" variant="ghost" size="sm" @click="removeItem(index)" />
                    </div>
                    <p v-if="form.errors.items" class="text-xs text-error">{{ form.errors.items }}</p>
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

        <FormModal v-model:open="receiveOpen" :title="t('purchasing.receive_stock')" width="sm:max-w-2xl">
            <div v-if="receiveDetail" class="space-y-2">
                <div
                    v-for="item in receiveDetail.items"
                    :key="item.id"
                    class="flex items-center justify-between gap-3 rounded-lg border border-default p-3"
                >
                    <div class="flex-1">
                        <div class="font-medium">{{ item.product }}</div>
                        <div class="text-xs text-muted">
                            {{ t('purchasing.outstanding') }}: {{ item.outstanding }} / {{ item.quantity }}
                        </div>
                    </div>
                    <UInput v-model="receiveForm.quantities[item.id]" type="number" min="0" :max="item.outstanding" class="w-28" />
                </div>
            </div>
            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('purchasing.receive')" :loading="receiveForm.processing" @click="submitReceive()" />
            </template>
        </FormModal>

        <FormModal v-model:open="paymentOpen" :title="t('purchasing.record_payment')" width="sm:max-w-lg">
            <div class="grid gap-4">
                <UFormField :label="t('purchasing.amount')" :error="payForm.errors.pay_amount">
                    <UInput v-model="payForm.pay_amount" type="number" step="0.01" min="0.01" class="w-full" />
                </UFormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('purchasing.method')" :error="payForm.errors.pay_method">
                        <USelectMenu v-model="payForm.pay_method" :items="methodItems" value-key="value" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('purchasing.payment_date')" :error="payForm.errors.pay_date">
                        <UInput v-model="payForm.pay_date" type="date" class="w-full" />
                    </UFormField>
                </div>
                <UFormField :label="t('purchasing.reference')" :error="payForm.errors.pay_reference">
                    <UInput v-model="payForm.pay_reference" class="w-full" />
                </UFormField>
            </div>
            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="payForm.processing" @click="submitPayment()" />
            </template>
        </FormModal>

        <USlideover v-model:open="detailOpen" :title="detail?.po_number" :description="t('nav.purchases')">
            <template #body>
                <div v-if="detail" class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <UBadge :color="orderStatusColor(detail.order_status)" variant="subtle" :label="t('purchasing.status.' + detail.order_status)" />
                        <UBadge :color="paymentStatusColor(detail.payment_status)" variant="subtle" :label="t('purchasing.pay.' + detail.payment_status)" />
                        <span class="ms-auto text-sm text-muted">{{ detail.supplier }}</span>
                    </div>

                    <div class="divide-y divide-default rounded-lg border border-default">
                        <div v-for="(item, index) in detail.items" :key="index" class="flex items-center justify-between p-3">
                            <div>
                                <div class="font-medium">{{ item.product }}</div>
                                <div class="text-xs text-muted">
                                    {{ item.received_quantity }} / {{ item.quantity }} @ {{ item.cost_per_unit }}
                                </div>
                            </div>
                            <span class="font-medium tabular-nums">{{ item.total }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between rounded-lg bg-elevated p-3 font-semibold">
                        <span>{{ t('purchasing.total') }}</span>
                        <span class="tabular-nums">{{ detail.total_amount }}</span>
                    </div>
                    <div class="flex justify-between px-3 text-sm">
                        <span class="text-muted">{{ t('purchasing.paid') }}</span>
                        <span class="tabular-nums text-success">{{ detail.paid_amount }}</span>
                    </div>
                    <div class="flex justify-between px-3 text-sm">
                        <span class="text-muted">{{ t('purchasing.outstanding') }}</span>
                        <span class="tabular-nums text-error">{{ detail.outstanding }}</span>
                    </div>

                    <div v-if="detail.payments.length">
                        <div class="mb-2 text-sm font-medium">{{ t('purchasing.payments') }}</div>
                        <div class="space-y-1">
                            <div
                                v-for="(p, index) in detail.payments"
                                :key="index"
                                class="flex items-center justify-between rounded-lg border border-default px-3 py-2 text-sm"
                            >
                                <span>{{ p.date }} · {{ p.method }}</span>
                                <span class="tabular-nums">{{ p.amount }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </USlideover>

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.purchases')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
