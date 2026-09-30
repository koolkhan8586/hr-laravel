<?php

namespace App\Support;

use App\Models\OfficeLocation;
use App\Models\User;

/**
 * Whether an employee may mark attendance where they are standing.
 *
 * Only employees who have offices assigned are restricted. Anyone with none
 * is unrestricted, which is how everybody behaved before this existed, so
 * turning it on for one person does not lock out everybody else.
 */
class AttendanceLocation
{
    /** Used when an office has no radius of its own. */
    public const DEFAULT_RADIUS = 100;

    /** Metres between two points on the earth. */
    public static function distance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLon / 2) * sin($dLon / 2);

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function radiusOf(OfficeLocation $office): int
    {
        return (int) ($office->radius ?: self::DEFAULT_RADIUS);
    }

    /** An employee is only held to a location once one has been chosen for them. */
    public static function isRestricted(User $user): bool
    {
        if (self::hasOverride($user)) {
            return false;
        }

        return $user->attendanceOffices()->isNotEmpty();
    }

    /** Allowed anywhere, permanently or until a date. */
    public static function hasOverride(User $user): bool
    {
        if ($user->allow_anywhere_attendance) {
            return true;
        }

        return $user->attendance_override_until
            && now()->lessThan($user->attendance_override_until);
    }

    /**
     * Decide a clock-in attempt.
     *
     * @return array{
     *   allowed: bool,
     *   reason: string,          unrestricted|override|inside|outside|no_position
     *   office: ?OfficeLocation, the office they are at, or the nearest one
     *   distance: ?float,        metres from that office
     *   message: ?string
     * }
     */
    public static function check(User $user, $lat, $lng): array
    {
        if (self::hasOverride($user)) {
            return self::result(true, 'override');
        }

        $offices = $user->attendanceOffices();

        if ($offices->isEmpty()) {
            return self::result(true, 'unrestricted');
        }

        // Restricted, so a position is not optional any more.
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return self::result(false, 'no_position', null, null,
                'Location is needed to mark attendance. Please allow location access and try again.');
        }

        $nearest = null;
        $nearestDistance = null;

        foreach ($offices as $office) {

            if (!is_numeric($office->latitude) || !is_numeric($office->longitude)) {
                continue;
            }

            $distance = self::distance($lat, $lng, $office->latitude, $office->longitude);

            if ($distance <= self::radiusOf($office)) {
                return self::result(true, 'inside', $office, $distance);
            }

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearest = $office;
                $nearestDistance = $distance;
            }
        }

        // Every assigned office is missing its coordinates, so there is
        // nothing to measure against. Say so rather than blocking blindly.
        if ($nearest === null) {
            return self::result(false, 'outside', null, null,
                'No coordinates have been set for your office. Please contact HR.');
        }

        return self::result(false, 'outside', $nearest, $nearestDistance, sprintf(
            'You are about %s from %s. Attendance can only be marked within %dm of it.',
            self::readable($nearestDistance),
            $nearest->name,
            self::radiusOf($nearest)
        ));
    }

    /** "80 m" or "1.4 km", so the message reads naturally. */
    public static function readable(float $metres): string
    {
        return $metres >= 1000
            ? round($metres / 1000, 1).' km'
            : round($metres).' m';
    }

    /** @return array<string, mixed> */
    protected static function result(
        bool $allowed,
        string $reason,
        ?OfficeLocation $office = null,
        ?float $distance = null,
        ?string $message = null
    ): array {
        return compact('allowed', 'reason', 'office', 'distance', 'message');
    }
}
