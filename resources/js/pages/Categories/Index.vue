<script setup>
import { computed } from 'vue';
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
    parents: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const { t } = useTrans();
const { filters } = useTableFilters('categories.index', {
    search: props.filters.search ?? '',
});

const headers = [
    { key: 'name', label: t('fields.name') },
    { key: 'parent', label: t('fields.parent') },
    { key: 'products_count', label: t('nav.products') },
    { key: 'is_active', label: t('common.status') },
];

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
                <DataTable :headers="headers" :rows="categories" :query="filters" striped actions>
                    <template #toolbar>
                        <UInput
                            v-model="filters.search"
                            icon="i-heroicons-magnifying-glass"
                            :placeholder="t('common.search')"
                            class="w-full sm:max-w-xs"
                        />
                    </template>

                    <template #cell-parent="{ value }">
                        {{ value || '—' }}
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
                <UFormField :label="t('fields.parent')" :error="form.errors.parent_id">
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
    </AppLayout>
</template>
