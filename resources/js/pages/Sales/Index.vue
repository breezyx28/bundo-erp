<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';
import { useTableColumns } from '@/composables/useTableColumns';
import { useOpenCreateQuery } from '@/composables/useOpenCreateQuery';
import { useFormDraft, useDraftQueryRestore } from '@/composables/useFormDraft';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    invoices: { type: Object, required: true },
    customerOptions: { type: Array, default: () => [] },
    productOptions: { type: Array, default: () => [] },
    stockMap: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    methodOptions: { type: Array, default: () => [] },
    discountTypeOptions: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
    currency: { type: Object, default: () => ({ symbol: '', decimals: 2 }) },
    detail: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    canCreate: { type: Boolean, default: false },
    canPay: { type: Boolean, default: false },
    canVoid: { type: Boolean, default: false },
    drafts: { type: Array, default: () => [] },
});

const { t } = useTrans();
const page = usePage();
const defaultExchangeRate = computed(() => Number(page.props.money?.exchangeRate ?? 600));
const { filters, toggleSort } = useTableFilters('sales.index', {
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const money = (amount) =>
    `${props.currency.symbol} ${Number(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: props.currency.decimals,
        maximumFractionDigits: props.currency.decimals,
    })}`;

const headers = [
    { key: 'invoice_number', label: t('sales.invoice_number'), sortable: true },
    { key: 'customer', label: t('nav.customers') },
    { key: 'invoice_date', label: t('sales.date'), sortable: true },
    { key: 'net_amount', label: t('sales.net'), align: 'end', sortable: true },
    { key: 'balance', label: t('sales.balance'), align: 'end', sortable: true },
    { key: 'payment_status', label: t('common.status') },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('sales.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.invoices.data ?? []).map((row) => ({
        ...row,
        customer: row.customer ?? t('sales.walk_in'),
        payment_status: t('sales.pay.' + row.payment_status),
    })),
);

const statusItems = computed(() => [{ label: t('common.all'), value: '' }, ...props.statusOptions]);
const customerItems = computed(() => props.customerOptions.map((c) => ({ label: c.name, value: c.id })));
const productItems = computed(() => props.productOptions.map((p) => ({ label: p.name, value: p.id })));
const methodItems = computed(() => props.methodOptions);
const discountItems = computed(() => [{ label: t('common.none'), value: null }, ...props.discountTypeOptions]);
const saleTypeItems = computed(() => [
    { label: t('sales.type.cash'), value: 'cash' },
    { label: t('sales.type.credit'), value: 'credit' },
]);

const statusColor = (s) => ({ paid: 'success', partial: 'warning', unpaid: 'error' }[s] ?? 'neutral');

// Create
const today = new Date().toISOString().slice(0, 10);
const formOpen = ref(false);
const editingDraftId = ref(null);
const holdLabel = ref('');
const form = useForm({
    id: null,
    customer_id: null,
    sale_type: 'cash',
    invoice_date: today,
    due_date: null,
    payment_method: 'cash',
    paid_amount: 0,
    discount_type: null,
    discount_value: 0,
    exchange_rate: 0,
    notes: '',
    items: [],
});

const saleDraft = useFormDraft({
    key: 'sales.create',
    label: t('sales.new_sale'),
    routeName: 'sales.index',
    form,
    active: formOpen,
    getSnapshot: () => ({
        ...form.data(),
        holdLabel: holdLabel.value,
        editingDraftId: editingDraftId.value,
    }),
    onApply: (data) => {
        holdLabel.value = data.holdLabel ?? '';
        editingDraftId.value = data.editingDraftId ?? null;
        const { holdLabel: _h, editingDraftId: _e, ...rest } = data;
        Object.keys(rest).forEach((field) => {
            if (field in form) {
                form[field] = rest[field];
            }
        });
    },
    isEmpty: () => !form.items.some((item) => item.product_id),
});

useDraftQueryRestore('sales', () => {
    if (saleDraft.restoreDraft(true)) {
        formOpen.value = true;
    }
});

