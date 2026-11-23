<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveTransaction;
use App\Models\ShortfallCharge;
use App\Models\User;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * A month of missed time, day by day, and what it comes to.
 *
 * Minutes are counted on the day they happen but charged only at the end of
 * the month, because that is the only point at which the arithmetic is fair.
 * Twenty minutes late on Monday is twenty minutes, not half a day; the same
 * twenty minutes every day for a month is seven hours, which is most of a
 * day and ought to cost one.
 *
 * Nothing is charged while the month is still running. Everybody can watch
 * the figure on their own screen and make it up before the month closes,
 * which is the point: this is meant to be a thing people correct, not a
 * thing that is sprung on them.
 */
class MonthlyShortfall
{
    public const TIMEZONE = AttendanceRules::TIMEZONE;

    /** Why the balance moved, on the transaction report. */
    public const REASON = 'time_shortfall';

    /*
    |--------------------------------------------------------------------------
    | Working out the month
    |--------------------------------------------------------------------------
    */

    /**
     * One employee's month: every day that cost time, and the total.
     *
     * @return array{
     *   days: array<int, array<string, mixed>>,
     *   minutes: int, late: int, early: int, missing: int,
     *   hours: float, days_charged: float,
     *   from_balance: float, without_pay: float,
     *   balance: float, charge: ?ShortfallCharge,
     *   month: Carbon, closed: bool, counts: bool
     * }
     */
    public static function forUser(User $user, Carbon $month, ?Carbon $now = null): array
    {
        $now   = $now ?? Carbon::now(self::TIMEZONE);
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        // A month still running stops at yesterday for counting purposes -
        // today is not over, so being "early" today means nothing yet.
        $lastDay = $end->copy()->min($now->copy()->startOfDay());

        $charge = ShortfallCharge::where('user_id', $user->id)
            ->whereDate('month', $start->toDateString())
            ->first();

        $empty = [
            'days' => [], 'minutes' => 0, 'late' => 0, 'early' => 0, 'missing' => 0,
            'hours' => 0.0, 'days_charged' => 0.0,
            'from_balance' => 0.0, 'without_pay' => 0.0,
            'balance' => LeaveEntitlement::remainingFor($user->id),
            'charge' => $charge, 'month' => $start,
            'closed' => $end->lt($now->copy()->startOfDay()),
            'counts' => TimeShortfall::enabled(),
        ];

        if ($start->gt($lastDay)) {
            return $empty;
        }

        $records  = self::attendanceByDate($user, $start, $lastDay);
        $leaves   = self::leavesInRange($user, $start, $lastDay);
        $holidays = self::holidaysInRange($user, $start, $lastDay);

        $rows    = [];
        $minutes = 0;
        $late    = 0;
        $early   = 0;
        $missing = 0;

        for ($day = $start->copy(); $day->lte($lastDay); $day->addDay()) {

            $date = $day->toDateString();

            $row = self::describe(
                $user,
                $day->copy(),
                $records[$date] ?? null,
                $leaves,
                $holidays,
                $now
            );

            // Only the days that actually cost something are listed. A month
            // of clean days should read as a clean month, not forty rows of
            // zeroes somebody has to scan.
            if ($row['minutes'] > 0) {
                $rows[] = $row;
                $minutes += $row['minutes'];
                $late    += $row['late'];
                $early   += $row['early'];
                $missing += $row['missing'];
            }
        }

        $daysCharged = TimeShortfall::daysFor($minutes);

        $balance = LeaveEntitlement::remainingFor($user->id);

        // Annual leave pays for it while there is any; the rest is unpaid.
        $fromBalance = min($daysCharged, max(0.0, $balance));

        return array_merge($empty, [
            'days'         => $rows,
            'minutes'      => $minutes,
            'late'         => $late,
            'early'        => $early,
            'missing'      => $missing,
            'hours'        => round($minutes / 60, 2),
            'days_charged' => $daysCharged,
            'from_balance' => $charge ? (float) $charge->days_from_balance : $fromBalance,
            'without_pay'  => $charge
                ? (float) $charge->days_without_pay
                : round($daysCharged - $fromBalance, 2),
            'balance'      => $balance,
        ]);
    }

