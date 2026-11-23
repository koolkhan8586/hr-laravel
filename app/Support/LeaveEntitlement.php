<?php

namespace App\Support;

use App\Models\Leave;
use App\Models\LeaveBalance;

/**
 * What an employee has left, and what kind of leave they can actually take.
 *
 * Annual leave is paid out of a balance. Asking for more of it than is left
 * used to be accepted and then stall: the application sat pending, and the
 * approver was told "Insufficient leave balance" every time they pressed
 * Approve, with nothing they could do about it from that screen.
 *
 * So the decision is made where it belongs - at the point of asking. Not
 * enough balance means the leave is taken without pay, which is what it was
 * always going to be, said plainly up front instead of discovered later.
 */
class LeaveEntitlement
{
    /**
     * Annual leave days still available.
     *
     * The opening allocation less the approved annual leave already taken,
     * which is how the balance is worked out everywhere else - deleting a
     * leave, editing one, and the yearly recalculation all count it this way.
     *
     * One leave can be left out of the sum: when an existing leave is being
     * edited, the days it already holds are its own to spend again.
     */
    public static function remainingFor(int $userId, ?int $ignoreLeaveId = null): float
    {
        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $userId],
            ['opening_balance' => 0, 'used_leaves' => 0, 'remaining_leaves' => 0]
        );

        $used = Leave::where('user_id', $userId)
            ->where('type', 'annual')
            ->where('status', 'approved')
            ->when($ignoreLeaveId, fn ($q) => $q->where('id', '!=', $ignoreLeaveId))
            ->sum('calculated_days');

        return round((float) $balance->opening_balance - (float) $used, 2);
    }

    /**
     * The kind of leave this request can actually be.
     *
     * Anything other than annual is left exactly as asked for. Annual is
     * allowed while there is enough balance for the whole of it, and becomes
     * leave without pay when there is not.
     *
     * @return array{type: string, converted: bool, remaining: float, requested: string}
     */
    public static function resolve(
        int $userId,
        string $requested,
        float $days,
        ?int $ignoreLeaveId = null
    ): array {

        $remaining = self::remainingFor($userId, $ignoreLeaveId);

        if ($requested !== 'annual') {
            return [
                'type' => $requested,
                'converted' => false,
                'remaining' => $remaining,
                'requested' => $requested,
            ];
        }

        $enough = $remaining >= $days;

        return [
            'type' => $enough ? 'annual' : 'without_pay',
            'converted' => ! $enough,
            'remaining' => $remaining,
            'requested' => $requested,
        ];
    }

    /**
     * Why the leave came out as without pay, in one sentence.
     *
     * @param  array{remaining: float}  $resolved
     */
    public static function notice(array $resolved, float $days): string
    {
        $remaining = self::days($resolved['remaining']);

        return $resolved['remaining'] <= 0
            ? 'There is no annual leave balance left, so this leave has been recorded as Without Pay.'
            : 'Only '.$remaining.' day(s) of annual leave are left and '.self::days($days)
                .' were asked for, so this leave has been recorded as Without Pay.';
    }

    /** "3" rather than "3.0", but "0.5" kept. */
    public static function days(float $days): string
    {
        return rtrim(rtrim(number_format($days, 1), '0'), '.');
    }
}
