<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSchedule;
use App\Models\Shift;
use App\Models\User;
use App\Models\WeeklySchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Setting particular dates, rather than the recurring week.
 *
 * The weekly grid cannot say "off this Saturday, working the next one". This
 * screen lists the dates in a month - by default the Saturdays, since that is
 * the day that usually alternates - and lets each one be set per employee.
 */
class MonthlyScheduleController extends Controller
{
    public const TIMEZONE = 'Asia/Karachi';

    public function index(Request $request)
    {
        $month   = self::monthFrom($request->month);
        $weekday = self::weekdayFrom($request->weekday);

        $dates = self::datesIn($month, $weekday);

        $users = User::where('role', 'employee')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code']);

        $overrides = EmployeeSchedule::forUsersBetween(
            $users->pluck('id'),
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth()
        );

        // What each employee normally does on this weekday, so "Usual" on the
        // dropdown means something the admin can see.
        $usual = [];

        foreach ($users as $user) {
            $week = WeeklySchedule::forUser($user->id);

            $usual[$user->id] = $weekday
                ? ($week[$weekday]->shift ?? null)
                : null;
        }

        return view('admin.weekly.monthly', [
            'users'     => $users,
            'shifts'    => Shift::orderBy('name')->get(),
            'dates'     => $dates,
            'overrides' => $overrides,
            'usual'     => $usual,
            'month'     => $month,
            'monthName' => $month->format('F Y'),
            'weekday'   => $weekday,
            'weekdays'  => WeeklySchedule::DAYS,
        ]);
    }

    public function update(Request $request)
    {
        $month   = self::monthFrom($request->month);
        $weekday = self::weekdayFrom($request->weekday);

        // Only the dates this screen was showing may be written, so a stale
        // form cannot reach back into a month nobody was looking at.
        $allowed = collect(self::datesIn($month, $weekday))
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $employeeIds = User::where('role', 'employee')->pluck('id')->all();

        $changed = 0;

        foreach ($request->input('duty', []) as $userId => $byDate) {

            if (!in_array((int) $userId, $employeeIds, true)) {
                continue;
            }

            foreach ($byDate as $date => $value) {

                if (!in_array($date, $allowed, true)) {
                    continue;
                }

                // "" means leave it to the weekly pattern, "off" means take
                // them off that day, anything else is a shift id.
                if ($value === '' || $value === null) {
                    EmployeeSchedule::clearFor($userId, $date);
                } elseif ($value === 'off') {
                    EmployeeSchedule::setFor($userId, $date, null);
                } else {
                    EmployeeSchedule::setFor($userId, $date, $value);
                }

                $changed++;
            }
        }

        return redirect()->route('schedule.monthly', [
            'month'   => $month->format('Y-m'),
            'weekday' => $weekday,
        ])->with('success', 'Saved '.$changed.' day(s).');
    }

    /* ---------------------------------------------------------------- */

    /** The dates in the month, optionally only those falling on one weekday. */
    protected static function datesIn(Carbon $month, ?string $weekday): array
    {
        $dates = [];

        $day = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        for (; $day->lte($end); $day->addDay()) {
            if (!$weekday || $day->format('l') === $weekday) {
                $dates[] = $day->copy();
            }
        }

        return $dates;
    }

    /** ?month=YYYY-MM, falling back to this month rather than throwing. */
    protected static function monthFrom($value): Carbon
    {
        $now = Carbon::now(self::TIMEZONE)->startOfMonth();

        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
            return $now;
        }

        $year  = (int) $m[1];
        $month = (int) $m[2];

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return $now;
        }

        return Carbon::create($year, $month, 1, 0, 0, 0, self::TIMEZONE)->startOfMonth();
    }

    /**
     * Which weekday to list. Saturday by default, since that is the one that
     * alternates; "all" lists every date in the month.
     */
    protected static function weekdayFrom($value): ?string
    {
        if ($value === 'all') {
            return null;
        }

        return WeeklySchedule::normaliseDay($value) ?? 'Saturday';
    }
}
