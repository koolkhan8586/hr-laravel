protected function schedule(Schedule $schedule)
{
    // Auto Absent Detection (11 AM)
    $schedule->command('attendance:mark-absent')
             ->dailyAt('11:00')
             ->timezone('Asia/Karachi');

    // Auto Clock-out (9 PM)
    $schedule->command('attendance:auto-clockout')
             ->dailyAt('21:00')
             ->timezone('Asia/Karachi');
}
