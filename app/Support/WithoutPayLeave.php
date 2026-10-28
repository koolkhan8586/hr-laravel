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

    /**
     * What one approved leave without pay will cost the employee.
     *
     * Worked out a month at a time, because the rate is a month's pay over a
     * month's days: a leave running from one month into the next is deducted
     * from two salaries, at two different daily rates.
     *
     * @return array{days: float, amount: float, months: array<int, array{label: string, days: float, rate: float, amount: float, priced: bool}>}
     */
    public static function forLeave(Leave $leave, ?string $basis = null): array
    {
        $leave->loadMissing('user');

        $from = Carbon::parse($leave->start_date)->startOfDay();
        $to   = Carbon::parse($leave->end_date)->startOfDay();

        $offDays  = self::offDaysByUser([$leave->user_id])[$leave->user_id] ?? ['sunday'];
        $holidays = self::holidaysInRange($from, $to);

        $months = [];
        $totalDays = 0.0;
        $totalAmount = 0.0;

        for ($month = $from->copy()->startOfMonth(); $month->lte($to); $month->addMonth()) {

            // Only the part of the leave that falls inside this month.
            $windowStart = $month->copy()->startOfMonth()->max($from);
            $windowEnd   = $month->copy()->endOfMonth()->min($to);

            $days = self::countDays(
                collect([$leave]),
                $windowStart,
                $windowEnd,
                $offDays,
                $holidays,
                $leave->user_id
            );

            if ($days <= 0) {
                continue;
            }

            $basisDays = self::basisDays($month, $basis);

            [$monthly, $source] = self::monthlyPay(collect([$leave->user_id]), $month)[$leave->user_id] ?? [0.0, 'none'];

            $rate = $basisDays > 0 ? $monthly / $basisDays : 0.0;
            $amount = (float) round($rate * $days);

            $months[] = [
                'label'  => $month->format('F Y'),
                'days'   => $days,
                'rate'   => round($rate, 2),
                'amount' => $amount,
                // No salary on file yet: the days are known, the money is not.
                'priced' => $monthly > 0,
                'source' => $source,
            ];

            $totalDays += $days;
            $totalAmount += $amount;
        }

        return [
            'days'   => $totalDays,
            'amount' => $totalAmount,
            'months' => $months,
        ];
    }

    /**
     * The deduction spelled out for the employee, as lines of a message.
     *
     * Null when there is nothing to say - the leave is paid, or it falls
     * entirely on days they were not due in anyway.
     *
     * @return array<int, string>|null
     */
    public static function noticeLines(Leave $leave, ?string $basis = null): ?array
    {
        if ($leave->type !== 'without_pay') {
            return null;
        }

        $cost = self::forLeave($leave, $basis);

        if ($cost['days'] <= 0) {
            return null;
        }

        $lines = ['This leave is without pay.'];

        foreach ($cost['months'] as $month) {

            $days = self::niceDays($month['days']);

            $lines[] = $month['priced']
                ? 'Rs '.number_format($month['amount']).' will be deducted from your '
                  .$month['label'].' salary ('.$days.' day(s) at Rs '
                  .number_format($month['rate'], 2).' a day).'
                : $days.' day(s) will be deducted from your '.$month['label']
                  .' salary. The amount will be worked out from your salary for that month.';
        }

        if (count($cost['months']) > 1 && $cost['amount'] > 0) {
            $lines[] = 'Total to be deducted: Rs '.number_format($cost['amount']).'.';
        }

        $lines[] = 'A weekly off or a holiday inside the leave is not deducted for.';

        // Said plainly when the figure came from the salary on record rather
        // than a salary sheet that does not exist yet, so a later difference
        // is not a surprise.
        $fromRecord = collect($cost['months'])
            ->contains(fn ($m) => $m['priced'] && $m['source'] === 'staff record');

        if ($fromRecord) {
            $lines[] = 'This is worked out from your salary on record and will follow that month\'s salary sheet.';
        }

        return $lines;
    }

    /** "3" rather than "3.0", but "0.5" kept. */
    public static function niceDays(float $days): string
    {
        return rtrim(rtrim(number_format($days, 1), '0'), '.');
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
