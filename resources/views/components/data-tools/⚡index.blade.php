<?php

use App\Models\ImportLog;
use App\Services\Backup\BackupService;
use App\Services\DataTransfer\ExportService;
use App\Services\DataTransfer\ImportService;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Traits\UiToast;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Layout('components.layouts.app')] #[Title('Data Tools')] class extends Component
{
    use ConfirmsDeletion, UiToast, WithFileUploads;

    public string $tab = 'export';

    public string $exportType = 'products';

    public string $importType = 'products';

    public $file;

    public function exportUrl(string $format): string
    {
        return route('data.export', ['type' => $this->exportType, 'format' => $format]);
    }

    public function templateUrl(): string
    {
        return route('data.template', ['type' => $this->importType]);
    }

    public function runImport(ImportService $service): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'importType' => 'required|in:'.implode(',', ImportService::TYPES),
        ]);

        $log = $service->run($this->importType, $this->file->getRealPath());

        $this->reset('file');

        $this->success(__('datatools.import_done', ['imported' => $log->imported_rows, 'failed' => $log->failed_rows]));
    }

    public function createBackup(BackupService $service, bool $onlyDatabase = false): void
    {
        try {
            $code = $service->create($onlyDatabase);
            $code === 0
                ? $this->success(__('datatools.backup_started'))
                : $this->error(__('datatools.backup_failed'));
        } catch (\Throwable $e) {
            $this->error(__('datatools.backup_failed'));
        }
    }

    public function downloadBackup(BackupService $service, string $name): StreamedResponse
    {
        return $service->download($name);
    }

    public function deleteConfirmed(BackupService $service): void
    {
        if ($this->deleteId === null) {
            return;
        }

        $service->delete((string) $this->deleteId);
        $this->cancelDelete();
        $this->success(__('datatools.backup_deleted'));
    }

    public function with(BackupService $backups): array
    {
        return [
            'exportTypes' => array_map(fn ($t) => ['id' => $t, 'name' => __('datatools.ds_'.$t)], ExportService::TYPES),
            'importTypes' => array_map(fn ($t) => ['id' => $t, 'name' => __('datatools.ds_'.$t)], ImportService::TYPES),
            'imports' => ImportLog::query()->with('user:id,name')->latest()->limit(10)->get(),
            'backups' => $backups->list(),
            'importHeaders' => [
                ['key' => 'type', 'label' => __('datatools.dataset')],
                ['key' => 'imported_rows', 'label' => __('datatools.imported'), 'class' => 'text-end'],
                ['key' => 'failed_rows', 'label' => __('datatools.failed'), 'class' => 'text-end'],
                ['key' => 'created_at', 'label' => __('datatools.date')],
            ],
            'backupHeaders' => [
                ['key' => 'name', 'label' => __('datatools.name')],
                ['key' => 'size', 'label' => __('datatools.size'), 'class' => 'text-end'],
                ['key' => 'date', 'label' => __('datatools.date')],
            ],
        ];
    }

    public function fmtSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}; ?>

