<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import StatCard from '@/components/StatCard.vue';
import DataTable from '@/components/DataTable.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import StatusBadgeCell from '@/components/StatusBadgeCell.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableColumns } from '@/composables/useTableColumns';
import { numericHeader, textHeader } from '@/utils/tableHeaders';

const props = defineProps({
    summary: { type: Object, required: true },
    tenants: { type: Array, default: () => [] },
    health: { type: Object, required: true },
});

const { t } = useTrans();

const headers = [
    textHeader('name', t('fields.name')),
    numericHeader('branches', t('nav.branches')),
    numericHeader('users', t('nav.users')),
    textHeader('is_active', t('common.status')),
];

const { visibleHeaders, columnOptions, toggle: toggleColumn } = useTableColumns('platform.index.tenants', headers);
const printOpen = ref(false);
const tableFilters = ref({ search: '' });

const printRows = computed(() =>
    props.tenants.map((row) => ({
        ...row,
        is_active: row.is_active ? t('common.active') : t('common.inactive'),
    })),
);
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

                    <DataTable :headers="visibleHeaders" :rows="tenants" striped>
                        <template #toolbar>
                            <TableToolbar
                                :filters="tableFilters"
                                :column-options="columnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="toggleColumn"
                                @print="printOpen = true"
                            />
                        </template>

                        <template #cell-is_active="{ row }">
                            <StatusBadgeCell
                                :active="row.is_active"
                                :active-label="t('common.active')"
                                :inactive-label="t('common.inactive')"
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

        <TablePrintModal
            v-model:open="printOpen"
            :title="t('platform.tenant_list')"
            :headers="visibleHeaders"
            :rows="printRows"
        />
    </AppLayout>
</template>
