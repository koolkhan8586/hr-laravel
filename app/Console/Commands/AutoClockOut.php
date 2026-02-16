<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;

class AutoClockOut extends Command
{
    protected $signature = 'attendance:auto-clockout';
    protected $description = 'Auto clock out users at 9 PM if not clocked out';

    public function handle()
    {
        $today = Carbon::now('Asia/Karachi')->toDateString();
        $autoTime = Carbon::createFromTime(21, 0, 0, 'Asia/Karachi');

        $records = Attendance::whereDate('clock_in', $today)
            ->whereNull('clock_out')
            ->get();

        foreach ($records as $attendance) {

            $attendance->clock_out = $autoTime;

            $hours = Carbon::parse($attendance->clock_in)
                        ->diffInMinutes($autoTime) / 60;

            $attendance->total_hours = round($hours, 2);

            // Half-day detection
            if ($hours < 4) {
                $attendance->status = 'half_day';
            }

            $attendance->save();
        }

        $this->info('Auto clock-out executed successfully.');
    }
}
