<?php

namespace App\Services\Backup;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Thin wrapper around spatie/laravel-backup for the in-app backup manager:
 * trigger on-demand backups, and list / download / delete the resulting
 * archives from the configured destination disk.
 */
class BackupService
{
    public function disk(): Filesystem
    {
        /** @var list<string> $disks */
        $disks = config('backup.backup.destination.disks', ['local']);

        return Storage::disk($disks[0] ?? 'local');
    }

    public function directory(): string
    {
        return (string) config('backup.backup.name', config('app.name'));
    }

    /**
     * Trigger an on-demand backup. Returns the artisan exit code (0 = success).
     */
    public function create(bool $onlyDatabase = false): int
    {
        $options = [];
        if ($onlyDatabase) {
            $options['--only-db'] = true;
        }

        return Artisan::call('backup:run', $options);
    }

    /**
     * Available backup archives, newest first.
     *
     * @return list<array{name:string, path:string, size:int, date:string}>
     */
    public function list(): array
    {
        $disk = $this->disk();
        $dir = $this->directory();

        $backups = [];
        foreach ($disk->files($dir) as $path) {
            if (! str_ends_with($path, '.zip')) {
                continue;
            }

            $backups[] = [
                'name' => basename($path),
                'path' => $path,
                'size' => $disk->size($path),
                'date' => date('Y-m-d H:i', $disk->lastModified($path)),
            ];
        }

        usort($backups, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $backups;
    }

    public function download(string $name): StreamedResponse
    {
        return $this->disk()->download($this->directory().'/'.$name);
    }

    public function delete(string $name): void
    {
        $this->disk()->delete($this->directory().'/'.$name);
    }
}