<div class="space-y-6">
    <x-ui.header :title="__('datatools.title')" :subtitle="__('datatools.subtitle')" separator />

    <x-ui.tabs wire:model="tab">
        {{-- Export --}}
        <x-ui.tab name="export" :label="__('datatools.tab_export')" icon="o-arrow-down-tray">
            <x-ui.card :title="__('datatools.export_title')" :subtitle="__('datatools.export_hint')">
                <div class="flex flex-wrap items-end gap-3">
                    <x-ui.select wire:model.live="exportType" :options="$exportTypes" option-value="id" option-label="name"
                        :label="__('datatools.dataset')" class="w-64" />
                    <x-ui.button :label="__('datatools.export_csv')" icon="o-table-cells" :link="$this->exportUrl('csv')" external class="btn-outline" />
                    <x-ui.button :label="__('datatools.export_excel')" icon="o-document-arrow-down" :link="$this->exportUrl('xlsx')" external class="btn-primary" />
                </div>
            </x-ui.card>
        </x-ui.tab>

        {{-- Import --}}
        <x-ui.tab name="import" :label="__('datatools.tab_import')" icon="o-arrow-up-tray">
            <div class="grid gap-4 lg:grid-cols-2">
                <x-ui.card :title="__('datatools.import_title')" :subtitle="__('datatools.import_hint')">
                    <div class="space-y-4">
                        <x-ui.select wire:model.live="importType" :options="$importTypes" option-value="id" option-label="name"
                            :label="__('datatools.dataset')" />
                        <x-ui.file wire:model="file" :label="__('datatools.file')" accept=".csv,.txt,.xlsx,.xls" />
                        <div class="flex items-center gap-3">
                            <x-ui.button :label="__('datatools.run_import')" icon="o-play" wire:click="runImport" spinner="runImport" class="btn-primary" />
                            <x-ui.button :label="__('datatools.download_template')" icon="o-document-text" :link="$this->templateUrl()" external class="btn-text btn-circle btn-sm" />
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card :title="__('datatools.history')">
                    <x-ui.table :headers="$importHeaders" :rows="$imports" show-empty-text :empty-text="__('datatools.no_history')">
                        @scope('cell_type', $row)
                            {{ __('datatools.ds_' . $row->type) }}
                        @endscope
                        @scope('cell_imported_rows', $row)
                            <span class="text-end tabular-nums text-success">{{ $row->imported_rows }}</span>
                        @endscope
                        @scope('cell_failed_rows', $row)
                            <span class="text-end tabular-nums {{ $row->failed_rows > 0 ? 'text-error' : '' }}">{{ $row->failed_rows }}</span>
                        @endscope
                        @scope('cell_created_at', $row)
                            <span class="text-sm text-base-content/60">{{ $row->created_at?->format('Y-m-d H:i') }}</span>
                        @endscope
                        @scope('actions', $row)
                            @if ($row->failed_rows > 0)
                                <x-ui.button icon="o-exclamation-circle" class="btn-text btn-circle btn-xs text-error"
                                    tooltip="{{ __('datatools.view_errors') }}"
                                    @click="document.getElementById('errors-{{ $row->id }}')?.showModal()" />
                                <x-ui.modal id="errors-{{ $row->id }}" :title="__('datatools.errors')">
                                    <div class="max-h-80 space-y-2 overflow-y-auto text-sm">
                                        @foreach (($row->errors ?? []) as $err)
                                            <div class="rounded bg-base-200 p-2">
                                                <span class="font-medium">{{ __('datatools.row') }} {{ $err['row'] ?? '?' }}:</span>
                                                {{ implode(' · ', $err['messages'] ?? []) }}
                                            </div>
                                        @endforeach
                                    </div>
                                </x-ui.modal>
                            @endif
                        @endscope
                    </x-ui.table>
                </x-ui.card>
            </div>
        </x-ui.tab>

        {{-- Backups --}}
        <x-ui.tab name="backups" :label="__('datatools.tab_backups')" icon="o-server-stack">
            <x-ui.card :title="__('datatools.backups_title')" :subtitle="__('datatools.backups_hint')">
                <x-slot:menu>
                    <x-ui.button :label="__('datatools.create_db')" icon="o-circle-stack" wire:click="createBackup(true)" spinner class="btn-text btn-circle btn-sm" />
                    <x-ui.button :label="__('datatools.create_full')" icon="o-server-stack" wire:click="createBackup(false)" spinner class="btn-primary btn-sm" />
                </x-slot:menu>

                <x-ui.table :headers="$backupHeaders" :rows="$backups" show-empty-text :empty-text="__('datatools.no_backups')">
                    @scope('cell_name', $row)
                        <span class="font-mono text-sm">{{ $row['name'] }}</span>
                    @endscope
                    @scope('cell_size', $row)
                        <span class="text-end tabular-nums">{{ $this->fmtSize($row['size']) }}</span>
                    @endscope
                    @scope('cell_date', $row)
                        <span class="text-sm text-base-content/60">{{ $row['date'] }}</span>
                    @endscope
                    @scope('actions', $row)
                        <x-ui.button icon="o-arrow-down-tray" wire:click="downloadBackup('{{ $row['name'] }}')" class="btn-text btn-circle btn-xs" tooltip="{{ __('datatools.download') }}" />
                        <x-ui.button icon="o-trash" wire:click.stop="confirmDelete(@js($row['name']))" class="btn-text btn-circle btn-xs text-error" />
                    @endscope
                </x-ui.table>
            </x-ui.card>
        </x-ui.tab>
    </x-ui.tabs>

    <x-ui.delete-confirm-modal />
</div>
