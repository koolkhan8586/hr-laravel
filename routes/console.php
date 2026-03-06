<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\AttendanceController;

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
| Auto Clock-Out
|--------------------------------------------------------------------------
| Previously this ran once at 20:30.
| Now it runs every minute so it can check:
| - employee shift end time
| - overtime allowed by admin
| - 30 minute margin
*/

Schedule::command('attendance:auto-clockout')
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
