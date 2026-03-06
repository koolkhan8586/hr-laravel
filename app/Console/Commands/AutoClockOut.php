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

    $attendances = Attendance::whereNull('clock_out')
        ->with('user')
        ->get();

    foreach ($attendances as $attendance) {

        $shiftEnd = Carbon::parse(
            $attendance->date.' '.$attendance->user->shift_end_time
        );

        $autoTime = $shiftEnd->addMinutes(30);

        if ($attendance->overtime_allowed_until) {

            $autoTime = Carbon::parse(
                $attendance->overtime_allowed_until
            )->addMinutes(30);

        }

        if (now()->greaterThanOrEqualTo($autoTime)) {

            $attendance->clock_out = $autoTime;

            $minutes = Carbon::parse($attendance->clock_in)
                ->diffInMinutes($autoTime);

            $attendance->total_hours = round($minutes / 60,2);

            $attendance->save();
        }
    }

}
