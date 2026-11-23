<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

/**
 * Time owed, counted in minutes rather than punished in half days.
 *
 * The old rule charged half a day - four hours of somebody's leave - for
 * clocking out twenty-five minutes early. Twenty-five minutes is not four
 * hours, and taking four for twenty-five is taking more than is owed.
 *
 * So nothing is charged by the day any more. What is counted is the time
 * actually missed:
 *
 *  - arriving past the shift's grace counts the whole lateness from the
 *    start of the shift;
 *  - leaving more than the early grace before the shift ends counts the
 *    whole of that;
 *  - a day with no clock-out at all counts half the shift, because nobody
 *    knows when they left.
 *
 * Those minutes sit on the day and add up over the month. Only at the end of
 * the month is the total turned into days - four hours to half a day, eight
 * to a full one - and taken off the leave balance, or, with no balance left,
 * off the pay. An employee who is five minutes late twice loses ten minutes,
 * which rounds to nothing at all.
 *
 * Nothing here charges anything. It works out what the day was; charging is
 * MonthlyShortfall's job, once a month, when the whole month can be seen.
 */
class TimeShortfall
{
    public const TIMEZONE = AttendanceRules::TIMEZONE;

    /** Which rule is in force: half days per offence, or time owed. */
    public const MODE = 'attendance_charge_mode';

    public const MODE_HALF_DAY = 'half_day';
    public const MODE_TIME     = 'time';

    /** Leaving less than this many minutes early is not counted at all. */
    public const EARLY_GRACE = 'attendance_early_grace_minutes';

    /** Hours that make half a day when the month is totted up. */
    public const HOURS_PER_HALF_DAY = 'attendance_hours_per_half_day';

    /** The first day time is counted. The rules never reach backwards. */
    public const RULES_FROM = 'attendance_time_rules_from';

    /** Why minutes went against a day. */
    public const LATE    = 'late';
    public const EARLY   = 'early';
    public const MISSING = 'missing';

    /*
    |--------------------------------------------------------------------------
    | The settings
    |--------------------------------------------------------------------------
    */

    public static function mode(): string
    {
        return AppSetting::get(self::MODE) === self::MODE_TIME
            ? self::MODE_TIME
            : self::MODE_HALF_DAY;
    }

    /** Is time being counted instead of half days charged? */
    public static function enabled(): bool
    {
        return self::mode() === self::MODE_TIME;
    }

    /**
     * How early somebody may leave before any of it is counted.
     *
     * The mirror of the shift's own grace at the other end of the day.
     */
    public static function earlyGraceMinutes(): int
    {
        return max(0, (int) AppSetting::get(self::EARLY_GRACE, 15));
    }

    /**
     * The same, for one employee.
     *
     * Anybody with a standing arrangement to leave earlier is held to their
     * own figure - the one already on their staff record - rather than to
     * the company's.
     */
    public static function earlyGraceFor(?User $user): int
    {
        $own = $user?->early_leave_allowance_minutes;

        return $own > 0 ? (int) $own : self::earlyGraceMinutes();
    }

    /** Hours that count as half a day. Four, so eight is a full one. */
    public static function hoursPerHalfDay(): float
    {
        $hours = (float) AppSetting::get(self::HOURS_PER_HALF_DAY, 4);

        return $hours > 0 ? $hours : 4.0;
    }

    /** Hours that count as a whole day. */
    public static function hoursPerDay(): float
    {
        return self::hoursPerHalfDay() * 2;
    }

    /**
     * The first day time is counted.
     *
     * Defaults to today, like every other rule here, so switching it on never
     * reaches back and bills somebody for a month they were never told about.
     */
    public static function rulesFrom(): Carbon
    {
        $from = AppSetting::get(self::RULES_FROM);

        return $from
            ? Carbon::parse($from, self::TIMEZONE)->startOfDay()
            : Carbon::now(self::TIMEZONE)->startOfDay();
    }

    public static function appliesOn($date): bool
    {
        return Carbon::parse($date)->startOfDay()->gte(self::rulesFrom());
    }

    /*
    |--------------------------------------------------------------------------
    | What one day cost
    |--------------------------------------------------------------------------
    */

