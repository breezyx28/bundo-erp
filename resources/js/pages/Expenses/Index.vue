<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
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
import { useResourceForm } from '@/composables/useResourceForm';
import { useOpenCreateQuery } from '@/composables/useOpenCreateQuery';
import AutocompleteInput from '@/components/AutocompleteInput.vue';

const props = defineProps({
    expenses: { type: Object, required: true },
    report: { type: Object, required: true },
    categoryOptions: { type: Array, default: () => [] },
    methodOptions: { type: Array, default: () => [] },
    poOptions: { type: Array, default: () => [] },
    sortOptions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters, toggleSort } = useTableFilters('expenses.index', {
    search: props.filters.search ?? '',
    category: props.filters.category ?? null,
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
});

const headers = [
    { key: 'expense_date', label: t('sales.date'), sortable: true },
    { key: 'category', label: t('fields.category') },
    { key: 'description', label: t('fields.description') },
    { key: 'amount', label: t('purchasing.amount'), align: 'end', sortable: true },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('expenses.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.expenses.data ?? []).map((row) => ({
        ...row,
        amount: row.amount_formatted,
        category: row.category ?? '—',
    })),
);

const categoryItems = computed(() => [
    { label: t('common.all'), value: null },
    ...props.categoryOptions.map((c) => ({ label: c.name, value: c.id })),
]);

const categoryFormItems = computed(() =>
    props.categoryOptions.map((c) => ({ label: c.name, value: c.id })),
);

const methodItems = computed(() =>
    props.methodOptions.map((m) => ({ label: m.label, value: m.value })),
);

const poItems = computed(() =>
    props.poOptions.map((p) => ({ label: p.name, value: p.id })),
);

const form = useForm({
    expense_category_id: null,
    amount: 0,
    description: '',
    expense_date: props.filters.to ?? '',
    payment_method: 'cash',
    receipt_number: '',
    linked: false,
    purchase_order_id: null,
    receipt: null,
});

const {
    modalOpen,
    editingId,
    deleteOpen,
    deleting,
    openCreate,
    openEdit,
    askDelete,
    destroy,
    clearDraft,
} = useResourceForm(form, {
    resource: 'expenses',
    draftKey: 'expenses',
    draftLabel: t('nav.expenses'),
    only: [
        'expense_category_id', 'amount', 'description', 'expense_date',
        'payment_method', 'receipt_number', 'linked', 'purchase_order_id',
    ],
});

useOpenCreateQuery(openCreate, () => props.canManage);

function onReceiptChange(event) {
    form.receipt = event.target.files[0] ?? null;
}

const toPayload = (data) => ({ ...data, linked: data.linked ? 1 : 0 });

function submit() {
    const withFile = form.receipt instanceof File;
    const options = {
        preserveScroll: true,
        forceFormData: withFile,
        onSuccess: () => {
            clearDraft();
            modalOpen.value = false;
        },
    };

    if (editingId.value) {
        if (withFile) {
            form.transform((data) => ({ ...toPayload(data), _method: 'put' }))
                .post(route('expenses.update', editingId.value), options);
        } else {
            form.transform(toPayload).put(route('expenses.update', editingId.value), options);
        }
    } else {
        form.transform(toPayload).post(route('expenses.store'), options);
    }
}
</script>