function addItem() {
    form.items.push({ product_id: null, quantity: 1, unit_price: 0 });
}
function removeItem(index) {
    form.items.splice(index, 1);
}
function onProductChange(item) {
    const product = props.productOptions.find((p) => p.id === item.product_id);
    if (product) {
        item.unit_price = product.price;
    }
}
function openCreate() {
    editingDraftId.value = null;
    holdLabel.value = '';
    form.reset();
    form.clearErrors();
    form.id = null;
    form.invoice_date = today;
    form.exchange_rate = defaultExchangeRate.value;
    form.items = [{ product_id: null, quantity: 1, unit_price: 0 }];
    formOpen.value = true;
}

function resumeDraft(draft) {
    editingDraftId.value = draft.id;
    holdLabel.value = draft.hold_label ?? '';
    form.clearErrors();
    form.id = draft.id;
    form.customer_id = draft.customer_id;
    form.sale_type = draft.sale_type;
    form.invoice_date = draft.invoice_date ?? today;
    form.due_date = draft.due_date;
    form.discount_type = draft.discount_type;
    form.discount_value = draft.discount_value ?? 0;
    form.exchange_rate = draft.exchange_rate || defaultExchangeRate.value;
    form.notes = draft.notes ?? '';
    form.payment_method = 'cash';
    form.paid_amount = 0;
    form.items = draft.items.length
        ? draft.items.map((item) => ({ ...item }))
        : [{ product_id: null, quantity: 1, unit_price: 0 }];
    formOpen.value = true;
}

function holdOrder() {
    form.transform((data) => ({
        ...data,
        id: editingDraftId.value,
        hold_label: holdLabel.value || null,
    })).post(route('sales.draft'), {
        preserveScroll: true,
        onSuccess: () => {
            saleDraft.clearDraft();
            formOpen.value = false;
            editingDraftId.value = null;
        },
    });
}

function completeDraft() {
    if (editingDraftId.value) {
        form.post(route('sales.post', editingDraftId.value), {
            preserveScroll: true,
            onSuccess: () => {
                formOpen.value = false;
                editingDraftId.value = null;
                filters.search = '';
                filters.status = '';
            },
        });
        return;
    }
    submit();
}

useOpenCreateQuery(openCreate, () => props.canCreate);

const totals = computed(() => {
    let subtotal = 0;
    for (const item of form.items) {
        subtotal += (Number(item.unit_price) || 0) * (Number(item.quantity) || 0);
    }
    let discount = 0;
    if (form.discount_value > 0 && form.discount_type) {
        discount = form.discount_type === 'percentage'
            ? Math.round(subtotal * Math.min(form.discount_value, 100) / 100 * 100) / 100
            : Math.min(form.discount_value, subtotal);
    }
    return {
        subtotal: Math.round(subtotal * 100) / 100,
        discount: Math.round(discount * 100) / 100,
        net: Math.round((subtotal - discount) * 100) / 100,
    };
});

const creditNeedsCustomer = computed(
    () => form.sale_type === 'credit' && !form.customer_id,
);

// A future due date implies the customer pays later, i.e. a credit sale.
function onDueDateChange() {
    if (form.due_date && form.due_date > today) {
        form.sale_type = 'credit';
    }
}

function submit() {
    form.post(route('sales.store'), {
        preserveScroll: true,
        onSuccess: () => {
            saleDraft.clearDraft();
            formOpen.value = false;
            // Clear stale filters so the new invoice is visible on page 1.
            filters.search = '';
            filters.status = '';
        },
    });
}

// Discard held order
const discardOpen = ref(false);
const discardId = ref(null);
const discarding = ref(false);
function askDiscard(id) {
    discardId.value = id;
    discardOpen.value = true;
}
function confirmDiscard() {
    discarding.value = true;
    router.delete(route('sales.draft.discard', discardId.value), {
        preserveScroll: true,
        onFinish: () => {
            discarding.value = false;
            discardOpen.value = false;
        },
    });
}

