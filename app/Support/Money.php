<?php

namespace App\Support;

class Money
{
    /** Format an amount with its currency symbol, using locale-aware grouping. */
    public static function format(int|float|string|null $amount, ?string $currency = null): string
    {
        $currency ??= config('money.base');
        $config = config("money.currencies.{$currency}", ['symbol' => $currency, 'decimals' => 2]);

        $formatted = number_format((float) $amount, $config['decimals']);

        return $config['symbol'].' '.$formatted;
    }

    /** Base accounting currency code. */
    public static function base(): string
    {
        return config('money.base');
    }
}
