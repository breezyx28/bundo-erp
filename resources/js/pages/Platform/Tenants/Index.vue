<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import FormModal from '@/components/FormModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableFilters } from '@/composables/useTableFilters';

const props = defineProps({
    tenants: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '' }) },
    moduleCatalog: { type: Array, default: () => [] },
});

const { t } = useTrans();
const { filters } = useTableFilters('platform.tenants', {
    search: props.filters.search ?? '',
});

const headers = [
    { key: 'name', label: t('fields.name') },
    { key: 'domain', label: t('platform.domain') },
    { key: 'branches_count', label: t('nav.branches'), class: 'text-end' },
    { key: 'users_count', label: t('nav.users'), class: 'text-end' },
    { key: 'is_active', label: t('common.status') },
];

const localeItems = [
    { label: 'العربية', value: 'ar' },
    { label: 'English', value: 'en' },
];

const modalOpen = ref(false);
const editingId = ref(null);

function defaultModuleToggles() {
    return Object.fromEntries(props.moduleCatalog.map((m) => [m.key, m.enabled]));
}

const form = useForm({
    name: '',
    domain: '',
    primary_color: '#39C6A0',
    secondary_color: '#228C70',
    is_active: true,
    locale: 'ar',
    timezone: 'Africa/Khartoum',
    currency: 'SDG',
    exchange_rate: '600',
    branch_name: '',
    branch_code: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    moduleToggles: {},
    logo: null,
});

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.moduleToggles = defaultModuleToggles();
    modalOpen.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    form.reset();
    form.clearErrors();
    form.name = row.name;
    form.domain = row.domain ?? '';
    form.primary_color = row.primary_color ?? '#39C6A0';
    form.secondary_color = row.secondary_color ?? '#228C70';
    form.is_active = row.is_active;
    form.locale = row.locale ?? 'ar';
    form.timezone = row.timezone ?? 'Africa/Khartoum';
    form.moduleToggles = { ...row.moduleToggles };
    modalOpen.value = true;
}

function onLogoChange(event) {
    form.logo = event.target.files[0] ?? null;
}

function submit() {
    const withFile = form.logo instanceof File;
    const options = {
        preserveScroll: true,
        forceFormData: withFile,
        onSuccess: () => {
            modalOpen.value = false;
        },
    };

    const transform = (data) => {
        const payload = {
            ...data,
            is_active: data.is_active ? 1 : 0,
            moduleToggles: Object.fromEntries(
                Object.entries(data.moduleToggles).map(([key, value]) => [key, value ? 1 : 0]),
            ),
        };
        if (editingId.value) {
            payload._method = 'post';
        }
        return payload;
    };

    if (editingId.value) {
        form.transform(transform).post(route('platform.tenants.update', editingId.value), options);
    } else {
        form.transform(transform).post(route('platform.tenants.store'), options);
    }
}

function enterTenant(id) {
    router.post(route('platform.tenants.enter', id));
}

