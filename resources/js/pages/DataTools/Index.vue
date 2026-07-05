<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import TableToolbar from '@/components/TableToolbar.vue';
import TablePrintModal from '@/components/TablePrintModal.vue';
import { useTrans } from '@/composables/useTrans';
import { useTableColumns } from '@/composables/useTableColumns';
import { numericHeader, textHeader } from '@/utils/tableHeaders';
import { useFormDraft, useDraftQueryRestore } from '@/composables/useFormDraft';

const props = defineProps({
    exportTypes: { type: Array, default: () => [] },
    importTypes: { type: Array, default: () => [] },
    imports: { type: Array, default: () => [] },
    backups: { type: Array, default: () => [] },
});

const { t } = useTrans();

const tabs = computed(() => [
    { value: 'export', label: t('datatools.tab_export'), icon: 'i-heroicons-arrow-down-tray' },
    { value: 'import', label: t('datatools.tab_import'), icon: 'i-heroicons-arrow-up-tray' },
    { value: 'backups', label: t('datatools.tab_backups'), icon: 'i-heroicons-server-stack' },
]);
const tab = ref('export');

const exportType = ref(props.exportTypes[0]?.value ?? '');
function exportUrl(format) {
    return route('data.export', { type: exportType.value, format });
}

const importForm = useForm({ importType: props.importTypes[0]?.value ?? '', file: null });
const importDraft = useFormDraft({
    key: 'datatools.import',
    label: t('datatools.tab_import'),
    routeName: 'data-tools.index',
    form: importForm,
    active: computed(() => tab.value === 'import'),
    getSnapshot: () => ({ importType: importForm.importType }),
    onApply: (data) => {
        importForm.importType = data.importType ?? props.importTypes[0]?.value ?? '';
    },
});

useDraftQueryRestore('datatools', () => {
    tab.value = 'import';
    importDraft.restoreDraft(true);
});

function submitImport() {
    importForm.post(route('data-tools.import'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importDraft.clearDraft();
            importForm.reset('file');
        },
    });
}
function onImportFile(e) {
    importForm.file = e.target.files?.[0] ?? null;
}

const errorRows = ref(null);
const errorModal = ref(false);
function showErrors(row) {
    errorRows.value = row;
    errorModal.value = true;
}

const backupForm = useForm({ only_database: false });
function createBackup(onlyDb) {
    backupForm.only_database = onlyDb;
    backupForm.post(route('data-tools.backup'), { preserveScroll: true });
}

const deleteTarget = ref(null);
const deleteOpen = ref(false);
function confirmDelete(name) {
    deleteTarget.value = name;
    deleteOpen.value = true;
}
function doDelete() {
    router.delete(route('data-tools.backup.delete'), {
        data: { name: deleteTarget.value },
        preserveScroll: true,
        onFinish: () => { deleteOpen.value = false; },
    });
}

function fmtSize(bytes) {
    const b = Number(bytes) || 0;
    if (b >= 1073741824) return (b / 1073741824).toFixed(1) + ' GB';
    if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
    if (b >= 1024) return (b / 1024).toFixed(1) + ' KB';
    return b + ' B';
}

const importHeaders = [
    textHeader('type_label', t('datatools.dataset')),
    numericHeader('imported_rows', t('datatools.imported')),
    numericHeader('failed_rows', t('datatools.failed')),
    textHeader('created_at', t('datatools.date')),
];

const backupHeaders = [
    textHeader('name', t('datatools.name')),
    numericHeader('size', t('datatools.size')),
    textHeader('date', t('datatools.date')),
];

const {
    visibleHeaders: importVisibleHeaders,
    columnOptions: importColumnOptions,
    toggle: toggleImportColumn,
} = useTableColumns('datatools.imports', importHeaders);
const {
    visibleHeaders: backupVisibleHeaders,
    columnOptions: backupColumnOptions,
    toggle: toggleBackupColumn,
} = useTableColumns('datatools.backups', backupHeaders);

const importPrintOpen = ref(false);
const backupPrintOpen = ref(false);
const tableFilters = ref({ search: '' });

const importPrintRows = computed(() => props.imports ?? []);
const backupPrintRows = computed(() =>
    (props.backups ?? []).map((row) => ({
        ...row,
        size: fmtSize(row.size),
    })),
);
</script>

