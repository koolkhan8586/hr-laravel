<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WorkFromHome;
use Carbon\Carbon;

/**
 * A month of attendance, one row per calendar day.
 *
 * The monthly report used to list only the days that had a clock-in, so a day
 * spent on leave, a day marked absent and a weekly off all looked identical:
 * a gap in the table. Walking the calendar instead means every day is
 * accounted for and says what it was.
 */
class MonthlyAttendanceSheet
{
    public const TIMEZONE = 'Asia/Karachi';

    /**
     * @return array{
     *   days: array<int, array<string, mixed>>,
     *   totals: array<string, float|int>,
     *   month: \Carbon\Carbon
     * }
     */
    public static function build(User $user, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        // A month still running stops at today: days that have not happened
        // yet are not absences.
        $today = Carbon::now(self::TIMEZONE)->startOfDay();

        if ($end->greaterThan($today)) {
            $end = $today->copy();
        }

        if ($start->greaterThan($today)) {
            return ['days' => [], 'totals' => self::emptyTotals(), 'month' => $month];
        }

        $records  = self::attendanceByDate($user, $start, $end);
        $leaves   = self::leavesInRange($user, $start, $end);
        $wfh      = self::wfhInRange($user, $start, $end);
        $holidays = self::holidaysInRange($user, $start, $end);
        $offDays  = self::weeklyOffDays($user);

        $days   = [];
        $totals = self::emptyTotals();

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {

            $row = self::describe($day, $records, $leaves, $wfh, $holidays, $offDays);

            $days[] = $row;

            $totals[$row['bucket']] = ($totals[$row['bucket']] ?? 0) + 1;
            $totals['hours'] += $row['hours'] ?? 0;
        }

        $totals['hours'] = round($totals['hours'], 2);

        return ['days' => $days, 'totals' => $totals, 'month' => $month];
    }

    /**
     * What a single day was.
     *
     * Order matters. A day they were not rostered on and did not work is a
     * weekly off. Otherwise an approved half day wins, then the attendance
     * record, because somebody who clocked in on their day off was still at
     * work. After that: holiday, full-day leave, work from home.
     *
     * @return array<string, mixed>
     */
    protected static function describe(
        Carbon $day,
        array $records,
        $leaves,
        $wfh,
        $holidays,
        array $offDays
    ): array {

        $date = $day->toDateString();

        $base = [
            'date'      => $day->copy(),
            'day_name'  => $day->format('D'),
            'full_day'  => $day->format('l'),
            'clock_in'  => null,
            'clock_out' => null,
            'hours'     => null,
            'note'      => null,
        ];

        $record = $records[$date] ?? null;

        $worked = $record && $record->clock_in
            ? [
                'clock_in'  => Carbon::parse($record->clock_in)->format('h:i A'),
                'clock_out' => $record->clock_out
                    ? Carbon::parse($record->clock_out)->format('h:i A')
                    : null,
                'hours'     => (float) ($record->total_hours ?? 0),
            ]
            : [];

        // A day they were never rostered on, and did not come in for, is a day
        // off whatever else covers it. Counting the Sunday inside a week's
        // leave as leave would overstate the leave taken.
        if (!$worked && self::isOffDay($day, $offDays)) {
            return array_merge($base, ['label' => 'Weekly Off', 'bucket' => 'off']);
        }

        $halfDayLeave = $leaves->first(fn ($l) => $l->duration_type === 'half_day'
            && self::covers($l->start_date, $l->end_date, $day));

        // Half a day off and half a day worked is a half day, not a late
        // arrival. The attendance row alone would say "Late", because coming
        // in after lunch looks exactly like coming in late.
        if ($halfDayLeave) {
            return array_merge($base, $worked, [
                'label'  => 'Half Day',
                'bucket' => 'half_day',
                'note'   => self::leaveNote($halfDayLeave),
            ]);
        }

        // Somebody actually turned up, whatever the calendar said.
        // array_merge, not +, so these values win over the blanks in $base.
        if ($worked) {
            return array_merge($base, $worked, [
                'label'  => self::statusLabel($record->status),
                'bucket' => self::bucketFor($record->status),
            ]);
        }

        $holiday = $holidays->first(fn ($h) => self::covers($h->start_date, $h->end_date, $day));

        if ($holiday) {
            return array_merge($base, [
                'label'  => 'Holiday',
                'bucket' => 'holiday',
                'note'   => $holiday->title,
            ]);
        }

        $leave = $leaves->first(fn ($l) => self::covers($l->start_date, $l->end_date, $day));

        if ($leave) {
            return array_merge($base, [
                'label'  => 'Leave',
                'bucket' => 'leave',
                'note'   => self::leaveNote($leave),
            ]);
        }

        if ($wfh->contains(fn ($w) => self::covers($w->start_date, $w->end_date, $day))) {
            return array_merge($base, ['label' => 'Work From Home', 'bucket' => 'wfh']);
        }

        // A row with no clock-in that the nightly job wrote, or no row at all:
        // either way nobody came in and nothing accounts for it.
        return array_merge($base, ['label' => 'Absent', 'bucket' => 'absent']);
    }