function toggleActive(id) {
    router.post(route('platform.tenants.toggle', id), {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('platform.tenants')">
        <Head :title="t('platform.tenants')" />

        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold text-highlighted">
                    {{ t('platform.tenants') }}
                </h1>
                <UButton :label="t('platform.add_tenant')" icon="i-heroicons-plus" @click="openCreate()" />
            </div>

            <UCard>
                <DataTable :headers="headers" :rows="tenants" :query="filters" striped actions>
                    <template #toolbar>
                        <UInput
                            v-model="filters.search"
                            icon="i-heroicons-magnifying-glass"
                            :placeholder="t('common.search')"
                            class="w-full sm:max-w-xs"
                        />
                    </template>

                    <template #cell-domain="{ value }">
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
                        <UButton icon="i-heroicons-arrow-right-end-on-rectangle" color="neutral" variant="ghost" size="sm" @click="enterTenant(row.id)" />
                        <UButton icon="i-heroicons-pencil-square" color="neutral" variant="ghost" size="sm" @click="openEdit(row)" />
                        <UButton :icon="row.is_active ? 'i-heroicons-pause' : 'i-heroicons-play'" color="neutral" variant="ghost" size="sm" @click="toggleActive(row.id)" />
                    </template>
                </DataTable>
            </UCard>
        </div>

        <FormModal
            v-model:open="modalOpen"
            :title="editingId ? t('platform.edit_tenant') : t('platform.add_tenant')"
            width="sm:max-w-3xl"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <UFormField :label="t('fields.name')" :error="form.errors.name">
                    <UInput v-model="form.name" class="w-full" />
                </UFormField>
                <UFormField :label="t('platform.domain')" :error="form.errors.domain">
                    <UInput v-model="form.domain" class="w-full" />
                </UFormField>
                <UFormField :label="t('settings.primary_color')" :error="form.errors.primary_color">
                    <UInput v-model="form.primary_color" type="color" class="w-full" />
                </UFormField>
                <UFormField :label="t('settings.secondary_color')" :error="form.errors.secondary_color">
                    <UInput v-model="form.secondary_color" type="color" class="w-full" />
                </UFormField>
                <UFormField :label="t('settings.locale')" :error="form.errors.locale">
                    <USelectMenu v-model="form.locale" :items="localeItems" value-key="value" class="w-full" />
                </UFormField>
                <UFormField :label="t('settings.timezone')" :error="form.errors.timezone">
                    <UInput v-model="form.timezone" class="w-full" />
                </UFormField>
                <UFormField :label="t('settings.logo')" :error="form.errors.logo">
                    <input
                        type="file"
                        accept="image/*"
                        class="block w-full text-sm text-muted file:me-3 file:rounded-md file:border-0 file:bg-elevated file:px-3 file:py-1.5 file:text-sm"
                        @change="onLogoChange"
                    />
                </UFormField>
                <UCheckbox v-model="form.is_active" :label="t('common.active')" />

                <template v-if="!editingId">
                    <div class="border-t border-default pt-4 md:col-span-2">
                        <p class="mb-3 text-sm font-semibold">{{ t('platform.first_branch') }}</p>
                        <div class="grid gap-4 md:grid-cols-2">
                            <UFormField :label="t('fields.name')" :error="form.errors.branch_name">
                                <UInput v-model="form.branch_name" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('platform.branch_code')" :error="form.errors.branch_code">
                                <UInput v-model="form.branch_code" class="w-full" />
                            </UFormField>
                        </div>
                    </div>
                    <div class="border-t border-default pt-4 md:col-span-2">
                        <p class="mb-3 text-sm font-semibold">{{ t('platform.admin_user') }}</p>
                        <div class="grid gap-4 md:grid-cols-2">
                            <UFormField :label="t('fields.name')" :error="form.errors.admin_name">
                                <UInput v-model="form.admin_name" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('auth.email')" :error="form.errors.admin_email">
                                <UInput v-model="form.admin_email" type="email" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('users.password')" :error="form.errors.admin_password">
                                <UInput v-model="form.admin_password" type="password" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('settings.default_currency')">
                                <UInput v-model="form.currency" class="w-full" />
                            </UFormField>
                            <UFormField :label="t('settings.exchange_rate')">
                                <UInput v-model="form.exchange_rate" type="number" step="0.01" class="w-full" />
                            </UFormField>
                        </div>
                    </div>
                </template>

                <div class="border-t border-default pt-4 md:col-span-2">
                    <p class="mb-3 text-sm font-semibold">{{ t('platform.modules') }}</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <UCheckbox
                            v-for="module in moduleCatalog"
                            :key="module.key"
                            v-model="form.moduleToggles[module.key]"
                            :label="module.name || module.key"
                        />
                    </div>
                </div>
            </div>

            <template #footer="{ close }">
                <UButton color="neutral" variant="ghost" :label="t('common.cancel')" @click="close" />
                <UButton :label="t('common.save')" :loading="form.processing" @click="submit()" />
            </template>
        </FormModal>
    </AppLayout>
</template>
