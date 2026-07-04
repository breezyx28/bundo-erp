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
    brands: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
    sortOptions: { type: Array, default: () => [] },
});

const { t } = useTrans();
const { filters, toggleSort } = useTableFilters('brands.index', {
    search: props.filters.search ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
});

const headers = [
    { key: 'name', label: t('fields.name'), sortable: true },
    { key: 'products_count', label: t('nav.products'), sortable: true, align: 'end' },
    { key: 'is_active', label: t('common.status'), sortable: true },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('brands.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.brands.data ?? []).map((row) => ({
        ...row,
        is_active: row.is_active ? t('common.active') : t('common.inactive'),
    })),
);

const form = useForm({
    name: '',
    description: '',
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
} = useResourceForm(form, { resource: 'brands' });
</script>

<template>
    <AppLayout :title="t('nav.brands')">
        <Head :title="t('nav.brands')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.brands') }}
                </h1>
                <UButton
                    :label="t('common.create')"
                    icon="i-heroicons-plus"
                    @click="openCreate()"
                />
            </div>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="brands"
                    :query="filters"
                    :sort="filters.sort"
                    :direction="filters.direction"
                    striped
                    actions
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
        >
            <div class="space-y-4">
                <UFormField :label="t('fields.name')" :error="form.errors.name">
                    <UInput v-model="form.name" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.description')" :error="form.errors.description">
                    <UTextarea v-model="form.description" :rows="2" class="w-full" />
                </UFormField>
                <UCheckbox v-model="form.is_active" :label="t('common.active')" />
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>

        <ConfirmModal
            v-model:open="deleteOpen"
            :loading="deleting"
            @confirm="destroy()"
        />

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.brands')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
