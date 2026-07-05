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
import { useFormDraft, useDraftQueryRestore } from '@/composables/useFormDraft';

const props = defineProps({
    transfers: { type: Object, required: true },
    branchOptions: { type: Array, default: () => [] },
    defaultFromBranch: { type: [Number, null], default: null },
    productOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
    detail: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters, toggleSort } = useTableFilters('transfers.index', {
    status: props.filters.status ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
});

const headers = [
    { key: 'number', label: t('inventory.transfer_no'), sortable: true },
    { key: 'from', label: t('inventory.from_branch') },
    { key: 'to', label: t('inventory.to_branch') },
    { key: 'status', label: t('common.status'), sortable: true },
    { key: 'created_at', label: t('inventory.requested_at'), sortable: true },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('transfers.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.transfers.data ?? []).map((row) => ({
        ...row,
        from: row.from ?? '—',
        to: row.to ?? '—',
        status: t('inventory.status.' + row.status),
    })),
);

const statusItems = computed(() => [{ label: t('common.all'), value: '' }, ...props.statusOptions]);
const branchItems = computed(() => props.branchOptions.map((b) => ({ label: b.name, value: b.id })));
const productItems = computed(() => props.productOptions.map((p) => ({ label: p.name, value: p.id })));

const statusColor = (s) => ({
    requested: 'info', approved: 'primary', dispatched: 'warning', received: 'success', cancelled: 'neutral',
}[s] ?? 'neutral');

// Create
const createOpen = ref(false);
const form = useForm({
    from_branch_id: props.defaultFromBranch,
    to_branch_id: null,
    notes: '',
    items: [],
});
const transferDraft = useFormDraft({
    key: 'transfers.create',
    label: t('inventory.new_transfer'),
    routeName: 'transfers.index',
    form,
    active: createOpen,
    isEmpty: () => !form.items.some((item) => item.product_id),
});
function addItem() {
    form.items.push({ product_id: null, quantity: 1 });
}
function removeItem(index) {
    form.items.splice(index, 1);
}
function openCreate() {
    form.reset();
    form.clearErrors();
    form.from_branch_id = props.defaultFromBranch;
    form.items = [{ product_id: null, quantity: 1 }];
    transferDraft.restoreDraft(false);
    createOpen.value = true;
}
function submit() {
    form.post(route('transfers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            transferDraft.clearDraft();
            createOpen.value = false;
        },
    });
}

useDraftQueryRestore('transfers', () => {
    if (transferDraft.restoreDraft(true)) {
        createOpen.value = true;
    }
});

function applyAction(action, id) {
    router.post(route('transfers.action', id), { action }, { preserveScroll: true });
}

// Cancel confirm
const cancelOpen = ref(false);
const cancelId = ref(null);
const cancelling = ref(false);
function askCancel(id) {
    cancelId.value = id;
    cancelOpen.value = true;
}
function confirmCancel() {
    cancelling.value = true;
    router.post(route('transfers.action', cancelId.value), { action: 'cancel' }, {
        preserveScroll: true,
        onFinish: () => {
            cancelling.value = false;
            cancelOpen.value = false;
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
    <AppLayout :title="t('inventory.stock_transfers')">
        <Head :title="t('inventory.stock_transfers')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">{{ t('inventory.stock_transfers') }}</h1>
                <UButton v-if="canManage" :label="t('inventory.new_transfer')" icon="i-heroicons-plus" @click="openCreate()" />
            </div>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="transfers"
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
                            :search="false"
                            @toggle-column="toggleColumn"
                            @print="printOpen = true"
                        >
                            <template #filters>
                                <USelectMenu v-model="filters.status" :items="statusItems" value-key="value" class="w-full sm:w-48" />
                            </template>
                        </TableToolbar>
                    </template>

                    <template #cell-status="{ value }">
                        <UBadge :color="statusColor(value)" variant="subtle" :label="t('inventory.status.' + value)" />
                    </template>
                    <template #cell-created_at="{ value }">
                        <span class="text-xs text-muted">{{ value }}</span>
                    </template>

                    <template #actions="{ row }">
                        <UButton icon="i-heroicons-eye" color="neutral" variant="ghost" size="sm" @click="openDetail(row.id)" />
                        <template v-if="canManage">
                            <UButton v-if="row.status === 'requested'" icon="i-heroicons-check" color="primary" variant="ghost" size="sm" @click="applyAction('approve', row.id)" />
                            <UButton v-else-if="row.status === 'approved'" icon="i-heroicons-paper-airplane" color="warning" variant="ghost" size="sm" @click="applyAction('dispatch', row.id)" />
                            <UButton v-else-if="row.status === 'dispatched'" icon="i-heroicons-arrow-down-on-square" color="success" variant="ghost" size="sm" @click="applyAction('receive', row.id)" />
                            <UButton v-if="['requested', 'approved'].includes(row.status)" icon="i-heroicons-x-mark" color="error" variant="ghost" size="sm" @click="askCancel(row.id)" />
                        </template>
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal v-model:open="createOpen" :title="t('inventory.new_transfer')" width="sm:max-w-2xl">
            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('inventory.from_branch')" :error="form.errors.from_branch_id">
                        <USelectMenu v-model="form.from_branch_id" :items="branchItems" value-key="value" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('inventory.to_branch')" :error="form.errors.to_branch_id">
                        <USelectMenu v-model="form.to_branch_id" :items="branchItems" value-key="value" class="w-full" />
                    </UFormField>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">{{ t('inventory.items') }}</span>
                        <UButton size="xs" icon="i-heroicons-plus" :label="t('inventory.add_item')" variant="ghost" @click="addItem" />
                    </div>
                    <div v-for="(item, index) in form.items" :key="index" class="flex items-end gap-2">
                        <USelectMenu v-model="item.product_id" :items="productItems" value-key="value" searchable :placeholder="t('nav.products')" class="flex-1" />
                        <UInput v-model="item.quantity" type="number" min="1" class="w-28" :placeholder="t('inventory.quantity')" />
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
                <UButton :label="t('common.create')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>

        <USlideover v-model:open="detailOpen" :title="detail?.number" :description="t('inventory.stock_transfers')">
            <template #body>
                <div v-if="detail" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <UBadge :color="statusColor(detail.status)" variant="subtle" :label="t('inventory.status.' + detail.status)" />
                        <span class="text-sm text-muted">{{ detail.from }} → {{ detail.to }}</span>
                    </div>

                    <div class="divide-y divide-default rounded-lg border border-default">
                        <div v-for="(item, index) in detail.items" :key="index" class="flex items-center justify-between p-3">
                            <div>
                                <div class="font-medium">{{ item.product }}</div>
                                <div v-if="item.variant" class="text-xs text-dimmed">{{ item.variant }}</div>
                            </div>
                            <div class="text-end tabular-nums">
                                <div class="font-semibold">{{ item.quantity.toLocaleString() }}</div>
                                <div v-if="item.received_quantity" class="text-xs text-success">
                                    {{ t('inventory.received') }}: {{ item.received_quantity.toLocaleString() }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <UAlert v-if="detail.notes" :title="detail.notes" icon="i-heroicons-chat-bubble-bottom-center-text" color="neutral" variant="soft" />
                </div>
            </template>
        </USlideover>

        <ConfirmModal
            v-model:open="cancelOpen"
            :loading="cancelling"
            :message="t('common.confirm') + '?'"
            :confirm-label="t('inventory.cancel')"
            @confirm="confirmCancel()"
        />

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('inventory.stock_transfers')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