    protected static function statusLabel(?string $status): string
    {
        return match ($status) {
            'present'   => 'Present',
            'late'      => 'Late',
            'half_day'  => 'Half Day',
            'absent'    => 'Absent',
            null, ''    => 'Present',
            default     => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    protected static function bucketFor(?string $status): string
    {
        return match ($status) {
            'late'     => 'late',
            'half_day' => 'half_day',
            'absent'   => 'absent',
            default    => 'present',
        };
    }

    /**
     * The detail behind a leave, for the Remarks column.
     *
     * The Status column stays short so the table can be scanned; which kind
     * of leave it was, which half of the day, and why, all read better here.
     */
    protected static function leaveNote(Leave $leave): string
    {
        $parts = [ucfirst(str_replace('_', ' ', (string) $leave->type)).' leave'];

        if ($leave->duration_type === 'half_day' && $leave->half_day_type) {
            $parts[0] .= ' ('.str_replace('_', ' ', $leave->half_day_type).')';
        }

        if (filled($leave->reason)) {
            $parts[] = $leave->reason;
        }

        return implode(' - ', $parts);
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

    /**
     * Days of the week the employee is not rostered on.
     *
     * Taken from their weekly schedule where one exists, since not everybody
     * is off on the same days. With no schedule on file, Sunday is treated as
     * the weekly off rather than assuming a Saturday-Sunday weekend.
     *
     * @return array<int, string>
     */
    protected static function weeklyOffDays(User $user): array
    {
        $rostered = WeeklySchedule::where('user_id', $user->id)
            ->whereNotNull('shift_id')
            ->pluck('day_of_week')
            ->map(fn ($d) => strtolower((string) $d))
            ->all();

        if (empty($rostered)) {
            return ['sunday'];
        }

        $week = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return array_values(array_diff($week, $rostered));
    }

    protected static function isOffDay(Carbon $day, array $offDays): bool
    {
        return in_array(strtolower($day->format('l')), $offDays, true);
    }

    /** @return array<string, \App\Models\Attendance> */
    protected static function attendanceByDate(User $user, Carbon $start, Carbon $end): array
    {
        // Rows are matched on the date column, not on clock_in: an absent row
        // has no clock_in at all and would otherwise never be found.
        $rows = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $byDate = [];

        foreach ($rows as $row) {
            $date = $row->date
                ? Carbon::parse($row->date)->toDateString()
                : ($row->clock_in ? Carbon::parse($row->clock_in)->toDateString() : null);

            if (!$date) {
                continue;
            }

            // Keep the most informative row for a day if there are several.
            if (!isset($byDate[$date]) || (!$byDate[$date]->clock_in && $row->clock_in)) {
                $byDate[$date] = $row;
            }
        }

        // Legacy rows written before the date column was filled in.
        $loose = Attendance::where('user_id', $user->id)
            ->whereNull('date')
            ->whereNotNull('clock_in')
            ->whereBetween('clock_in', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get();

        foreach ($loose as $row) {
            $date = Carbon::parse($row->clock_in)->toDateString();
            $byDate[$date] ??= $row;
        }

        return $byDate;
    }

    protected static function leavesInRange(User $user, Carbon $start, Carbon $end)
    {
        return Leave::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->orderBy('start_date')
            ->get();
    }

    protected static function wfhInRange(User $user, Carbon $start, Carbon $end)
    {
        return WorkFromHome::where('user_id', $user->id)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();
    }

    protected static function holidaysInRange(User $user, Carbon $start, Carbon $end)
    {
        return Holiday::with('users')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->filter(fn ($h) => (int) $h->for_all === 1
                || $h->users->contains('id', $user->id))
            ->values();
    }

    /** @return array<string, float|int> */
    protected static function emptyTotals(): array
    {
        return [
            'present'  => 0,
            'late'     => 0,
            'half_day' => 0,
            'leave'    => 0,
            'absent'   => 0,
            'wfh'      => 0,
            'holiday'  => 0,
            'off'      => 0,
            'hours'    => 0.0,
        ];
    }
}