    /**
     * The same for a list of employees, for the admin screen.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forUsers($users, Carbon $month, ?Carbon $now = null): array
    {
        $out = [];

        foreach (collect($users) as $user) {
            $out[$user->id] = self::forUser($user, $month, $now);
        }

        return $out;
    }

    /**
     * What one day was, and what it cost.
     *
     * @return array<string, mixed>
     */
    protected static function describe(
        User $user,
        Carbon $day,
        ?Attendance $attendance,
        Collection $leaves,
        Collection $holidays,
        Carbon $now
    ): array {

        $base = [
            'date'      => $day->copy(),
            'day_name'  => $day->format('D'),
            'minutes'   => 0,
            'late'      => 0,
            'early'     => 0,
            'missing'   => 0,
            'reasons'   => [],
            'clock_in'  => null,
            'clock_out' => null,
            'timing'    => null,
            'skipped'   => null,
        ];

        // The rule looks forward only. Days before it was switched on are
        // left exactly as they were, whatever is on them.
        if (! TimeShortfall::appliesOn($day)) {
            return array_merge($base, ['skipped' => 'before the rule started']);
        }

        // A holiday or an approved leave takes the day out altogether. A half
        // day's approved leave does too: the employee is due for half a day,
        // and measuring somebody against half a shift they were given off is
        // how a perfectly ordinary afternoon turns into four hours owed.
        if (self::covered($holidays, $day)) {
            return array_merge($base, ['skipped' => 'holiday']);
        }

        if (self::covered($leaves, $day)) {
            return array_merge($base, ['skipped' => 'on leave']);
        }

        $shortfall = TimeShortfall::forDay($user, $day, $attendance, $now);

        return array_merge($base, [
            'minutes'   => $shortfall['minutes'],
            'late'      => $shortfall['late'],
            'early'     => $shortfall['early'],
            'missing'   => $shortfall['missing'],
            'reasons'   => $shortfall['reasons'],
            'clock_in'  => $shortfall['clock_in']?->format('h:i A'),
            'clock_out' => $shortfall['clock_out']?->format('h:i A'),
            'timing'    => $shortfall['starts_at'] && $shortfall['ends_at']
                ? $shortfall['starts_at']->format('h:i A').' - '.$shortfall['ends_at']->format('h:i A')
                : null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Charging it, once, at the end of the month
    |--------------------------------------------------------------------------
    */

    /**
     * Charge one employee for one finished month.
     *
     * Returns the charge, or null when there was nothing to charge - which is
     * the ordinary case and never an error. Running it again on a month that
     * has already been charged finds the existing row and changes nothing.
     */
    public static function charge(User $user, Carbon $month, ?Carbon $now = null): ?ShortfallCharge
    {
        $now   = $now ?? Carbon::now(self::TIMEZONE);
        $start = $month->copy()->startOfMonth();

        if (! TimeShortfall::enabled()) {
            return null;
        }

        // Never while the month is still running: the whole point is that the
        // total is only fair once the month can be seen whole.
        if ($start->copy()->endOfMonth()->gte($now->copy()->startOfDay())) {
            return null;
        }

        $existing = ShortfallCharge::where('user_id', $user->id)
            ->whereDate('month', $start->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        $sheet = self::forUser($user, $start, $now);

        if ($sheet['days_charged'] <= 0) {
            return null;
        }

        $charge = self::write($user, $start, $sheet);

        self::tell($user, $charge, $sheet);

        return $charge;
    }

    /**
     * Write the charge and take it off the balance, both or neither.
     *
     * The transaction is written before the balance moves, the way every
     * other deduction here now does it: the balance coming down with nothing
     * on the transaction report to show for it is exactly the failure that
     * made employees distrust the figure in the first place.
     */
    protected static function write(User $user, Carbon $month, array $sheet): ShortfallCharge
    {
        return DB::transaction(function () use ($user, $month, $sheet) {

            $charge = ShortfallCharge::create([
                'user_id'           => $user->id,
                'month'             => $month->toDateString(),
                'minutes'           => $sheet['minutes'],
                'late_minutes'      => $sheet['late'],
                'early_minutes'     => $sheet['early'],
                'missing_minutes'   => $sheet['missing'],
                'days_charged'      => $sheet['days_charged'],
                'days_from_balance' => $sheet['from_balance'],
                'days_without_pay'  => $sheet['without_pay'],
                'charged_at'        => now(),
            ]);

            if ($sheet['from_balance'] > 0) {

                $balance = LeaveBalance::firstOrCreate(
                    ['user_id' => $user->id],
                    ['opening_balance' => 0, 'used_leaves' => 0, 'remaining_leaves' => 0]
                );

                $before = (float) $balance->remaining_leaves;

                LeaveTransaction::create([
                    'user_id'        => $user->id,
                    'leave_id'       => null,
                    'days'           => $sheet['from_balance'],
                    'balance_before' => $before,
                    'balance_after'  => $before - $sheet['from_balance'],
                    'action'         => 'deducted',
                    'note'           => 'Time short in '.$month->format('F Y').' - '
                        .TimeShortfall::label($sheet['minutes']),
                    'processed_by'   => null,
                ]);

                $balance->update([
                    'used_leaves'      => $balance->used_leaves + $sheet['from_balance'],
                    'remaining_leaves' => $before - $sheet['from_balance'],
                ]);
            }

            return $charge;
        });
    }

    /**
     * Tell the employee what the month came to and how it was worked out.
     *
     * Neither channel may break the charge, which is already written by the
     * time this runs.
     */
    protected static function tell(User $user, ShortfallCharge $charge, array $sheet): void
    {
        $lines = [
            'Time Short in '.$charge->month->format('F Y'),
            '',
            'Total time short: '.TimeShortfall::label((int) $charge->minutes).'.',
        ];

        $parts = [];

        if ($sheet['late'] > 0) {
            $parts[] = 'coming late '.TimeShortfall::label($sheet['late']);
        }

        if ($sheet['early'] > 0) {
            $parts[] = 'leaving early '.TimeShortfall::label($sheet['early']);
        }

        if ($sheet['missing'] > 0) {
            $parts[] = 'days with no clock-out marked '.TimeShortfall::label($sheet['missing']);
        }

        if ($parts) {
            $lines[] = 'Made up of: '.implode(', ', $parts).'.';
        }

        $lines[] = '';
        $lines[] = TimeShortfall::hoursPerHalfDay().' hours counts as half a day and '
            .TimeShortfall::hoursPerDay().' hours as a full day, so this comes to '
            .LeaveEntitlement::days((float) $charge->days_charged).' day(s).';

        if ($charge->days_from_balance > 0) {
            $lines[] = LeaveEntitlement::days((float) $charge->days_from_balance)
                .' day(s) have been taken off your annual leave balance.';
        }

        if ($charge->days_without_pay > 0) {
            $lines[] = 'You had no annual leave left for '
                .LeaveEntitlement::days((float) $charge->days_without_pay)
                .' day(s), so that much is without pay and will be deducted from your salary.';
        }

        $lines[] = '';
        $lines[] = 'Every day that counted is listed on your My Timing screen.';
        $lines[] = 'Speak to HR if you think this is wrong.';

        $body = implode("\n", $lines);

        try {
            Mail::raw($body, function ($message) use ($user, $charge) {
                $message->to($user->email)
                    ->subject('Time short in '.$charge->month->format('F Y'));
            });
        } catch (\Throwable $e) {
            Log::error('Shortfall charge email failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }

        if (! filled($user->mobile)) {
            return;
        }

        try {
            app(WahaService::class)->sendToMobile($user->mobile, $body."\n\n- LSAF HR");
        } catch (\Throwable $e) {
            Log::error('Shortfall charge WhatsApp failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Unpaid days from time shortfalls already charged, per employee.
     *
     * What the salary sheet adds to its deduction. Only months that have been
     * charged count - a month still running is not money yet.
     *
     * @return array<int, float>
     */
    public static function unpaidDaysFor($userIds, Carbon $month): array
    {
        return ShortfallCharge::whereIn('user_id', collect($userIds))
            ->whereDate('month', $month->copy()->startOfMonth()->toDateString())
            ->where('days_without_pay', '>', 0)
            ->pluck('days_without_pay', 'user_id')
            ->map(fn ($days) => (float) $days)
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Loading a month
    |--------------------------------------------------------------------------
    */

    /** @return array<string, Attendance> */
    protected static function attendanceByDate(User $user, Carbon $start, Carbon $end): array
    {
        return Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString())
            ->all();
    }

    protected static function leavesInRange(User $user, Carbon $start, Carbon $end): Collection
    {
        return Leave::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();
    }

    protected static function holidaysInRange(User $user, Carbon $start, Carbon $end): Collection
    {
        return Holiday::with('users')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->filter(fn ($h) => (int) $h->for_all === 1 || $h->users->contains('id', $user->id))
            ->values();
    }

    /** Does anything in this list cover the day? */
    protected static function covered(Collection $rows, Carbon $day): bool
    {
        return $rows->contains(function ($row) use ($day) {

            if (! $row->start_date || ! $row->end_date) {
                return false;
            }

            return Carbon::parse($row->start_date)->startOfDay()->lte($day)
                && Carbon::parse($row->end_date)->startOfDay()->gte($day);
        });
    }
}