<template>
    <AppLayout :title="t('nav.expenses')">
        <Head :title="t('nav.expenses')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.expenses') }}
                </h1>
                <div class="flex items-center gap-2">
                    <UButton
                        :label="t('nav.expense_categories')"
                        icon="i-heroicons-tag"
                        color="neutral"
                        variant="ghost"
                        :to="route('expense-categories.index')"
                        as="a"
                    />
                    <UButton
                        v-if="canManage"
                        :label="t('expenses.new')"
                        icon="i-heroicons-plus"
                        @click="openCreate()"
                    />
                </div>
            </div>

            <UCard>
                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="font-medium">{{ t('expenses.report') }}</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <UInput v-model="filters.from" type="date" class="w-40" />
                            <UInput v-model="filters.to" type="date" class="w-40" />
                            <USelectMenu
                                v-model="filters.category"
                                :items="categoryItems"
                                value-key="value"
                                class="w-48"
                            />
                        </div>
                    </div>
                </template>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="rounded-lg border border-default p-4">
                        <div class="text-sm text-muted">{{ t('expenses.total') }}</div>
                        <div class="mt-1 text-2xl font-semibold text-error tabular-nums">
                            {{ report.total }}
                        </div>
                        <div class="text-xs text-muted">
                            {{ t('expenses.count', { count: report.count }) }}
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="mb-2 text-sm font-medium">{{ t('expenses.by_category') }}</div>
                        <div class="space-y-1">
                            <div
                                v-for="(c, index) in report.by_category"
                                :key="index"
                                class="flex items-center justify-between rounded-lg border border-default px-3 py-2 text-sm"
                            >
                                <span>{{ c.category }} <span class="text-dimmed">· {{ c.count }}</span></span>
                                <span class="font-medium tabular-nums">{{ c.total }}</span>
                            </div>
                            <div v-if="!report.by_category.length" class="text-sm text-muted">
                                {{ t('common.no_results') }}
                            </div>
                        </div>
                    </div>
                </div>
            </UCard>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="expenses"
                    :query="filters"
                    :sort="filters.sort"
                    :direction="filters.direction"
                    :actions="canManage"
                    @sort="toggleSort"
                >
                    <template #toolbar>
                        <TableToolbar
                            :filters="filters"
                            :sort-options="sortOptions"
                            :column-options="columnOptions"
                            :date-range="false"
                            @toggle-column="toggleColumn"
                            @print="printOpen = true"
                        />
                    </template>

                    <template #cell-expense_date="{ value }">
                        <span class="text-xs">{{ value }}</span>
                    </template>

                    <template #cell-category="{ row }">
                        {{ row.category }}
                        <UBadge
                            v-if="row.is_linked"
                            color="info"
                            variant="subtle"
                            size="sm"
                            class="ms-1"
                            :label="t('expenses.linked')"
                        />
                    </template>

                    <template #cell-amount="{ row }">
                        <span class="text-end font-medium tabular-nums">{{ row.amount_formatted }}</span>
                    </template>

                    <template #actions="{ row }">
                        <UButton
                            v-if="row.receipt_url"
                            icon="i-heroicons-paper-clip"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            :to="row.receipt_url"
                            as="a"
                            target="_blank"
                        />
                        <template v-if="canManage">
                            <UButton icon="i-heroicons-pencil-square" color="neutral" variant="ghost" size="sm" @click="openEdit(row)" />
                            <UButton icon="i-heroicons-trash" color="error" variant="ghost" size="sm" @click="askDelete(row.id)" />
                        </template>
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal
            v-model:open="modalOpen"
            :title="editingId ? t('common.edit') : t('expenses.new')"
            width="sm:max-w-xl"
        >
            <div class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('fields.category')" :error="form.errors.expense_category_id">
                        <USelectMenu v-model="form.expense_category_id" :items="categoryFormItems" value-key="value" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('purchasing.amount')" :error="form.errors.amount">
                        <UInput v-model="form.amount" type="number" step="0.01" min="0.01" class="w-full" />
                    </UFormField>
                </div>
                <UFormField :label="t('fields.description')" :error="form.errors.description">
                    <AutocompleteInput v-model="form.description" field="expense_description" />
                </UFormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('sales.date')" :error="form.errors.expense_date">
                        <UInput v-model="form.expense_date" type="date" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('purchasing.method')" :error="form.errors.payment_method">
                        <USelectMenu v-model="form.payment_method" :items="methodItems" value-key="value" class="w-full" />
                    </UFormField>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('expenses.receipt_number')" :error="form.errors.receipt_number">
                        <UInput v-model="form.receipt_number" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('expenses.receipt')" :error="form.errors.receipt">
                        <input
                            type="file"
                            accept="image/*"
                            class="block w-full text-sm text-muted file:me-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
                            @change="onReceiptChange"
                        />
                    </UFormField>
                </div>
                <UCheckbox v-model="form.linked" :label="t('expenses.link_to_po')" />
                <UFormField v-if="form.linked" :label="t('nav.purchases')" :error="form.errors.purchase_order_id">
                    <USelectMenu
                        v-model="form.purchase_order_id"
                        :items="poItems"
                        value-key="value"
                        searchable
                        :placeholder="t('common.none')"
                        class="w-full"
                    />
                </UFormField>
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>

        <ConfirmModal v-model:open="deleteOpen" :loading="deleting" @confirm="destroy()" />

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.expenses')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
