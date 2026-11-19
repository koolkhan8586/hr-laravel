<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\AttendanceController;
use App\Console\Commands\AutoClockOut;

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


/*
|--------------------------------------------------------------------------
| Mark Absent (Auto)
|--------------------------------------------------------------------------
*/

Schedule::command('attendance:mark-absent')
    ->dailyAt('11:45')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| Missing Clock-Out
|--------------------------------------------------------------------------
| Auto clock-out is gone. It used to stamp a clock-out at the end of every
| open shift, so a day nobody closed looked exactly like one they did.
|
| A shift with no clock-out now stays open, and half an hour after it should
| have ended half a day is charged for it - off the annual balance, or
| without pay when there is none left. The command still exists and can be
| switched back on from Settings; it is simply not scheduled.
*/

Schedule::command('attendance:charge-missing-clockout')
    ->everyFiveMinutes()
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| WhatsApp Attendance Reminder (WAHA)
|--------------------------------------------------------------------------
| Runs every minute. Sends a reminder 1 hour after each employee's
| shift start if they have not clocked in and have not applied leave.
*/

Schedule::command('attendance:whatsapp-reminder')
    ->everyMinute()
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| Daily WhatsApp Attendance Report (WAHA)
|--------------------------------------------------------------------------
| Sends the Absent / Late / Leave lists to the numbers set in Settings.
|
| It runs every minute and the command decides for itself whether the report
| is due, so the send time can be changed on the Settings screen without
| touching this file or the crontab, and a report missed because WAHA was
| down is retried instead of being lost for the day.
*/

Schedule::command('attendance:whatsapp-daily-report')
    ->everyMinute()
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| Daily Attendance Summary
|--------------------------------------------------------------------------
*/

Schedule::command('app:send-daily-attendance-summary')
    ->dailyAt('21:00')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();
