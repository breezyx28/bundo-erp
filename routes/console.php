<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily operational alert scan (low stock, overdue receivables).
Schedule::command('notifications:scan')->dailyAt('07:00');

// Automated backups: full backup nightly, prune old archives afterwards.
Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt('02:00');
Schedule::command('backup:run --only-db')->weekly()->sundays()->at('03:00');
