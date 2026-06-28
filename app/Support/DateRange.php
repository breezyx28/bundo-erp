<?php

namespace App\Support;

class DateRange
{
    /**
     * Inclusive timestamp bounds for a date range.
     *
     * Date columns are stored with a `00:00:00` time component (Laravel's date cast),
     * so a date-only upper bound would exclude same-day rows. Expanding to end-of-day
     * keeps `whereBetween` inclusive of the final day.
     *
     * @return array{0:string, 1:string}
     */
    public static function bounds(string $from, string $to): array
    {
        return [$from.' 00:00:00', $to.' 23:59:59'];
    }
}
