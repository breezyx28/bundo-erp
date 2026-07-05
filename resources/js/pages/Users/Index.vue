<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';
import { useTableColumns } from '@/composables/useTableColumns';
import { useResourceForm } from '@/composables/useResourceForm';

const props = defineProps({
    users: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '' }) },
    sortOptions: { type: Array, default: () => [] },
});

const { t } = useTrans();
const { filters, toggleSort } = useTableFilters('users.index', {
    search: props.filters.search ?? '',
    sort: props.filters.sort ?? '',
    direction: props.filters.direction ?? 'desc',
});

const headers = [
    { key: 'name', label: t('fields.name'), sortable: true },
    { key: 'email', label: t('auth.email'), sortable: true },
    { key: 'is_active', label: t('common.status'), sortable: true },
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('users.index', headers);
const printOpen = ref(false);
const printRows = computed(() =>
    (props.users.data ?? []).map((row) => ({
        ...row,
        is_active: row.is_active ? t('common.active') : t('common.inactive'),
    })),
);

const roleItems = computed(() => props.roles.map((role) => ({ label: role, value: role })));
const branchItems = computed(() =>
    props.branches.map((branch) => ({ label: branch.name, value: branch.id })),
);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: 'salesperson',
    branchIds: [],
    is_active: true,
});

const {
    modalOpen,
    editingId,
    openCreate,
    openEdit,
    submit,
} = useResourceForm(form, {
    resource: 'users',
    draftKey: 'users',
    draftLabel: t('nav.users'),
    only: ['name', 'email', 'phone', 'role', 'branchIds', 'is_active'],
});
</script>

<template>
    <AppLayout :title="t('nav.users')">
        <Head :title="t('nav.users')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('nav.users') }}
                </h1>
                <UButton :label="t('users.add')" icon="i-heroicons-plus" @click="openCreate()" />
            </div>

            <UCard>
                <DataTable
                    :headers="visibleHeaders"
                    :rows="users"
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
                        <UButton icon="i-heroicons-pencil-square" color="neutral" variant="ghost" size="sm" @click="openEdit(row)" />
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal
            v-model:open="modalOpen"
            :title="editingId ? t('users.edit') : t('users.add')"
            width="sm:max-w-2xl"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <UFormField :label="t('fields.name')" :error="form.errors.name">
                    <UInput v-model="form.name" class="w-full" />
                </UFormField>
                <UFormField :label="t('auth.email')" :error="form.errors.email">
                    <UInput v-model="form.email" type="email" class="w-full" />
                </UFormField>
                <UFormField :label="t('fields.phone')" :error="form.errors.phone">
                    <UInput v-model="form.phone" class="w-full" />
                </UFormField>
                <UFormField :label="t('users.role')" :error="form.errors.role">
                    <USelectMenu v-model="form.role" :items="roleItems" value-key="value" class="w-full" />
                </UFormField>
                <UFormField :label="t('users.password')" :error="form.errors.password">
                    <UInput v-model="form.password" type="password" class="w-full" />
                </UFormField>
                <UFormField :label="t('users.password_confirm')">
                    <UInput v-model="form.password_confirmation" type="password" class="w-full" />
                </UFormField>
                <div class="md:col-span-2">
                    <p class="mb-2 text-sm font-medium">{{ t('users.branches') }}</p>
                    <UCheckboxGroup
                        v-model="form.branchIds"
                        :items="branchItems"
                        value-key="value"
                        orientation="horizontal"
                        class="flex flex-wrap gap-3"
                    />
                    <p v-if="form.errors.branchIds" class="mt-1 text-xs text-error">
                        {{ form.errors.branchIds }}
                    </p>
                </div>
                <UCheckbox v-model="form.is_active" :label="t('common.active')" />
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('nav.users')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
