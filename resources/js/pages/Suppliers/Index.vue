<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
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

const props = defineProps({
    suppliers: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
    sortOptions: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters, toggleSort } = useTableFilters('suppliers.index', {
    search: props.filters.search ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
});

const headers = [
    { key: 'name', label: t('fields.name'), sortable: true },
    { key: 'contact_person', label: t('fields.contact_person'), sortable: true },
    { key: 'phone', label: t('fields.phone'), sortable: true },
    { key: 'is_active', label: t('common.status'), sortable: true },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('suppliers.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.suppliers.data ?? []).map((row) => ({
        ...row,
        is_active: row.is_active ? t('common.active') : t('common.inactive'),
    })),
);

const form = useForm({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    tax_number: '',
    opening_balance: 0,
    notes: '',
    is_active: true,
});

const {
    modalOpen,
    editingId,
    deleteOpen,
    deleting,
    openCreate,
    openEdit,
    submit,
    askDelete,
    destroy,
} = useResourceForm(form, { resource: 'suppliers', draftKey: 'suppliers', draftLabel: t('nav.suppliers') });
</script>

<template>
    <AppLayout :title="t('nav.suppliers')">
        <Head :title="t('nav.suppliers')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.suppliers') }}
                </h1>
                <UButton
                    v-if="canManage"
                    :label="t('common.create')"
                    icon="i-heroicons-plus"
                    @click="openCreate()"
                />
            </div>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="suppliers"
                    :query="filters"
                    :sort="filters.sort"
                    :direction="filters.direction"
                    striped
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

                    <template #cell-is_active="{ row }">
                        <UBadge
                            :color="row.is_active ? 'success' : 'neutral'"
                            variant="subtle"
                            :label="row.is_active ? t('common.active') : t('common.inactive')"
                        />
                    </template>

                    <template #actions="{ row }">
                        <UButton
                            icon="i-heroicons-pencil-square"
                            color="neutral"
                            variant="ghost"
                            size="sm"
                            @click="openEdit(row)"
                        />
                        <UButton
                            icon="i-heroicons-trash"
                            color="error"
                            variant="ghost"
                            size="sm"
                            @click="askDelete(row.id)"
                        />
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal
            v-model:open="modalOpen"
            :title="editingId ? t('common.edit') : t('common.create')"
            width="sm:max-w-2xl"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <UFormField :label="t('fields.name')" :error="form.errors.name">
                    <UInput v-model="form.name" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.contact_person')" :error="form.errors.contact_person">
                    <UInput v-model="form.contact_person" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.phone')" :error="form.errors.phone">
                    <UInput v-model="form.phone" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.email')" :error="form.errors.email">
                    <UInput v-model="form.email" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.tax_number')" :error="form.errors.tax_number">
                    <UInput v-model="form.tax_number" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.opening_balance')" :error="form.errors.opening_balance">
                    <UInput v-model="form.opening_balance" type="number" step="0.01" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.address')" :error="form.errors.address" class="sm:col-span-2">
                    <UTextarea v-model="form.address" :rows="2" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.notes')" :error="form.errors.notes" class="sm:col-span-2">
                    <UTextarea v-model="form.notes" :rows="2" class="w-full" />
                </UFormField>
                <UCheckbox v-model="form.is_active" :label="t('common.active')" />
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>

        <ConfirmModal v-model:open="deleteOpen" :loading="deleting" @confirm="destroy()" />

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.suppliers')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
