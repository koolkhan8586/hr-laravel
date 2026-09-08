<?php

namespace App\Support;

use App\Models\AppSetting;
use Carbon\Carbon;

/**
 * When the daily WhatsApp attendance report should go out, and how long it
 * should keep trying if WAHA is not answering.
 *
 * The time lives in settings rather than in the schedule itself, so it can be
 * changed from the Settings screen without touching code or the crontab.
 */
class DailyReportSchedule
{
    public const TIMEZONE = 'Asia/Karachi';

    public const DEFAULT_TIME = '11:38';
    public const DEFAULT_RETRY_UNTIL = '18:00';

    /** Time of day the report is due, as HH:MM. */
    public static function time(): string
    {
        return self::normalise(
            AppSetting::get('daily_report_time', self::DEFAULT_TIME),
            self::DEFAULT_TIME
        );
    }

    /** Keep trying after a failed attempt? */
    public static function retries(): bool
    {
        $value = AppSetting::get('daily_report_retry', '1');

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    /** Give up for the day after this time, as HH:MM. */
    public static function retryUntil(): string
    {
        return self::normalise(
            AppSetting::get('daily_report_retry_until', self::DEFAULT_RETRY_UNTIL),
            self::DEFAULT_RETRY_UNTIL
        );
    }

    /** Is now inside the window where the report should be attempted? */
    public static function isDue(Carbon $now): bool
    {
        $due = self::at($now, self::time());

        if ($now->lt($due)) {
            return false;
        }

        if (!self::retries()) {
            // A single shot: only the minute it falls due.
            return $now->format('H:i') === self::time();
        }

        return $now->lte(self::at($now, self::retryUntil()));
    }

    /** The given HH:MM on the same day as $now. */
    public static function at(Carbon $now, string $time): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $now->copy()->setTime($hour, $minute, 0);
    }

    /** Anything unparseable falls back rather than breaking the schedule. */
    public static function normalise($value, string $fallback): string
    {
        $value = trim((string) $value);

        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return $fallback;
        }

        $hour   = (int) $m[1];
        $minute = (int) $m[2];

        if ($hour > 23 || $minute > 59) {
            return $fallback;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    /** "11:38 AM", for reading rather than editing. */
    public static function label(string $time): string
    {
        return Carbon::createFromFormat('H:i', $time)->format('h:i A');
    }
}
