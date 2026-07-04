<script setup>
import { computed, ref } from 'vue';
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
    companies: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();

const headers = [
    textHeader('name', t('fields.name')),
    textHeader('contact_person', t('shipping.contact_person')),
    textHeader('rating', t('shipping.rating')),
    numericHeader('shipments_count', t('nav.shipping')),
    textHeader('is_active', t('common.status')),
];

const {
    filters,
    visibleHeaders,
    columnOptions,
    toggleColumn,
    printOpen,
} = useIndexTable('logistics.index', headers, {
    search: props.filters.search ?? '',
});

const printRows = computed(() =>
    (props.companies.data ?? []).map((row) => ({
        ...row,
        is_active: row.is_active ? t('common.active') : t('common.inactive'),
    })),
);

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
                <DataTable :headers="visibleHeaders" :rows="companies" :query="filters" :actions="canManage">
                    <template #toolbar>
                        <TableToolbar
                            :filters="filters"
                            :column-options="columnOptions"
                            :date-range="false"
                            @toggle-column="toggleColumn"
                            @print="printOpen = true"
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
                        <span class="text-muted">{{ value.toLocaleString() }}</span>
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

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.logistics')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
