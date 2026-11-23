<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\AppSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * What marked the attendance.
 *
 * Checking where somebody is cannot catch the oldest trick there is here:
 * connect to an office computer from home, and mark attendance on it. The
 * machine is standing in the office, so its position is right, its address
 * is right, and every location rule passes.
 *
 * What does not pass is the machine itself. A phone in somebody's hand and
 * an office desktop are different things, and an office desktop that marks
 * attendance for several different people is a different thing again. So
 * each mark records the browser it came from, and two rules can be switched
 * on against it:
 *
 *  - attendance may only be marked from a phone, which a remote desktop
 *    session is not;
 *  - a browser that has already marked somebody else today cannot mark
 *    again, which a shared office computer always has.
 *
 * Neither is proof on its own - a determined person can make a browser say
 * what they like - but together they turn a quiet daily habit into
 * something that has to be worked at, and every attempt is on the record
 * either way.
 */
class AttendanceDevice
{
    public const REQUIRE_MOBILE = 'attendance_require_mobile';
    public const ONE_PER_DEVICE = 'attendance_one_employee_per_device';

    /**
     * Everything worth keeping about the browser this request came from.
     *
     * @return array{id: ?string, kind: string, ip: ?string, agent: ?string}
     */
    public static function describe(Request $request): array
    {
        $agent = (string) $request->userAgent();

        return [
            // The browser's own token, kept in its storage. Absent when
            // somebody posts straight at the endpoint.
            'id'    => self::cleanId($request->input('device_id')),
            'kind'  => self::kind($agent, $request),
            'ip'    => $request->ip(),
            'agent' => Str::limit($agent, 250, ''),
        ];
    }

    /**
     * Phone, computer, or something that did not say.
     *
     * Read from the user agent, with the browser's own answer about touch
     * only able to argue a phone down to a computer, never the other way
     * round: a claim that makes the rules stricter can be believed, a claim
     * that makes them looser cannot.
     */
    public static function kind(string $agent, ?Request $request = null): string
    {
        if ($agent === '') {
            return 'unknown';
        }

        $looksMobile = (bool) preg_match(
            '/(android|iphone|ipod|ipad|iemobile|blackberry|opera mini|mobile safari|windows phone)/i',
            $agent
        );

        if (! $looksMobile) {
            return 'computer';
        }

        // A phone has a touch screen. A desktop browser pretending to be a
        // phone usually forgets to say so.
        $touch = $request?->input('touch_points');

        if ($touch !== null && (int) $touch < 1) {
            return 'computer';
        }

        return 'phone';
    }

    /** May attendance only be marked from a phone? */
    public static function requiresMobile(): bool
    {
        return (bool) AppSetting::get(self::REQUIRE_MOBILE, false);
    }

    /** May one browser mark attendance for only one employee a day? */
    public static function onePerDevice(): bool
    {
        return (bool) AppSetting::get(self::ONE_PER_DEVICE, false);
    }

    /**
     * Whether this employee may mark attendance from this browser.
     *
     * @param  array{id: ?string, kind: string, ip: ?string, agent: ?string}  $device
     * @return array{allowed: bool, message: ?string, reason: string}
     */
    public static function check(User $user, array $device, ?Carbon $date = null): array
    {
        // Copied before it is wound back. Carbon is mutable and the caller is
        // holding this very instance as "now": winding it to midnight here
        // stamped every clock-out at 00:00 and then charged half a day for
        // leaving seventeen hours early.
        $date = ($date ? $date->copy() : Carbon::now(AttendanceRules::TIMEZONE))->startOfDay();

        // Somebody who genuinely has to use a shared machine - a guard on
        // the gate computer, anybody without a phone - is let through both
        // rules, and their marks still carry the device on the record.
        if ($user->attendance_device_exempt) {
            return self::pass('exempt');
        }

        if (self::requiresMobile() && $device['kind'] !== 'phone') {
            return [
                'allowed' => false,
                'reason'  => 'not_a_phone',
                'message' => 'Attendance has to be marked from your own phone. '
                    .'This looks like a computer, so nothing was recorded. '
                    .'Speak to HR if you need to mark it from a computer.',
            ];
        }

        if (self::onePerDevice() && filled($device['id'])) {

            $others = self::othersUsing($device['id'], $user->id, $date);

            if ($others->isNotEmpty()) {
                return [
                    'allowed' => false,
                    'reason'  => 'shared_device',
                    'message' => 'This device has already marked attendance today for '
                        .$others->implode(', ').'. '
                        .'Everybody marks their own attendance from their own phone.',
                ];
            }
        }

        return self::pass('ok');
    }

    /**
     * Other employees this browser has already marked attendance for today.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function othersUsing(string $deviceId, int $exceptUserId, ?Carbon $date = null): \Illuminate\Support\Collection
    {
        // Copied, never wound back - see check().
        $date = ($date ? $date->copy() : Carbon::now(AttendanceRules::TIMEZONE))->startOfDay();

        return Attendance::with('user')
            ->where('device_id', $deviceId)
            ->where('user_id', '!=', $exceptUserId)
            ->whereDate('date', $date->toDateString())
            ->get()
            ->pluck('user.name')
            ->filter()
            ->unique()
            ->values();
    }

    /** "Computer" / "Phone", for the screens. */
    public static function kindLabel(?string $kind): string
    {
        return match ($kind) {
            'phone'    => 'Phone',
            'computer' => 'Computer',
            default    => 'Not recorded',
        };
    }

    /**
     * The browser and system, short enough for a table.
     *
     * "Chrome on Windows" rather than ninety characters of version numbers.
     */
    public static function agentLabel(?string $agent): string
    {
        if (! filled($agent)) {
            return 'Not recorded';
        }

        $browser = match (true) {
            str_contains($agent, 'Edg/')      => 'Edge',
            str_contains($agent, 'OPR/')      => 'Opera',
            str_contains($agent, 'Chrome')    => 'Chrome',
            str_contains($agent, 'Firefox')   => 'Firefox',
            str_contains($agent, 'Safari')    => 'Safari',
            default                           => 'Browser',
        };

        $system = match (true) {
            str_contains($agent, 'Android')   => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/', $agent) => 'iPhone',
            str_contains($agent, 'Windows')   => 'Windows',
            str_contains($agent, 'Mac OS')    => 'Mac',
            str_contains($agent, 'Linux')     => 'Linux',
            default                           => 'unknown system',
        };

        return $browser.' on '.$system;
    }

    protected static function pass(string $reason): array
    {
        return ['allowed' => true, 'message' => null, 'reason' => $reason];
    }

    /** Only ever a plain token, whatever the browser sends. */
    protected static function cleanId($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '' || ! preg_match('/^[A-Za-z0-9\-]{8,64}$/', $value)) {
            return null;
        }

        return $value;
    }
}
