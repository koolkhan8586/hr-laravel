<?php

namespace App\Support;

use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Shift;
use App\Models\User;
use App\Models\WeeklySchedule;
use Carbon\Carbon;

/**
 * An employee's duty timings: the weekly pattern they work to, and what each
 * date of a given month actually looks like.
 *
 * A date can be set individually, which takes precedence over the weekly
 * pattern, and leave or a holiday can take the day out altogether.
 */
class DutyRoster
{
    public const TIMEZONE = 'Asia/Karachi';

    public const DAYS = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    ];

    /**
     * The recurring week: every day, with the shift worked or nothing.
     *
     * @return array<int, array{day: string, shift: ?Shift, timing: ?string, working: bool}>
     */
    public static function week(User $user): array
    {
        // Resolved by the model, so this and the admin grid always agree.
        $byDay = WeeklySchedule::forUser($user->id);

        $week = [];

        foreach (self::DAYS as $day) {

            $shift = $byDay[$day]->shift ?? null;

            $week[] = [
                'day'     => $day,
                'shift'   => $shift,
                'timing'  => self::timing($shift),
                'working' => (bool) $shift,
            ];
        }

        return $week;
    }

    /**
     * Every date in the month, and the duty for it.
     *
     * Unlike the attendance report this runs to the end of the month: a
     * roster is there to be looked at ahead of time.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function month(User $user, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();
        $today = Carbon::now(self::TIMEZONE)->startOfDay();

        $weekly = collect(self::week($user))->keyBy('day');

        $dated = EmployeeSchedule::forUserBetween($user->id, $start, $end);

        $leaves = Leave::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        $holidays = Holiday::with('users')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->filter(fn ($h) => (int) $h->for_all === 1 || $h->users->contains('id', $user->id))
            ->values();

        $days = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {

            $date = $day->toDateString();

            // A row for this exact date overrides the weekly pattern outright,
            // including one with no shift on it: that is an employee taken off
            // a day they would normally work, which is how an alternating
            // Saturday is expressed. Falling back to the weekly shift here
            // would quietly put them back on duty.
            $override = $dated[$date] ?? null;

            $shift = $override
                ? $override->shift
                : ($weekly[$day->format('l')]['shift'] ?? null);

            $holiday = $holidays->first(fn ($h) => self::covers($h->start_date, $h->end_date, $day));
            $leave   = $leaves->first(fn ($l) => self::covers($l->start_date, $l->end_date, $day));

            $days[] = [
                'date'      => $day->copy(),
                'day_name'  => $day->format('D'),
                'shift'     => $shift,
                'timing'    => self::timing($shift),
                'working'   => (bool) $shift && !$holiday && !$leave,
                'is_today'  => $day->isSameDay($today),
                'is_past'   => $day->lt($today),
                'changed'   => (bool) $override,
                'status'    => match (true) {
                    (bool) $holiday => 'Holiday',
                    (bool) $leave   => self::leaveLabel($leave),
                    !$shift         => 'Off',
                    default         => 'On duty',
                },
                'note'      => $holiday?->title ?? $leave?->reason,
            ];
        }

        return $days;
    }

    /** The duty for today, for the line at the top of the page. */
    public static function today(User $user): ?array
    {
        $now = Carbon::now(self::TIMEZONE);

        return collect(self::month($user, $now))
            ->first(fn ($d) => $d['is_today']);
    }

    /**
     * The shift an employee was rostered on for one date.
     *
     * A shift set against the date itself wins over the weekly pattern, the
     * same order the roster screens use. Null means they were not due in.
     *
     * Everything that needs to know when somebody was supposed to start -
     * clocking in, and editing a record afterwards - goes through here, so a
     * one-off change is honoured everywhere rather than only on the screens
     * that display it.
     */
    public static function shiftFor(User $user, $date): ?Shift
    {
        $date = Carbon::parse($date)->startOfDay();

        $override = EmployeeSchedule::forUserBetween($user->id, $date, $date)[$date->toDateString()] ?? null;

        if ($override) {
            return $override->shift;
        }

        return WeeklySchedule::forUser($user->id)[$date->format('l')]?->shift;
    }

    /**
     * When an arrival counts as late for that date, grace included.
     *
     * Null when they were not rostered, so the caller decides what that means.
     */
    public static function lateAfter(User $user, $date): ?Carbon
    {
        $shift = self::shiftFor($user, $date);

        if (!$shift || !$shift->start_time) {
            return null;
        }

        $date = Carbon::parse($date)->startOfDay();

        $start = Carbon::parse($shift->start_time, self::TIMEZONE);

        return $date->copy()
            ->setTime((int) $start->format('H'), (int) $start->format('i'), (int) $start->format('s'))
            ->addMinutes((int) ($shift->grace_minutes ?? 0));
    }

    /** "09:00 AM - 05:30 PM", or nothing when no shift is set. */
    public static function timing(?Shift $shift): ?string
    {
        if (!$shift || !$shift->start_time || !$shift->end_time) {
            return null;
        }

        return Carbon::parse($shift->start_time)->format('h:i A')
            .' - '.Carbon::parse($shift->end_time)->format('h:i A');
    }

    protected static function leaveLabel(Leave $leave): string
    {
        return $leave->duration_type === 'half_day' ? 'Half Day Leave' : 'On Leave';
    }

    /** Inclusive date-range check that ignores any time component. */
    protected static function covers($from, $to, Carbon $day): bool
    {
        if (!$from || !$to) {
            return false;
        }

        return Carbon::parse($from)->startOfDay()->lte($day)
            && Carbon::parse($to)->startOfDay()->gte($day);
    }
}
