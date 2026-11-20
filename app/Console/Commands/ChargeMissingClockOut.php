<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Support\AttendanceRules;
use App\Support\DutyRoster;
use App\Support\HalfDayCharge;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Half a day for a shift nobody clocked out of.
 *
 * The system no longer closes an open shift on the employee's behalf. Once a
 * shift has been over for long enough - half an hour by default - and no
 * clock-out has been marked, half a day is charged instead: off the annual
 * balance, or without pay when there is none left.
 *
 * The clock-out is still not stamped. The day stays open, which is the
 * record of what happened; if the employee clocks out later the charge is
 * taken back by itself.
 */
class ChargeMissingClockOut extends Command
{
    protected $signature = 'attendance:charge-missing-clockout';

    protected $description = 'Charge half a day where a shift ended long ago and no clock-out was marked';

    public function handle(): int
    {
        $now = Carbon::now(AttendanceRules::TIMEZONE);

        $after = AttendanceRules::missingMinutes();

        // Yesterday as well as today, for shifts that run past midnight and
        // for the first run after a quiet night - but never before the day
        // the rules were switched on, or the first run would charge for
        // every open shift still sitting in the table from before.
        $from = $now->copy()->subDay()->startOfDay()->max(AttendanceRules::rulesFrom());

        $open = Attendance::with('user')
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $now->toDateString())
            ->get();

        $charged = 0;

        foreach ($open as $attendance) {

            if (!$attendance->user) {
                continue;
            }

            $date = HalfDayCharge::dateOf($attendance);

            if (!$date) {
                continue;
            }

            $endsAt = DutyRoster::endsAt($attendance->user, $date);

            if (!$endsAt || $now->lt($endsAt->copy()->addMinutes($after))) {
                continue;
            }

            // An admin who allowed overtime moved the end of the day, so the
            // clock-out is not late until that has passed too.
            if ($attendance->overtime_allowed_until) {

                $allowedUntil = Carbon::parse($attendance->overtime_allowed_until, AttendanceRules::TIMEZONE);

                if ($now->lt($allowedUntil->copy()->addMinutes($after))) {
                    continue;
                }
            }

            if (HalfDayCharge::existing($attendance->user_id, $date)) {
                continue;
            }

            if (HalfDayCharge::charge($attendance, AttendanceRules::MISSING_CLOCK_OUT)) {
                $charged++;
                $this->info('Half day charged: '.$attendance->user->name.' on '.$date->toDateString());
            }
        }

        $this->info('Missing clock-out check completed. '.$charged.' half day(s) charged.');

        return self::SUCCESS;
    }
}
