<?php

use App\Services\Maintenance\MaintenanceSnapshotRepository;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('maintenance:scan quick')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

Schedule::command('maintenance:scan deep')
    ->dailyAt((string) config('maintenance.deep_scan_time'))
    ->withoutOverlapping();

Schedule::call(fn () => app(MaintenanceSnapshotRepository::class)->recordHeartbeat())
    ->name('maintenance:scheduler-heartbeat')
    ->everyFiveMinutes()
    ->withoutOverlapping();
