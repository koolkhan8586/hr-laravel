<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * The house rules for a working day, and the settings behind them.
 *
 * Three things are decided here rather than scattered through commands and
 * controllers, so the figure on the settings screen is the figure the rules
 * actually use:
 *
 *  - whether the system closes a shift nobody clocked out of (it no longer
 *    does: the day stays open and the half day below is charged instead),
 *  - how early a clock-out has to be before it costs half a day,
 *  - how long after a shift ends an employee has to mark their clock-out
 *    before that costs half a day,
 *  - whether a plain absence with no leave behind it comes off the pay.
 */
class AttendanceRules
{
    public const TIMEZONE = 'Asia/Karachi';

    public const AUTO_CLOCK_OUT   = 'attendance_auto_clock_out';
    public const EARLY_MINUTES    = 'attendance_early_clock_out_minutes';
    public const MISSING_MINUTES  = 'attendance_missing_clock_out_minutes';
    public const DEDUCT_ABSENCES  = 'attendance_deduct_absences';
    public const ABSENCES_FROM    = 'attendance_deduct_absences_from';

    /** Why a half day was charged. */
    public const EARLY_CLOCK_OUT   = 'early_clock_out';
    public const MISSING_CLOCK_OUT = 'missing_clock_out';

    /**
     * Does the system close a shift on the employee's behalf?
     *
     * Off. It used to stamp a clock-out at the end of every open shift, which
     * made a day nobody closed look exactly like one they did, and hid the
     * very thing the half day rule is there to catch. A day with no clock-out
     * now simply stays open.
     */
    public static function autoClockOutEnabled(): bool
    {
        return (bool) AppSetting::get(self::AUTO_CLOCK_OUT, false);
    }

    /** Clocking out this many minutes before the shift ends costs half a day. */
    public static function earlyMinutes(): int
    {
        return max(1, (int) AppSetting::get(self::EARLY_MINUTES, 25));
    }

    /** No clock-out this long after the shift ended costs half a day. */
    public static function missingMinutes(): int
    {
        return max(1, (int) AppSetting::get(self::MISSING_MINUTES, 30));
    }

    /** Is a day absent with no leave behind it taken off the pay? */
    public static function deductsAbsences(): bool
    {
        return (bool) AppSetting::get(self::DEDUCT_ABSENCES, true);
    }

    /**
     * The first month absences are charged for.
     *
     * A guard rail, not a preference. Every working day with no attendance
     * record counts as absent, and years of old months are full of them -
     * days nobody was tracked, days before an employee joined a system that
     * did not exist yet. Switching the rule on without a line in the sand
     * would put a month's pay against names going back to the first import.
     *
     * The month the rule was switched on is the default, so it only ever
     * looks forward unless somebody deliberately moves it back.
     */
    public static function absencesFrom(): ?\Carbon\Carbon
    {
        $month = AppSetting::get(self::ABSENCES_FROM);

        return $month
            ? \Carbon\Carbon::parse($month.'-01')->startOfMonth()
            : null;
    }

    /** Are absences charged for in this particular month? */
    public static function deductsAbsencesIn(\Carbon\Carbon $month): bool
    {
        if (!self::deductsAbsences()) {
            return false;
        }

        $from = self::absencesFrom();

        return !$from || $month->copy()->startOfMonth()->gte($from);
    }

    public static function label(?string $reason): string
    {
        return match ($reason) {
            self::EARLY_CLOCK_OUT   => 'Left early',
            self::MISSING_CLOCK_OUT => 'No clock-out marked',
            default                 => 'Charged by the system',
        };
    }

    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            AppSetting::put($key, $value);
        }
    }
}
