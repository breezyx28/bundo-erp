<?php

namespace App\Support;

use App\Services\Settings\SettingsManager;

class InvoiceDesign
{
    /** @return array<string, array{view:string, label:string}> */
    public static function all(): array
    {
        return config('invoice.designs', []);
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function defaultKey(): string
    {
        return (string) config('invoice.default', 'classic');
    }

    public static function currentKey(): string
    {
        $key = (string) app(SettingsManager::class)->get('design', self::defaultKey(), group: 'invoice');

        return array_key_exists($key, self::all()) ? $key : self::defaultKey();
    }

    public static function view(string $key): string
    {
        $designs = self::all();

        return $designs[$key]['view'] ?? $designs[self::defaultKey()]['view'];
    }

    public static function currentView(): string
    {
        return self::view(self::currentKey());
    }

    /** @return list<array{key:string, label:string}> */
    public static function options(): array
    {
        return collect(self::all())->map(fn ($design, $key) => [
            'key' => $key,
            'label' => $design['label'],
            'cover' => $design['cover'] ?? null,
        ])->values()->all();
    }
}
