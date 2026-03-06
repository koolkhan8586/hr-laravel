<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AutoClockOut extends Command
{
    protected $signature = 'attendance:auto-clockout';

    protected $description = 'Auto clock out employees based on shift end time + margin';

    public function handle()
    {
        $now = Carbon::now('Asia/Karachi');

        $attendances = Attendance::whereNull('clock_out')
            ->with('user')
            ->get();

        foreach ($attendances as $attendance) {

            if (!$attendance->user) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Get today's shift from employee_schedules
            |--------------------------------------------------------------------------
            */

            $schedule = DB::table('employee_schedules')
                ->join('shifts', 'employee_schedules.shift_id', '=', 'shifts.id')
                ->where('employee_schedules.user_id', $attendance->user_id)
                ->whereDate('employee_schedules.date', $attendance->date)
                ->select('shifts.end_time', 'shifts.grace_minutes')
                ->first();

            if (!$schedule) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate auto clock-out time
            |--------------------------------------------------------------------------
            */

            $shiftEnd = Carbon::parse($attendance->date . ' ' . $schedule->end_time);

            $autoClockOut = $shiftEnd->copy()->addMinutes($schedule->grace_minutes ?? 30);

            /*
            |--------------------------------------------------------------------------
            | If admin allowed overtime
            |--------------------------------------------------------------------------
            */

            if ($attendance->overtime_allowed_until) {
                $autoClockOut = Carbon::parse($attendance->overtime_allowed_until)
                    ->addMinutes(30);
            }

            /*
            |--------------------------------------------------------------------------
            | Auto Clock-Out Condition
            |--------------------------------------------------------------------------
            */

            if ($now->greaterThanOrEqualTo($autoClockOut)) {

                $clockIn = Carbon::parse($attendance->clock_in);

                if ($autoClockOut < $clockIn) {
                    $autoClockOut = $clockIn->copy()->addMinute();
                }

                $attendance->clock_out = $autoClockOut;

                $minutes = $clockIn->diffInMinutes($autoClockOut);

                $attendance->total_hours = round($minutes / 60, 2);

                $attendance->save();

                /*
                |--------------------------------------------------------------------------
                | Send Email
                |--------------------------------------------------------------------------
                */

                try {

                    Mail::raw(
                        "Auto Clock-Out Notification\n\n".
                        "Date: ".$attendance->date."\n".
                        "Clock In: ".$attendance->clock_in."\n".
                        "Clock Out: ".$attendance->clock_out."\n".
                        "Total Hours: ".$attendance->total_hours."\n\n".
                        "Your shift was automatically closed by the system.",
                        function ($message) use ($attendance) {

                            $message->to($attendance->user->email)
                                ->subject('Auto Clock-Out Recorded');

                        }
                    );

                } catch (\Exception $e) {}

            }

        }

        $this->info('Auto clock-out check completed.');
    }
}
