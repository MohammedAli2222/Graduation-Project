<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('auth:clean-unverified-users')->hourly();

Schedule::command('queue:work', [
    '--queue' => 'default',
    '--tries' => 3,
    '--max-time' => 50,
])
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

