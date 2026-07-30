<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule 15-minute SLA Warning Check every minute
Schedule::command('ticket:check-sla')->everyMinute();

// Schedule 5-minute Auto-Disposition Check for pending tickets every minute
Schedule::command('tickets:check-auto-dispo --timeout=5')->everyMinute();



