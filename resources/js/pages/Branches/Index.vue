<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';

const props = defineProps({
    branches: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const { t } = useTrans();
const { filters } = useTableFilters('branches.index', {
    search: props.filters.search ?? '',
});

const headers = [
    { key: 'name', label: t('fields.name') },
    { key: 'code', label: t('branches.code') },
    { key: 'phone', label: t('branches.phone') },
    { key: 'is_active', label: t('common.status') },
];

const modalOpen = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    code: '',
    address: '',
    phone: '',
    email: '',
    primary_color: '#39C6A0',
    secondary_color: '#228C70',
    is_active: true,
});

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    form.clearErrors();
    form.name = row.name;
    form.code = row.code;
    form.address = row.address ?? '';
    form.phone = row.phone ?? '';
    form.email = row.email ?? '';
    form.primary_color = row.primary_color ?? '#39C6A0';
    form.secondary_color = row.secondary_color ?? '#228C70';
    form.is_active = row.is_active;
    modalOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
        },
    };

    if (editingId.value) {
        form.put(route('branches.update', editingId.value), options);
    } else {
        form.post(route('branches.store'), options);
    }
}
</script>

<template>
    <AppLayout :title="t('nav.branches')">
        <Head :title="t('nav.branches')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-highlighted">
                        {{ t('nav.branches') }}
                    </h1>
                </div>
                <UButton
                    :label="t('branches.add')"
                    icon="i-heroicons-plus"
                    @click="openCreate"
                />
            </div>

            <UCard>
                <DataTable
                    :headers="headers"
                    :rows="branches"
                    :query="filters"
                    striped
                    actions
                >
                    <template #toolbar>
                        <UInput
                            v-model="filters.search"
                            icon="i-heroicons-magnifying-glass"
                            :placeholder="t('common.search')"
                            class="w-full sm:max-w-xs"
                        />
                    </template>

                    <template #cell-is_active="{ row }">
                        <UBadge
                            :color="row.is_active ? 'success' : 'neutral'"
                            variant="subtle"
                            :label="
                                row.is_active
                                    ? t('common.active')
                                    : t('common.inactive')
                            "
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
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal
            v-model:open="modalOpen"
            :title="editingId ? t('branches.edit') : t('branches.add')"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <UFormField :label="t('fields.name')" :error="form.errors.name">
                    <UInput v-model="form.name" class="w-full" />
                </UFormField>
                <UFormField :label="t('branches.code')" :error="form.errors.code">
                    <UInput v-model="form.code" class="w-full" />
                </UFormField>
                <UFormField :label="t('branches.phone')" :error="form.errors.phone">
                    <UInput v-model="form.phone" class="w-full" />
                </UFormField>
                <UFormField :label="t('branches.email')" :error="form.errors.email">
                    <UInput v-model="form.email" type="email" class="w-full" />
                </UFormField>
                <UFormField
                    :label="t('branches.address')"
                    :error="form.errors.address"
                    class="md:col-span-2"
                >
                    <UTextarea v-model="form.address" class="w-full" />
                </UFormField>
                <UFormField
                    :label="t('settings.primary_color')"
                    :error="form.errors.primary_color"
                >
                    <UInput v-model="form.primary_color" type="color" class="w-full" />
                </UFormField>
                <UFormField
                    :label="t('settings.secondary_color')"
                    :error="form.errors.secondary_color"
                >
                    <UInput
                        v-model="form.secondary_color"
                        type="color"
                        class="w-full"
                    />
                </UFormField>
                <UCheckbox
                    v-model="form.is_active"
                    :label="t('common.active')"
                />
            </div>

            <template #footer="{ close }">
                <UButton
                    color="neutral"
                    variant="ghost"
                    :label="t('common.cancel')"
                    @click="close"
                />
                <UButton
                    :label="t('common.save')"
                    :loading="form.processing"
                    @click="submit"
                />
            </template>
        </FormModal>
    </AppLayout>
</template>
