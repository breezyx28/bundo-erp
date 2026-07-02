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
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', type: '' }) },
    canManage: { type: Boolean, default: false },
});

const { t } = useTrans();
const { filters } = useTableFilters('customers.index', {
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
});

const headers = [
    { key: 'name', label: t('fields.name') },
    { key: 'phone', label: t('fields.phone') },
    { key: 'type', label: t('fields.type') },
    { key: 'balance', label: t('fields.balance') },
    { key: 'badges', label: t('fields.badges') },
];

const typeItems = computed(() => [
    { label: t('common.all'), value: '' },
    { label: t('fields.retail'), value: 'retail' },
    { label: t('fields.wholesale'), value: 'wholesale' },
]);

const typeFormItems = [
    { label: t('fields.retail'), value: 'retail' },
    { label: t('fields.wholesale'), value: 'wholesale' },
];

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    type: 'retail',
    credit_limit: 0,
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
} = useResourceForm(form, { resource: 'customers' });
</script>

<template>
    <AppLayout :title="t('nav.customers')">
        <Head :title="t('nav.customers')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.customers') }}
                </h1>
                <UButton
                    v-if="canManage"
                    :label="t('common.create')"
                    icon="i-heroicons-plus"
                    @click="openCreate()"
                />
            </div>

            <UCard>
                <DataTable :headers="headers" :rows="customers" :query="filters" striped :actions="canManage">
                    <template #toolbar>
                        <USelectMenu
                            v-model="filters.type"
                            :items="typeItems"
                            value-key="value"
                            class="w-full sm:w-40"
                        />
                        <UInput
                            v-model="filters.search"
                            icon="i-heroicons-magnifying-glass"
                            :placeholder="t('common.search')"
                            class="w-full sm:max-w-xs"
                        />
                    </template>

                    <template #cell-type="{ row }">
                        <UBadge color="neutral" variant="subtle" :label="t('fields.' + row.type)" />
                    </template>

                    <template #cell-balance="{ value }">
                        <span class="font-medium tabular-nums">{{ value }}</span>
                    </template>

                    <template #cell-badges="{ row }">
                        <div class="flex flex-wrap gap-1">
                            <UBadge
                                v-for="(badge, index) in row.badges"
                                :key="index"
                                :color="badge.color"
                                variant="subtle"
                                size="sm"
                                :label="badge.label"
                            />
                        </div>
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
            width="sm:max-w-2xl"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <UFormField :label="t('fields.name')" :error="form.errors.name">
                    <UInput v-model="form.name" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.phone')" :error="form.errors.phone">
                    <UInput v-model="form.phone" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.email')" :error="form.errors.email">
                    <UInput v-model="form.email" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.type')" :error="form.errors.type">
                    <USelectMenu
                        v-model="form.type"
                        :items="typeFormItems"
                        value-key="value"
                        class="w-full"
                    />
                </UFormField>
                <UFormField :label="t('fields.credit_limit')" :error="form.errors.credit_limit">
                    <UInput v-model="form.credit_limit" type="number" step="0.01" class="w-full" />
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
    </AppLayout>
</template>
