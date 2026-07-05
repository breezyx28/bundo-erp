<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import StatusBadgeCell from '@/components/StatusBadgeCell.vue';
import TableRowActions from '@/components/TableRowActions.vue';
import { useTrans } from '@/composables/useTrans';
import { useIndexTable } from '@/composables/useIndexTable';
import { useResourceForm } from '@/composables/useResourceForm';
import { numericHeader, textHeader } from '@/utils/tableHeaders';

const props = defineProps({
    categories: { type: Object, required: true },
    parents: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '' }) },
    sortOptions: { type: Array, default: () => [] },
});

const { t } = useTrans();

const headers = [
    textHeader('name', t('fields.name'), true),
    textHeader('parent', t('fields.parent_category')),
    numericHeader('products_count', t('nav.products'), true),
    textHeader('is_active', t('common.status'), true),
];

const {
    filters,
    toggleSort,
    visibleHeaders,
    columnOptions,
    toggleColumn,
    printOpen,
} = useIndexTable('categories.index', headers, {
    search: props.filters.search ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
});
const printRows = computed(() =>
    (props.categories.data ?? []).map((row) => ({
        ...row,
        parent: row.parent || '—',
        is_active: row.is_active ? t('common.active') : t('common.inactive'),
    })),
);

const form = useForm({
    name: '',
    parent_id: null,
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
} = useResourceForm(form, {
    resource: 'categories',
    draftKey: 'categories',
    draftLabel: t('nav.categories'),
    only: ['name', 'parent_id', 'description', 'is_active'],
});

const parentItems = computed(() => [
    { label: '—', value: null },
    ...props.parents
        .filter((parent) => parent.id !== editingId.value)
        .map((parent) => ({ label: parent.name, value: parent.id })),
]);
</script>

<template>
    <AppLayout :title="t('nav.categories')">
        <Head :title="t('nav.categories')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.categories') }}
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
                    :rows="categories"
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

                    <template #cell-name="{ row }">
                        <span
                            class="inline-flex items-center gap-1.5"
                            :class="row.parent_id ? 'ps-3 text-muted' : 'font-medium text-highlighted'"
                        >
                            <UIcon
                                v-if="row.parent_id"
                                name="i-heroicons-arrow-turn-down-right"
                                class="size-4 shrink-0 opacity-60"
                            />
                            {{ row.name }}
                        </span>
                    </template>

                    <template #cell-parent="{ row }">
                        <span v-if="row.parent" class="text-muted">{{ row.parent }}</span>
                        <span v-else class="text-dimmed italic">—</span>
                    </template>

                    <template #cell-is_active="{ row }">
                        <StatusBadgeCell
                            :active="row.is_active"
                            :active-label="t('common.active')"
                            :inactive-label="t('common.inactive')"
                        />
                    </template>

                    <template #actions="{ row }">
                        <TableRowActions @edit="openEdit(row)" @delete="askDelete(row.id)" />
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
                <UFormField
                    :label="t('fields.parent_category')"
                    :description="t('fields.parent_help')"
                    :error="form.errors.parent_id"
                >
                    <USelectMenu
                        v-model="form.parent_id"
                        :items="parentItems"
                        value-key="value"
                        class="w-full"
                    />
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
            :title="t('nav.categories')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
