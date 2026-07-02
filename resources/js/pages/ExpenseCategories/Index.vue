<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';
import { useResourceForm } from '@/composables/useResourceForm';

const props = defineProps({
    categories: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters } = useTableFilters('expense-categories.index', {
    search: props.filters.search ?? '',
});

const headers = [
    { key: 'name', label: t('fields.name') },
    { key: 'is_operational', label: t('expenses.operational') },
    { key: 'expenses_count', label: t('nav.expenses'), class: 'text-end' },
    { key: 'is_active', label: t('common.status') },
];

const form = useForm({
    name: '',
    description: '',
    is_operational: true,
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
} = useResourceForm(form, { resource: 'expense-categories' });
</script>

<template>
    <AppLayout :title="t('nav.expense_categories')">
        <Head :title="t('nav.expense_categories')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.expense_categories') }}
                </h1>
                <UButton
                    v-if="canManage"
                    :label="t('common.create')"
                    icon="i-heroicons-plus"
                    @click="openCreate()"
                />
            </div>

            <UCard>
                <DataTable :headers="headers" :rows="categories" :query="filters" striped :actions="canManage">
                    <template #toolbar>
                        <UInput
                            v-model="filters.search"
                            icon="i-heroicons-magnifying-glass"
                            :placeholder="t('common.search')"
                            class="w-full sm:max-w-xs"
                        />
                    </template>

                    <template #cell-is_operational="{ row }">
                        <UBadge
                            :color="row.is_operational ? 'info' : 'neutral'"
                            variant="subtle"
                            size="sm"
                            :label="row.is_operational ? t('expenses.operational') : t('expenses.non_operational')"
                        />
                    </template>

                    <template #cell-expenses_count="{ value }">
                        <span class="tabular-nums text-muted">{{ value }}</span>
                    </template>

                    <template #cell-is_active="{ row }">
                        <UBadge
                            :color="row.is_active ? 'success' : 'neutral'"
                            variant="subtle"
                            :label="row.is_active ? t('common.active') : t('common.inactive')"
                        />
                    </template>

                    <template #actions="{ row }">
                        <UButton icon="i-heroicons-pencil-square" color="neutral" variant="ghost" size="sm" @click="openEdit(row)" />
                        <UButton icon="i-heroicons-trash" color="error" variant="ghost" size="sm" @click="askDelete(row.id)" />
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
                <UCheckbox v-model="form.is_operational" :label="t('expenses.operational')" />
                <UCheckbox v-model="form.is_active" :label="t('common.active')" />
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>

        <ConfirmModal v-model:open="deleteOpen" :loading="deleting" @confirm="destroy()" />
    </AppLayout>
</template>
