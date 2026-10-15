<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduled auto-backup for SaaS platform (default 02:00 AM daily)
Schedule::command('backup:auto')->dailyAt('02:00')->withoutOverlapping();

