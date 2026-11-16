<?php

/**
 * Artisan Console & Scheduler Definitions
 *
 * Registers scheduled background tasks for live class reminders and
 * daily F2F/Hybrid installment payment reminders.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send live class reminder notifications every 5 minutes
Schedule::command('live-classes:send-reminders')->everyFiveMinutes();

// Daily installment reminder check for F2F/Hybrid tuition schedules
Schedule::call(function () {
    app(\App\Services\InstallmentReminderService::class)->processDueReminders(true);
})->dailyAt('08:00');
