<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Models\ImportLog;
use App\Services\Backup\BackupService;
use App\Services\DataTransfer\ExportService;
use App\Services\DataTransfer\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataToolsController extends Controller
{
    use InteractsWithToast;

    public function index(BackupService $backups): Response
    {
        return Inertia::render('DataTools/Index', [
            'exportTypes' => array_map(fn ($t) => ['value' => $t, 'label' => __('datatools.ds_'.$t)], ExportService::TYPES),
            'importTypes' => array_map(fn ($t) => ['value' => $t, 'label' => __('datatools.ds_'.$t)], ImportService::TYPES),
            'imports' => ImportLog::query()->with('user:id,name')->latest()->limit(10)->get()
                ->map(fn (ImportLog $log) => [
                    'id' => $log->id,
                    'type' => $log->type,
                    'type_label' => __('datatools.ds_'.$log->type),
                    'imported_rows' => $log->imported_rows,
                    'failed_rows' => $log->failed_rows,
                    'created_at' => $log->created_at?->format('Y-m-d H:i'),
                    'errors' => $log->errors ?? [],
                ]),
            'backups' => collect($backups->list())->map(fn ($b) => [
                'name' => $b['name'],
                'size' => $b['size'],
                'date' => $b['date'],
            ])->all(),
        ]);
    }

    public function import(Request $request, ImportService $service): RedirectResponse
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'importType' => 'required|in:'.implode(',', ImportService::TYPES),
        ]);

        $log = $service->run($data['importType'], $request->file('file')->getRealPath());

        $this->toastSuccess(__('datatools.import_done', ['imported' => $log->imported_rows, 'failed' => $log->failed_rows]));

        return redirect()->route('data-tools.index');
    }

    public function createBackup(Request $request, BackupService $service): RedirectResponse
    {
        $onlyDatabase = $request->boolean('only_database');

        try {
            $code = $service->create($onlyDatabase);

            $code === 0
                ? $this->toastSuccess(__('datatools.backup_started'))
                : $this->toastError(__('datatools.backup_failed'));
        } catch (\Throwable $e) {
            $this->toastError(__('datatools.backup_failed'));
        }

        return redirect()->route('data-tools.index');
    }

    public function downloadBackup(BackupService $service, string $name): StreamedResponse
    {
        return $service->download($name);
    }

    public function deleteBackup(Request $request, BackupService $service): RedirectResponse
    {
        $service->delete((string) $request->string('name'));

        $this->toastSuccess(__('datatools.backup_deleted'));

        return redirect()->route('data-tools.index');
    }
}
