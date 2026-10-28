<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Salary;
use App\Models\Staff;
use App\Models\WeeklySchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Leave taken without pay, and what it costs.
 *
 * Leave without pay is the one kind the salary has to answer for: the day is
 * approved and authorised, but it is not paid. This works out, for a given
 * month, how many unpaid days each employee took and what that comes to, so
 * the calendar can show it and the salary sheet can take it off.
 *
 * What counts as an unpaid day:
 *
 *  - an approved leave of type "without pay" covering that date,
 *  - a half day counting as half a day,
 *  - but never a day the employee was not due in anyway. Their weekly off or
 *    a holiday inside a week of leave is not a day's pay lost, and charging
 *    for it would overstate the deduction.
 *
 * The rate is the monthly pay divided by a day count. Which pay and which
 * divisor is spelled out wherever the figure is shown, so the number on the
 * screen can always be checked by hand.
 */
class WithoutPayLeave
{
    /** How the per-day rate is worked out. */
    public const BASIS_MONTH_DAYS = 'month_days';
    public const BASIS_THIRTY     = 'thirty';

    public const BASIS_SETTING = 'wop_day_basis';

    /**
     * Unpaid leave for every one of these employees in one month.
     *
     * One pass over the leaves, schedules and holidays rather than a query
     * per employee, because the calendar draws forty rows at a time.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\User>  $users
     * @return array<int, array{days: float, rate: float, amount: float, basis_days: int, monthly: float, source: string}>
     */
    public static function forMonth(Collection $users, Carbon $month, ?string $basis = null): array
    {
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        $userIds = $users->pluck('id');

        $basis      = self::basis($basis);
        $basisDays  = self::basisDays($start, $basis);
        $monthlyPay = self::monthlyPay($userIds, $start);

        $leaves = Leave::where('type', 'without_pay')
            ->where('status', 'approved')
            ->whereIn('user_id', $userIds)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->groupBy('user_id');

        $offDays  = self::offDaysByUser($userIds);
        $holidays = self::holidaysInRange($start, $end);

        $result = [];

        foreach ($users as $user) {

            $days = self::countDays(
                $leaves[$user->id] ?? collect(),
                $start,
                $end,
                $offDays[$user->id] ?? ['sunday'],
                $holidays,
                $user->id
            );

            [$monthly, $source] = $monthlyPay[$user->id] ?? [0.0, 'none'];

            $rate = $basisDays > 0 ? $monthly / $basisDays : 0.0;

            $result[$user->id] = [
                'days'       => $days,
                'rate'       => round($rate, 2),
                // Whole rupees: this goes on a salary sheet, not a ledger.
                'amount'     => (float) round($rate * $days),
                'basis_days' => $basisDays,
                'monthly'    => $monthly,
                'source'     => $source,
            ];
        }

        return $result;
    }

    /** The unpaid days one employee took in a month. */
    protected static function countDays(
        $leaves,
        Carbon $start,
        Carbon $end,
        array $offDays,
        $holidays,
        int $userId
    ): float {

        if ($leaves->isEmpty()) {
            return 0.0;
        }

        $days = 0.0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {

            // Not due in: nothing to dock.
            if (in_array(strtolower($day->format('l')), $offDays, true)) {
                continue;
            }

            if (self::isHoliday($holidays, $day, $userId)) {
                continue;
            }

            $leave = $leaves->first(fn ($l) => self::covers($l->start_date, $l->end_date, $day));

            if (!$leave) {
                continue;
            }

            $days += $leave->duration_type === 'half_day' ? 0.5 : 1.0;
        }

        return $days;
    }

    /**
     * The pay each employee's rate comes from.
     *
     * The gross on this month's salary sheet where there is one, since that is
     * what is actually being paid; otherwise the salary on their staff record,
     * so a deduction can still be worked out before the sheet is filled in.
     *
     * @return array<int, array{0: float, 1: string}>
     */
    protected static function monthlyPay($userIds, Carbon $month): array
    {
        $pay = [];

        $staffSalaries = Staff::whereIn('user_id', $userIds)
            ->pluck('salary', 'user_id');

        foreach ($staffSalaries as $userId => $salary) {
            $pay[$userId] = [(float) $salary, 'staff record'];
        }

        $sheetRows = Salary::whereIn('user_id', $userIds)
            ->where('month', $month->month)
            ->where('year', $month->year)
            ->get();

        foreach ($sheetRows as $row) {
            if ((float) $row->gross_total > 0) {
                $pay[$row->user_id] = [(float) $row->gross_total, 'salary sheet'];
            }
        }

        return $pay;
    }

    /**
     * Days of the week each employee is not rostered on.
     *
     * Same rule the monthly attendance report uses: taken from the weekly
     * schedule where there is one, Sunday where there is not.
     *
     * @return array<int, array<int, string>>
     */
    protected static function offDaysByUser($userIds): array
    {
        $week = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $rostered = WeeklySchedule::whereIn('user_id', $userIds)
            ->whereNotNull('shift_id')
            ->get(['user_id', 'day_of_week'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows
                ->pluck('day_of_week')
                ->map(fn ($d) => strtolower((string) $d))
                ->all());

        $offDays = [];

        foreach ($userIds as $userId) {

            $days = $rostered[$userId] ?? [];

            $offDays[$userId] = empty($days)
                ? ['sunday']
                : array_values(array_diff($week, $days));
        }

        return $offDays;
    }

    protected static function holidaysInRange(Carbon $start, Carbon $end)
    {
        return Holiday::with('users')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();
    }

    protected static function isHoliday($holidays, Carbon $day, int $userId): bool
    {
        foreach ($holidays as $holiday) {

            if (!self::covers($holiday->start_date, $holiday->end_date, $day)) {
                continue;
            }

            if ((int) $holiday->for_all === 1 || $holiday->users->contains('id', $userId)) {
                return true;
            }
        }

        return false;
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

    /** The saved choice, unless one is passed in for this run. */
    public static function basis(?string $basis = null): string
    {
        $basis ??= AppSetting::get(self::BASIS_SETTING, self::BASIS_MONTH_DAYS);

        return $basis === self::BASIS_THIRTY
            ? self::BASIS_THIRTY
            : self::BASIS_MONTH_DAYS;
    }

    public static function rememberBasis(?string $basis): void
    {
        AppSetting::put(self::BASIS_SETTING, self::basis($basis));
    }

    public static function basisDays(Carbon $month, ?string $basis = null): int
    {
        return self::basis($basis) === self::BASIS_THIRTY
            ? 30
            : $month->daysInMonth;
    }

    /** "31 days in October 2026" / "a flat 30 days", for the screen. */
    public static function basisLabel(Carbon $month, ?string $basis = null): string
    {
        return self::basis($basis) === self::BASIS_THIRTY
            ? 'a flat 30 days'
            : $month->daysInMonth.' days in '.$month->format('F Y');
    }
}
