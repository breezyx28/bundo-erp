<?php

namespace App\Support;

use App\Services\Settings\SettingsManager;

class TenantMoney
{
    public static function exchangeRate(): float
    {
        return (float) app(SettingsManager::class)->get(
            'exchange_rate',
            config('money.default_exchange_rate'),
            group: 'currency',
        );
    }

    public static function baseCurrency(): string
    {
        return (string) app(SettingsManager::class)->get(
            'default',
            config('money.base'),
            group: 'currency',
        );
    }
}
