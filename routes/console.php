<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Spatie backup: clean old backups before creating a new one, then verify
// health. This was previously never registered, so the scheduler had
// nothing to run even if the server's cron heartbeat was ticking.
Schedule::command('backup:clean')->daily()->at('05:00')->onOneServer();
Schedule::command('backup:run')->daily()->at('19:00')->onOneServer();
Schedule::command('backup:monitor')->daily()->at('03:00')->onOneServer();
