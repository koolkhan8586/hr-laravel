<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment('Keep pushing forward!');
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
*/

Schedule::command('attendance:mark-absent')
    ->dailyAt('11:45')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();

Schedule::command('attendance:auto-clockout')
    ->dailyAt('20:30')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();

Schedule::command('app:send-daily-attendance-summary')
    ->dailyAt('21:00')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();
