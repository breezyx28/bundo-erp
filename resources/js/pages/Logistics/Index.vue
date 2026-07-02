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
    companies: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters } = useTableFilters('logistics.index', {
    search: props.filters.search ?? '',
});

const headers = [
    { key: 'name', label: t('fields.name') },
    { key: 'contact_person', label: t('shipping.contact_person') },
    { key: 'rating', label: t('shipping.rating') },
    { key: 'shipments_count', label: t('nav.shipping'), class: 'text-end' },
    { key: 'is_active', label: t('common.status') },
];

const form = useForm({
    name: '',
    phone: '',
    email: '',
    contact_person: '',
    address: '',
    rating: 0,
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
} = useResourceForm(form, {
    resource: 'logistics',
    only: ['name', 'phone', 'email', 'contact_person', 'address', 'rating', 'notes', 'is_active'],
});
</script>

<template>
    <AppLayout :title="t('nav.logistics')">
        <Head :title="t('nav.logistics')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">{{ t('nav.logistics') }}</h1>
                <UButton v-if="canManage" :label="t('common.create')" icon="i-heroicons-plus" @click="openCreate()" />
            </div>

            <UCard>
                <DataTable :headers="headers" :rows="companies" :query="filters" :actions="canManage">
                    <template #toolbar>
                        <UInput
                            v-model="filters.search"
                            icon="i-heroicons-magnifying-glass"
                            :placeholder="t('common.search')"
                            class="w-full sm:max-w-xs"
                        />
                    </template>

                    <template #cell-rating="{ row }">
                        <div class="flex">
                            <UIcon
                                v-for="i in 5"
                                :key="i"
                                name="i-heroicons-star-solid"
                                class="size-4"
                                :class="i <= row.rating ? 'text-warning' : 'text-dimmed/40'"
                            />
                        </div>
                    </template>
                    <template #cell-shipments_count="{ value }">
                        <span class="tabular-nums text-muted">{{ value.toLocaleString() }}</span>
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

        <FormModal v-model:open="modalOpen" :title="editingId ? t('common.edit') : t('common.create')" width="sm:max-w-xl">
            <div class="grid gap-4">
                <UFormField :label="t('fields.name')" :error="form.errors.name">
                    <UInput v-model="form.name" class="w-full" />
                </UFormField>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('fields.phone')" :error="form.errors.phone">
                        <UInput v-model="form.phone" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('fields.email')" :error="form.errors.email">
                        <UInput v-model="form.email" class="w-full" />
                    </UFormField>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField :label="t('shipping.contact_person')" :error="form.errors.contact_person">
                        <UInput v-model="form.contact_person" class="w-full" />
                    </UFormField>
                    <UFormField :label="t('shipping.rating')" :error="form.errors.rating">
                        <UInput v-model="form.rating" type="number" min="0" max="5" class="w-full" />
                    </UFormField>
                </div>
                <UFormField :label="t('fields.address')" :error="form.errors.address">
                    <UTextarea v-model="form.address" :rows="2" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.notes')" :error="form.errors.notes">
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
    </AppLayout>
</template>
