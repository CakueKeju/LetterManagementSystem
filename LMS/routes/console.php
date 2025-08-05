<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule system maintenance tasks
Schedule::command('lms:maintenance')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/maintenance.log'));

// Run a more thorough cleanup daily at 2 AM
Schedule::command('lms:maintenance --force')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/daily-maintenance.log'));