function postDraftDirect(draft) {
    router.post(route('sales.post', draft.id), {
        payment_method: draft.sale_type === 'credit' ? 'cash' : 'cash',
        paid_amount: draft.sale_type === 'credit' ? 0 : draft.net_amount_raw,
    }, { preserveScroll: true });
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
    payForm.pay_amount = row.balance_raw;
    payForm.pay_date = today;
    paymentOpen.value = true;
}
function submitPayment() {
    payForm.post(route('sales.payment', payId.value), {
        preserveScroll: true,
        onSuccess: () => {
            paymentOpen.value = false;
        },
    });
}

// Void
const voidOpen = ref(false);
const voidId = ref(null);
const voiding = ref(false);
function askVoid(id) {
    voidId.value = id;
    voidOpen.value = true;
}
function confirmVoid() {
    voiding.value = true;
    router.post(route('sales.void', voidId.value), {}, {
        preserveScroll: true,
        onFinish: () => {
            voiding.value = false;
            voidOpen.value = false;
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
    <AppLayout :title="t('nav.sales')">
        <Head :title="t('nav.sales')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">{{ t('nav.sales') }}</h1>
                <UButton v-if="canCreate" :label="t('sales.new_sale')" icon="i-heroicons-plus" @click="openCreate()" />
            </div>

            <UCard v-if="drafts.length" class="border-warning/30">
                <template #header>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-sm font-semibold text-highlighted">{{ t('sales.held_orders') }}</h2>
                        <UBadge color="warning" variant="subtle" :label="String(drafts.length)" />
                    </div>
                </template>
                <div class="divide-y divide-default">
                    <div v-for="draft in drafts" :key="draft.id" class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="font-medium text-highlighted">
                                {{ draft.hold_label || draft.customer || t('sales.walk_in') }}
                            </p>
                            <p class="text-xs text-dimmed">
                                {{ draft.item_count }} {{ t('sales.items') }} · {{ draft.net_amount }} · {{ draft.created_at }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <UButton v-if="canCreate" size="xs" variant="ghost" :label="t('sales.resume')" @click="resumeDraft(draft)" />
                            <UButton v-if="canCreate" size="xs" color="primary" variant="soft" :label="t('sales.complete_sale')" @click="postDraftDirect(draft)" />
                            <UButton v-if="canCreate" size="xs" color="error" variant="ghost" icon="i-heroicons-trash" @click="askDiscard(draft.id)" />
                        </div>
                    </div>
                </div>
            </UCard>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="invoices"
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
                            :search-placeholder="t('common.search')"
                            @toggle-column="toggleColumn"
                            @print="printOpen = true"
                        >
                            <template #filters>
                                <USelectMenu v-model="filters.status" :items="statusItems" value-key="value" class="w-full sm:w-40" />
                            </template>
                        </TableToolbar>
                    </template>

                    <template #cell-customer="{ row }">
                        {{ row.customer ?? t('sales.walk_in') }}
                    </template>
                    <template #cell-invoice_date="{ row }">
                        <span class="text-xs">{{ row.invoice_date }}</span>
                        <UBadge v-if="row.is_overdue" color="error" variant="subtle" size="sm" class="ms-1" :label="t('sales.overdue')" />
                    </template>
                    <template #cell-net_amount="{ value }">
                        <span class="font-medium tabular-nums">{{ value }}</span>
                    </template>
                    <template #cell-balance="{ row }">
                        <span class="tabular-nums" :class="row.balance_raw > 0 ? 'text-error' : 'text-dimmed'">{{ row.balance }}</span>
                    </template>
                    <template #cell-payment_status="{ value }">
                        <UBadge :color="statusColor(value)" variant="subtle" :label="t('sales.pay.' + value)" />
                    </template>

                    <template #actions="{ row }">
                        <UButton icon="i-heroicons-eye" color="neutral" variant="ghost" size="sm" @click="openDetail(row.id)" />
                        <UButton icon="i-heroicons-printer" color="neutral" variant="ghost" size="sm" :to="route('invoices.print', row.id)" as="a" target="_blank" />
                        <UButton v-if="row.balance_raw > 0 && canPay" icon="i-heroicons-banknotes" color="primary" variant="ghost" size="sm" @click="openPayment(row)" />
                        <UButton v-if="canVoid" icon="i-heroicons-trash" color="error" variant="ghost" size="sm" @click="askVoid(row.id)" />
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal v-model:open="formOpen" :title="editingDraftId ? t('sales.resume') : t('sales.new_sale')" width="sm:max-w-4xl">
            <div class="grid gap-4">
                <UFormField v-if="canCreate" :label="t('sales.hold_label')" :hint="t('sales.hold_label_hint')">
                    <UInput v-model="holdLabel" class="w-full" />
                </UFormField>
                <div class="responsive-stat-grid">
                    <UFormField :label="t('nav.customers')" :error="form.errors.customer_id" class="sm:col-span-2">
                        <USelectMenu v-model="form.customer_id" :items="customerItems" value-key="value" searchable :placeholder="t('sales.walk_in')" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('sales.sale_type')" :error="form.errors.sale_type">
                        <USelectMenu v-model="form.sale_type" :items="saleTypeItems" value-key="value" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('sales.date')" :error="form.errors.invoice_date">
                        <UInput v-model="form.invoice_date" type="date" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('sales.due_date')" :error="form.errors.due_date" :hint="t('sales.due_date_hint')">
                        <UInput v-model="form.due_date" type="date" class="w-full" @update:model-value="onDueDateChange" />
                    </UFormField>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">{{ t('sales.items') }}</span>
                        <UButton size="xs" icon="i-heroicons-plus" :label="t('sales.add_item')" variant="ghost" @click="addItem" />
                    </div>
                    <div v-for="(item, index) in form.items" :key="index" class="flex items-end gap-2">
                        <div class="flex-1">
                            <USelectMenu
                                v-model="item.product_id"
                                :items="productItems"
                                value-key="value"
                                searchable
                                :placeholder="t('nav.products')"
                                class="w-full"
                                @update:model-value="onProductChange(item)"
                            />
                            <span v-if="item.product_id" class="text-xs text-dimmed">
                                {{ t('inventory.on_hand') }}: {{ stockMap[item.product_id] ?? 0 }}
                            </span>
                        </div>
                        <UInput v-model="item.quantity" type="number" min="1" class="w-20" :placeholder="t('inventory.quantity')" />
                        <UInput v-model="item.unit_price" type="number" step="0.01" min="0" class="w-32" :placeholder="t('fields.selling_price')" />
                        <UButton icon="i-heroicons-trash" color="error" variant="ghost" size="sm" @click="removeItem(index)" />
                    </div>
                    <p v-if="form.errors.items" class="text-xs text-error">{{ form.errors.items }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <UFormField :label="t('sales.discount')" :error="form.errors.discount_type">
                        <USelectMenu v-model="form.discount_type" :items="discountItems" value-key="value" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('sales.discount_value')" :error="form.errors.discount_value">
                        <UInput v-model="form.discount_value" type="number" step="0.01" min="0" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('sales.exchange_rate')" :error="form.errors.exchange_rate" hint="SDG / USD">
                        <UInput v-model="form.exchange_rate" type="number" step="0.0001" min="0" class="w-full" />
                    </UFormField>
                </div>

                <UFormField v-if="form.sale_type === 'credit'" :label="t('sales.initial_payment')" :error="form.errors.paid_amount">
                    <UInput v-model="form.paid_amount" type="number" step="0.01" min="0" class="w-full" />
                </UFormField>
                <UFormField v-else :label="t('purchasing.method')" :error="form.errors.payment_method">
                    <USelectMenu v-model="form.payment_method" :items="methodItems" value-key="value" class="w-full" />
                </UFormField>

                <p v-if="creditNeedsCustomer" class="text-xs text-error">{{ t('sales.credit_requires_customer') }}</p>

                <UFormField :label="t('fields.notes')" :error="form.errors.notes">
                    <UTextarea v-model="form.notes" :rows="2" class="w-full" />
                </UFormField>

                <div class="rounded-lg bg-elevated p-4 text-sm">
                    <div class="flex justify-between"><span>{{ t('sales.subtotal') }}</span><span class="tabular-nums">{{ money(totals.subtotal) }}</span></div>
                    <div class="flex justify-between text-muted"><span>{{ t('sales.discount') }}</span><span class="tabular-nums">- {{ money(totals.discount) }}</span></div>
                    <div class="mt-1 flex justify-between border-t border-default pt-1 font-semibold"><span>{{ t('sales.net') }}</span><span class="tabular-nums">{{ money(totals.net) }}</span></div>
                </div>
            </div>
            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton v-if="canCreate" color="warning" variant="soft" :label="t('sales.hold_order')" :loading="form.processing" @click="holdOrder()" />
                <UButton :label="t('sales.complete_sale')" :loading="form.processing" :disabled="creditNeedsCustomer" @click="completeDraft()" />
            </template>
        </FormModal>

        <ConfirmModal v-model:open="discardOpen" :title="t('sales.discard')" :message="t('sales.confirm_discard')" :loading="discarding" @confirm="confirmDiscard" />

        <FormModal v-model:open="paymentOpen" :title="t('sales.record_payment')" width="sm:max-w-lg">
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

        <USlideover v-model:open="detailOpen" :title="detail?.invoice_number" :description="t('nav.sales')">
            <template #body>
                <div v-if="detail" class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <UBadge :color="statusColor(detail.payment_status)" variant="subtle" :label="t('sales.pay.' + detail.payment_status)" />
                        <UBadge color="neutral" variant="subtle" :label="t('sales.type.' + detail.sale_type)" />
                        <span class="ms-auto text-sm text-muted">{{ detail.customer ?? t('sales.walk_in') }}</span>
                    </div>

                    <div class="divide-y divide-default rounded-lg border border-default">
                        <div v-for="(item, index) in detail.items" :key="index" class="flex items-center justify-between p-3">
                            <div>
                                <div class="font-medium">{{ item.product }}</div>
                                <div class="text-xs text-muted">{{ item.quantity }} @ {{ item.unit_price }}</div>
                            </div>
                            <span class="font-medium tabular-nums">{{ item.total }}</span>
                        </div>
                    </div>

                    <div class="space-y-1 rounded-lg bg-elevated p-3 text-sm">
                        <div class="flex justify-between"><span>{{ t('sales.net') }}</span><span class="font-semibold tabular-nums">{{ detail.net_amount }}</span></div>
                        <div class="flex justify-between text-success"><span>{{ t('purchasing.paid') }}</span><span class="tabular-nums">{{ detail.paid_amount }}</span></div>
                        <div class="flex justify-between text-error"><span>{{ t('sales.balance') }}</span><span class="tabular-nums">{{ detail.balance }}</span></div>
                    </div>

                    <div class="flex gap-2">
                        <UButton :label="t('sales.print')" icon="i-heroicons-printer" color="neutral" variant="outline" size="sm" :to="detail.print_url" as="a" target="_blank" />
                        <UButton :label="t('sales.download_pdf')" icon="i-heroicons-arrow-down-tray" color="neutral" variant="outline" size="sm" :to="detail.pdf_url" as="a" target="_blank" />
                    </div>
                </div>
            </template>
        </USlideover>

        <ConfirmModal
            v-model:open="voidOpen"
            :loading="voiding"
            :title="t('sales.void')"
            :message="t('common.confirm') + '?'"
            :confirm-label="t('sales.void')"
            @confirm="confirmVoid()"
        />

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.sales')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
