<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
protected function schedule(Schedule $schedule)
{
    // Auto Absent Detection (11:45 AM)
    $schedule->command('attendance:mark-absent')
             ->dailyAt('11:45')
             ->timezone('Asia/Karachi');

    // Auto Clock-out (9 PM)
    $schedule->command('attendance:auto-clockout')
             ->dailyAt('21:00')
             ->timezone('Asia/Karachi');

    // Daily Summary Email (9 PM)
    $schedule->command('send:daily-attendance-summary')
             ->dailyAt('21:00')
             ->timezone('Asia/Karachi');
}