<template>
    <AppLayout :title="t('datatools.title')">
        <Head :title="t('datatools.title')" />

        <div class="space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-highlighted">{{ t('datatools.title') }}</h1>
                <p class="text-sm text-muted">{{ t('datatools.subtitle') }}</p>
            </div>

            <UTabs v-model="tab" :items="tabs" class="w-full" />

            <!-- Export -->
            <UCard v-if="tab === 'export'">
                <template #header>
                    <div>
                        <span class="font-medium">{{ t('datatools.export_title') }}</span>
                        <p class="text-sm text-muted">{{ t('datatools.export_hint') }}</p>
                    </div>
                </template>
                <div class="flex flex-wrap items-end gap-3">
                    <UFormField :label="t('datatools.dataset')" class="w-64">
                        <USelectMenu v-model="exportType" :items="exportTypes" value-key="value" label-key="label" class="w-full" />
                    </UFormField>
                    <UButton :label="t('datatools.export_csv')" icon="i-heroicons-table-cells" color="neutral" variant="outline" :to="exportUrl('csv')" as="a" external />
                    <UButton :label="t('datatools.export_excel')" icon="i-heroicons-document-arrow-down" :to="exportUrl('xlsx')" as="a" external />
                </div>
            </UCard>

            <!-- Import -->
            <div v-else-if="tab === 'import'" class="grid gap-6 lg:grid-cols-2">
                <UCard>
                    <template #header>
                        <div>
                            <span class="font-medium">{{ t('datatools.import_title') }}</span>
                            <p class="text-sm text-muted">{{ t('datatools.import_hint') }}</p>
                        </div>
                    </template>
                    <form class="space-y-4" @submit.prevent="submitImport">
                        <UFormField :label="t('datatools.dataset')" :error="importForm.errors.importType">
                            <USelectMenu v-model="importForm.importType" :items="importTypes" value-key="value" label-key="label" class="w-full" />
                        </UFormField>
                        <UFormField :label="t('datatools.file')" :error="importForm.errors.file">
                            <input type="file" accept=".csv,.txt,.xlsx,.xls" class="block w-full text-sm text-muted file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-white" @change="onImportFile" />
                        </UFormField>
                        <div class="flex items-center gap-3">
                            <UButton type="submit" :label="t('datatools.run_import')" icon="i-heroicons-play" :loading="importForm.processing" :disabled="!importForm.file" />
                            <UButton :label="t('datatools.download_template')" color="neutral" variant="ghost" :to="route('data.template', { type: importForm.importType })" as="a" external />
                        </div>
                    </form>
                </UCard>

                <UCard>
                    <template #header><span class="font-medium">{{ t('datatools.history') }}</span></template>
                    <DataTable :headers="importVisibleHeaders" :rows="imports" actions>
                        <template #toolbar>
                            <TableToolbar
                                :filters="tableFilters"
                                :column-options="importColumnOptions"
                                :date-range="false"
                                :search="false"
                                @toggle-column="toggleImportColumn"
                                @print="importPrintOpen = true"
                            />
                        </template>

                        <template #cell-imported_rows="{ row }">
                            <span class="text-success">{{ row.imported_rows }}</span>
                        </template>
                        <template #cell-failed_rows="{ row }">
                            <span :class="row.failed_rows > 0 ? 'text-error' : ''">{{ row.failed_rows }}</span>
                        </template>
                        <template #actions="{ row }">
                            <UButton v-if="row.errors && row.errors.length" :label="t('datatools.view_errors')" color="error" variant="ghost" size="xs" @click="showErrors(row)" />
                        </template>
                        <template #empty>{{ t('datatools.no_history') }}</template>
                    </DataTable>
                </UCard>
            </div>

            <!-- Backups -->
            <UCard v-else>
                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <span class="font-medium">{{ t('datatools.backups_title') }}</span>
                            <p class="text-sm text-muted">{{ t('datatools.backups_hint') }}</p>
                        </div>
                        <div class="flex gap-2">
                            <UButton :label="t('datatools.create_db')" icon="i-heroicons-circle-stack" color="neutral" variant="soft" size="sm" :loading="backupForm.processing" @click="createBackup(true)" />
                            <UButton :label="t('datatools.create_full')" icon="i-heroicons-server-stack" size="sm" :loading="backupForm.processing" @click="createBackup(false)" />
                        </div>
                    </div>
                </template>
                <DataTable :headers="backupVisibleHeaders" :rows="backups" actions>
                    <template #toolbar>
                        <TableToolbar
                            :filters="tableFilters"
                            :column-options="backupColumnOptions"
                            :date-range="false"
                            :search="false"
                            @toggle-column="toggleBackupColumn"
                            @print="backupPrintOpen = true"
                        />
                    </template>

                    <template #cell-name="{ row }"><span class="font-mono text-sm">{{ row.name }}</span></template>
                    <template #cell-size="{ row }">{{ fmtSize(row.size) }}</template>
                    <template #actions="{ row }">
                        <UButton icon="i-heroicons-arrow-down-tray" color="neutral" variant="ghost" size="xs" :to="route('data-tools.backup.download', { name: row.name })" as="a" external />
                        <UButton icon="i-heroicons-trash" color="error" variant="ghost" size="xs" @click="confirmDelete(row.name)" />
                    </template>
                    <template #empty>{{ t('datatools.no_backups') }}</template>
                </DataTable>
            </UCard>
        </div>

        <UModal v-model:open="errorModal" :title="t('datatools.errors')">
            <template #body>
                <div class="max-h-96 space-y-2 overflow-y-auto">
                    <div v-for="(err, i) in (errorRows?.errors ?? [])" :key="i" class="rounded-md border border-error/30 bg-error/5 p-2 text-sm">
                        <span class="font-medium">{{ t('datatools.row') }} {{ err.row ?? '?' }}:</span>
                        <span class="text-toned">{{ Array.isArray(err.messages) ? err.messages.join(' · ') : err.messages }}</span>
                    </div>
                </div>
            </template>
        </UModal>

        <ConfirmModal
            v-model="deleteOpen"
            :title="t('datatools.backups_title')"
            :message="t('common.confirm_delete')"
            :confirm-label="t('common.delete')"
            @confirm="doDelete"
        />

        <TablePrintModal
            v-model:open="importPrintOpen"
            :title="t('datatools.history')"
            :headers="importVisibleHeaders"
            :rows="importPrintRows"
        />
        <TablePrintModal
            v-model:open="backupPrintOpen"
            :title="t('datatools.backups_title')"
            :headers="backupVisibleHeaders"
            :rows="backupPrintRows"
        />
    </AppLayout>
</template>