    /**
     * The minutes owed for a single day.
     *
     * Whether the day should be counted at all - rostered, not a holiday, not
     * on leave - is decided by the caller, which can see the whole month at
     * once. This answers only the arithmetic.
     *
     * @return array{
     *   late: int, early: int, missing: int, minutes: int,
     *   shift_minutes: int, reasons: array<int, string>,
     *   clock_in: ?Carbon, clock_out: ?Carbon,
     *   starts_at: ?Carbon, ends_at: ?Carbon
     * }
     */
    public static function forDay(
        User $user,
        Carbon $date,
        ?Attendance $attendance,
        ?Carbon $now = null
    ): array {

        $now  = $now ?? Carbon::now(self::TIMEZONE);
        $date = $date->copy()->startOfDay();

        $empty = [
            'late' => 0, 'early' => 0, 'missing' => 0, 'minutes' => 0,
            'shift_minutes' => 0, 'reasons' => [],
            'clock_in' => null, 'clock_out' => null,
            'starts_at' => null, 'ends_at' => null,
        ];

        $shift = DutyRoster::shiftFor($user, $date);

        // Not rostered, or a shift with no times on it: there is no figure to
        // be late against and nothing to measure.
        if (! $shift || ! $shift->start_time || ! $shift->end_time) {
            return $empty;
        }

        $startsAt = self::at($date, $shift->start_time);
        $endsAt   = DutyRoster::endsAt($user, $date);

        if (! $startsAt || ! $endsAt) {
            return $empty;
        }

        $shiftMinutes = max(0, (int) round($startsAt->diffInMinutes($endsAt)));

        $clockIn = $attendance?->clock_in
            ? Carbon::parse($attendance->clock_in, self::TIMEZONE)
            : null;

        $clockOut = $attendance?->clock_out
            ? Carbon::parse($attendance->clock_out, self::TIMEZONE)
            : null;

        // Nobody came in. That is an absence, and absences are a whole day
        // charged by their own rule - counting hours here as well would be
        // billing the same day twice.
        if (! $clockIn) {
            return array_merge($empty, [
                'shift_minutes' => $shiftMinutes,
                'starts_at'     => $startsAt,
                'ends_at'       => $endsAt,
            ]);
        }

        $reasons = [];

        /*
        | Late. The shift's own grace decides whether it counts at all; once
        | it does, the whole lateness counts, measured from the start of the
        | shift rather than from the end of the grace.
        */
        $late = 0;

        $lateAfter = DutyRoster::lateAfter($user, $date);

        if ($lateAfter && $clockIn->gt($lateAfter)) {
            $late = max(0, (int) round($startsAt->diffInMinutes($clockIn)));
            $reasons[] = self::LATE;
        }

        /*
        | Left early, or never marked a clock-out at all.
        */
        $early   = 0;
        $missing = 0;

        if ($clockOut) {

            if ($clockOut->lt($endsAt)) {

                $earlyBy = (int) round($clockOut->diffInMinutes($endsAt));

                if ($earlyBy > self::earlyGraceFor($user)) {
                    $early = $earlyBy;
                    $reasons[] = self::EARLY;
                }
            }

        } elseif ($now->gt($endsAt->copy()->addMinutes(AttendanceRules::missingMinutes()))) {

            // Nobody knows when they left, so half the shift is counted -
            // the same weight the old rule gave it, now in minutes so it
            // joins the month's total instead of costing half a day outright.
            $missing = (int) round($shiftMinutes / 2);
            $reasons[] = self::MISSING;
        }

        // However the day went wrong, it cannot cost more than the shift it
        // was: somebody three hours late who then left two hours early owes
        // the hours they were away, not more hours than the day held.
        $minutes = min($late + $early + $missing, $shiftMinutes);

        return [
            'late'          => $late,
            'early'         => $early,
            'missing'       => $missing,
            'minutes'       => $minutes,
            'shift_minutes' => $shiftMinutes,
            'reasons'       => $reasons,
            'clock_in'      => $clockIn,
            'clock_out'     => $clockOut,
            'starts_at'     => $startsAt,
            'ends_at'       => $endsAt,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Turning minutes into days, once a month
    |--------------------------------------------------------------------------
    */

    /**
     * The days a month's minutes come to, to the nearest half day.
     *
     * Four hours is half a day and eight is a full one, so the total is
     * rounded to whichever half day it is nearest: five and a half hours is
     * half a day, six and a half is a whole one. Under two hours in a whole
     * month rounds away to nothing.
     */
    public static function daysFor(int $minutes): float
    {
        if ($minutes <= 0) {
            return 0.0;
        }

        $halves = round(($minutes / 60) / self::hoursPerHalfDay());

        return round($halves * 0.5, 2);
    }

    /** "2 h 35 m", "45 m", "3 h". */
    public static function label(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 m';
        }

        $hours = intdiv($minutes, 60);
        $rest  = $minutes % 60;

        if ($hours === 0) {
            return $rest.' m';
        }

        return $rest === 0 ? $hours.' h' : $hours.' h '.$rest.' m';
    }

    /** "Late", "Left early", "No clock-out marked". */
    public static function reasonLabel(string $reason): string
    {
        return match ($reason) {
            self::LATE    => 'Came late',
            self::EARLY   => 'Left early',
            self::MISSING => 'No clock-out marked',
            default       => 'Time owed',
        };
    }

    /** A date and a shift time, as one moment. */
    protected static function at(Carbon $date, $time): ?Carbon
    {
        if (! $time) {
            return null;
        }

        $parsed = Carbon::parse($time, self::TIMEZONE);

        return $date->copy()->setTime(
            (int) $parsed->format('H'),
            (int) $parsed->format('i'),
            (int) $parsed->format('s')
        );
    }
}
