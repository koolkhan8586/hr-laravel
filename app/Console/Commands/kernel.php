protected function schedule(Schedule $schedule)
{
    $schedule->command('attendance:mark-absent')
             ->dailyAt('18:00');
}
