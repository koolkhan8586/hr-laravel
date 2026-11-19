<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveTransaction;
use App\Models\User;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Half a day charged for a day that was not worked in full.
 *
 * Two things cost half a day: clocking out well before the shift ends, and
 * never marking a clock-out at all. Either way the half day is charged the
 * way any other half day would be - taken off the annual balance, and, when
 * there is none left, taken as leave without pay, which the salary sheet
 * then deducts.
 *
 * It is written as an ordinary approved leave so that every screen that
 * already understands leave understands this too: the calendar, the monthly
 * report, the balance, the payroll summary and the unpaid-leave deduction.
 * What marks it out is auto_reason, which says the system wrote it and why.
 *
 * Nothing here is final. An admin can delete the leave like any other, and a
 * charge for a missing clock-out is taken back by itself if the employee
 * clocks out later the same day.
 */
class HalfDayCharge
{
    public const DAYS = 0.5;

    /**
     * Charge half a day against one attendance record.
     *
     * Returns the leave that was written, or null when there was nothing to
     * charge - which is the usual case and never an error.
     */
    public static function charge(Attendance $attendance, string $reason): ?Leave
    {
        $attendance->loadMissing('user');

        $user = $attendance->user;

        if (!$user) {
            return null;
        }

        $date = self::dateOf($attendance);

        if (!$date) {
            return null;
        }

        if (!self::chargeable($user, $date)) {
            return null;
        }

        $leave = self::write($user, $date, $reason);

        if (!$leave) {
            return null;
        }

        // The day itself now reads as a half day, so the attendance screens
        // and the monthly report agree with the leave that was just charged.
        if ($attendance->status !== 'half_day') {
            $attendance->status = 'half_day';
            $attendance->save();
        }

        self::tell($leave, $reason);

        return $leave;
    }

    /**
     * Take back a charge, because the employee did mark their clock-out after
     * all. Only ever undoes what the system wrote itself.
     */
    public static function reverse(Attendance $attendance, string $reason): bool
    {
        $date = self::dateOf($attendance);

        if (!$date) {
            return false;
        }

        $leave = self::existing($attendance->user_id, $date, $reason);

        if (!$leave) {
            return false;
        }

        $wasAnnual = $leave->type === 'annual';
        $userId = $leave->user_id;

        // Deleting the leave takes its transaction with it, so the balance is
        // worked out again from the leaves that are left - the same way
        // deleting a leave by hand does it.
        $leave->delete();

        if ($wasAnnual) {
            self::recalculateBalance($userId);
        }

        return true;
    }

    /** Has this day already been charged, for this reason or any other? */
    public static function existing(int $userId, Carbon $date, ?string $reason = null): ?Leave
    {
        return Leave::where('user_id', $userId)
            ->whereNotNull('auto_reason')
            ->when($reason, fn ($q) => $q->where('auto_reason', $reason))
            ->whereDate('start_date', $date->toDateString())
            ->first();
    }

    /**
     * Is there anything to charge for this date?
     *
     * Not when they were not due in, not on a holiday, and not when a leave
     * already covers the day - a half day on top of an approved absence would
     * be charging twice for the same hours.
     */
    protected static function chargeable(User $user, Carbon $date): bool
    {
        if (!DutyRoster::shiftFor($user, $date)) {
            return false;
        }

        $onHoliday = Holiday::with('users')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->get()
            ->contains(fn ($h) => (int) $h->for_all === 1 || $h->users->contains('id', $user->id));

        if ($onHoliday) {
            return false;
        }

        $onLeave = Leave::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->exists();

        return !$onLeave;
    }

