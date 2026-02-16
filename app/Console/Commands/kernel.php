protected function schedule(Schedule $schedule)
{
    $schedule->command('attendance:mark-absent')
             ->dailyAt('18:00');
}

$schedule->command('attendance:auto-clockout')
         ->dailyAt('21:00')
         ->timezone('Asia/Karachi');
