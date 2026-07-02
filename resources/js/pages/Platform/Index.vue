<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import StatCard from '@/components/StatCard.vue';
import DataTable from '@/components/DataTable.vue';
import { useTrans } from '@/composables/useTrans';

defineProps({
    summary: { type: Object, required: true },
    tenants: { type: Array, default: () => [] },
    health: { type: Object, required: true },
});

const { t } = useTrans();
</script>

<template>
    <AppLayout :title="t('nav.platform') || 'Platform'">
        <Head title="Platform" />

        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <StatCard :title="t('platform.total_tenants')" :value="summary.tenants" icon="i-heroicons-building-office-2" />
                <StatCard :title="t('platform.active_tenants')" :value="summary.active_tenants" icon="i-heroicons-check-badge" icon-class="text-success" />
                <StatCard :title="t('platform.total_branches')" :value="summary.branches" icon="i-heroicons-building-storefront" />
                <StatCard :title="t('platform.total_users')" :value="summary.users" icon="i-heroicons-user-group" />
                <StatCard :title="t('platform.new_tenants_30d')" :value="summary.recent_tenants" icon="i-heroicons-sparkles" icon-class="text-primary" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <UCard class="lg:col-span-2">
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold">{{ t('platform.tenant_list') }}</h3>
                            <UButton
                                :label="t('platform.add_tenant')"
                                icon="i-heroicons-plus"
                                size="sm"
                                :to="route('platform.tenants')"
                            />
                        </div>
                    </template>

                    <DataTable
                        :headers="[
                            { key: 'name', label: t('fields.name') },
                            { key: 'branches', label: t('nav.branches'), class: 'text-end' },
                            { key: 'users', label: t('nav.users'), class: 'text-end' },
                            { key: 'is_active', label: t('common.status') },
                        ]"
                        :rows="tenants"
                        striped
                    >
                        <template #cell-is_active="{ row }">
                            <UBadge
                                :color="row.is_active ? 'success' : 'neutral'"
                                variant="subtle"
                                :label="row.is_active ? t('common.active') : t('common.inactive')"
                            />
                        </template>
                    </DataTable>
                </UCard>

                <UCard>
                    <template #header>
                        <h3 class="font-semibold">{{ t('platform.system_health') }}</h3>
                    </template>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ t('platform.database') }}</dt>
                            <dd>
                                <UBadge
                                    :color="health.db ? 'success' : 'error'"
                                    variant="subtle"
                                    :label="health.db ? t('platform.healthy') : t('platform.unhealthy')"
                                />
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ t('platform.queue') }}</dt>
                            <dd class="font-medium">{{ health.queue }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">{{ t('platform.cache') }}</dt>
                            <dd class="font-medium">{{ health.cache }}</dd>
                        </div>
                    </dl>
                </UCard>
            </div>
        </div>
    </AppLayout>
</template>