    /**
     * Write the half day, off the balance where there is one.
     *
     * Annual leave is used first; with less than half a day left it is taken
     * without pay instead, which is what reaches the salary sheet.
     */
    protected static function write(User $user, Carbon $date, string $reason): ?Leave
    {
        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $user->id],
            ['opening_balance' => 0, 'used_leaves' => 0, 'remaining_leaves' => 0]
        );

        $paid = $balance->remaining_leaves >= self::DAYS;

        $leave = Leave::create([
            'user_id'         => $user->id,
            'type'            => $paid ? 'annual' : 'without_pay',
            'start_date'      => $date->toDateString(),
            'end_date'        => $date->toDateString(),
            'duration_type'   => 'half_day',
            // The hours lost are at the end of the day either way: they left
            // before the shift was over, or nobody knows when they left.
            'half_day_type'   => 'afternoon',
            'days'            => self::DAYS,
            'calculated_days' => self::DAYS,
            'reason'          => AttendanceRules::label($reason)
                .' on '.$date->format('d M Y').' - half day charged by the system',
            'auto_reason'     => $reason,
            'auto_charged_at' => now(),
            'status'          => 'approved',
            'decided_via'     => 'system',
            'decided_at'      => now(),
        ]);

        if ($paid) {
            self::takeFromBalance($leave, $balance);
        }

        return $leave;
    }

    protected static function takeFromBalance(Leave $leave, LeaveBalance $balance): void
    {
        $before = $balance->remaining_leaves;

        $balance->update([
            'used_leaves'      => $balance->used_leaves + self::DAYS,
            'remaining_leaves' => $before - self::DAYS,
        ]);

        LeaveTransaction::create([
            'user_id'        => $leave->user_id,
            'leave_id'       => $leave->id,
            'days'           => self::DAYS,
            'balance_before' => $before,
            'balance_after'  => $balance->remaining_leaves,
            'action'         => 'deducted',
            'processed_by'   => null,
        ]);
    }

    /** The balance, counted back from the approved annual leave that remains. */
    protected static function recalculateBalance(int $userId): void
    {
        $balance = LeaveBalance::where('user_id', $userId)->first();

        if (!$balance) {
            return;
        }

        $approved = Leave::where('user_id', $userId)
            ->where('type', 'annual')
            ->where('status', 'approved')
            ->sum('calculated_days');

        $balance->update([
            'used_leaves'      => $approved,
            'remaining_leaves' => $balance->opening_balance - $approved,
        ]);
    }

    /**
     * Tell the employee, on email and WhatsApp.
     *
     * An unpaid half day says what it will cost, worked out the same way the
     * salary sheet works it out. Neither channel may break the charge, which
     * has already been written by the time this runs.
     */
    protected static function tell(Leave $leave, string $reason): void
    {
        $leave->loadMissing('user');

        $date = Carbon::parse($leave->start_date);

        $lines = [
            'Half Day Charged',
            '',
            'Date: '.$date->format('d M Y'),
            'Reason: '.AttendanceRules::label($reason).'.',
        ];

        $lines[] = $leave->type === 'annual'
            ? 'Half a day has been taken off your annual leave balance.'
            : 'You have no annual leave left, so this half day is without pay.';

        if ($leave->type !== 'annual') {

            $notice = WithoutPayLeave::noticeLines($leave);

            if ($notice) {
                $lines[] = '';
                $lines = array_merge($lines, $notice);
            }
        }

        $lines[] = '';
        $lines[] = 'Speak to HR if you think this is wrong.';

        $body = implode("\n", $lines);

        try {
            Mail::raw($body, function ($message) use ($leave) {
                $message->to($leave->user->email)->subject('Half Day Charged');
            });
        } catch (\Throwable $e) {
            Log::error('Half day charge email failed', [
                'leave_id' => $leave->id,
                'message' => $e->getMessage(),
            ]);
        }

        $mobile = $leave->user->mobile ?? null;

        if (!filled($mobile)) {
            return;
        }

        try {
            app(WahaService::class)->sendToMobile($mobile, $body."\n\n- LSAF HR");
        } catch (\Throwable $e) {
            Log::error('Half day charge WhatsApp failed', [
                'leave_id' => $leave->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** The calendar date an attendance row belongs to. */
    public static function dateOf(Attendance $attendance): ?Carbon
    {
        $date = $attendance->date ?: $attendance->clock_in;

        return $date ? Carbon::parse($date)->startOfDay() : null;
    }
}
