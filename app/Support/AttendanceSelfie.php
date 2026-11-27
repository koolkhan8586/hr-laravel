<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * A photo at clock-in, for the employees it is switched on for.
 *
 * Switched on per employee, never for everybody: it is there for somebody
 * whose attendance has to be backed by something more than a position and a
 * phone, and it is a photo of a person, so it is kept off by default and kept
 * out of reach.
 *
 *  - It is checked on the server. The page asks for the camera, but a page
 *    can be skipped, so a clock-in without a real photo is refused here.
 *  - Only a photo taken live is wanted. The page offers the camera and
 *    nothing else, with no way to choose a picture already on the phone.
 *  - Photos live on the private disk, not under public/, and are served only
 *    to an admin through a route that checks who is asking.
 */
class AttendanceSelfie
{
    public const DISK = 'local';

    /** A phone photo shrunk to this width is a few tens of kilobytes. */
    public const MAX_BYTES = 1_500_000;

    public const MIN_SIDE = 100;

    /** Setting: how many days a photo is kept. */
    public const RETENTION_DAYS = 'attendance_selfie_retention_days';

    public const DEFAULT_RETENTION_DAYS = 90;

    /**
     * How long a photo is kept, in days.
     *
     * A photo of a person is kept only as long as there is a reason to look at
     * it. Never less than a week, so a mistake in the setting cannot wipe the
     * photos before anybody has had the chance to look.
     */
    public static function retentionDays(): int
    {
        $days = (int) \App\Models\AppSetting::get(self::RETENTION_DAYS, self::DEFAULT_RETENTION_DAYS);

        return max(7, min(3650, $days ?: self::DEFAULT_RETENTION_DAYS));
    }

    /** Average brightness (0-255) below which the photo is too dark to show anybody. */
    public const MIN_BRIGHTNESS = 30;

    /** Spread of brightness below which the photo is a flat blank, with nothing in it. */
    public const MIN_DETAIL = 10;

    /**
     * Whether the picture is plausibly of something, rather than a lens with a
     * finger over it.
     *
     * A covered camera gives a dark, flat, usually reddish frame - light
     * coming through skin - and it is a perfectly valid JPEG, so every other
     * check passes it. This measures the picture itself: how bright it is on
     * average, and how much it varies. It runs on a 32 by 32 thumbnail, so it
     * is instant and nothing leaves the server.
     *
     * What it cannot do is tell a face from a wall. It catches the lazy ways
     * round the camera, not a determined one; an admin looking at the photos
     * is still the check on who is in them.
     *
     * @return array{ok: bool, brightness: float, detail: float}
     */
    public static function quality(string $bytes): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return ['ok' => true, 'brightness' => -1.0, 'detail' => -1.0];
        }

        $src = @imagecreatefromstring($bytes);

        if (! $src) {
            return ['ok' => true, 'brightness' => -1.0, 'detail' => -1.0];
        }

        $size = 32;
        $thumb = imagecreatetruecolor($size, $size);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));

        $values = [];

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $values[] = 0.299 * (($rgb >> 16) & 0xFF)
                          + 0.587 * (($rgb >> 8) & 0xFF)
                          + 0.114 * ($rgb & 0xFF);
            }
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / count($values);
        $detail = sqrt($variance);

        return [
            'ok'         => $mean >= self::MIN_BRIGHTNESS && $detail >= self::MIN_DETAIL,
            'brightness' => round($mean, 1),
            'detail'     => round($detail, 1),
        ];
    }

    public static function requiredFor(?User $user): bool
    {
        return (bool) $user?->selfie_required;
    }

    /**
     * The photo out of the request, or why there is not one.
     *
     * @return array{ok: bool, message: ?string, bytes: ?string}
     */
    public static function fromRequest(User $user, Request $request): array
    {
        if (! self::requiredFor($user)) {
            return ['ok' => true, 'message' => null, 'bytes' => null];
        }

        $data = $request->input('selfie');

        $fail = fn (string $message) => ['ok' => false, 'message' => $message, 'bytes' => null];

        if (! is_string($data) || $data === '') {
            return $fail('A photo is needed to clock in. Please allow the camera, take your photo, and try again.');
        }

        if (! preg_match('#^data:image/jpe?g;base64,([A-Za-z0-9+/=\s]+)$#', $data, $match)) {
            return $fail('That photo could not be read. Please take it again.');
        }

        $bytes = base64_decode(preg_replace('/\s+/', '', $match[1]), true);

        if ($bytes === false || $bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            return $fail('That photo could not be used. Please take it again.');
        }

        // Whatever the header said, the bytes have to actually be a JPEG of a
        // sensible size, or anything at all could be written to disk.
        $info = @getimagesizefromstring($bytes);

        if (! $info
            || ($info[2] ?? null) !== IMAGETYPE_JPEG
            || $info[0] < self::MIN_SIDE
            || $info[1] < self::MIN_SIDE) {
            return $fail('That photo could not be used. Please take it again.');
        }

        if (! self::quality($bytes)['ok']) {
            return $fail('The photo is too dark or the camera is covered. Uncover the camera, face the light, and take it again.');
        }

        return ['ok' => true, 'message' => null, 'bytes' => $bytes];
    }

    /** Keep the photo and return where it is. */
    public static function store(User $user, string $bytes, Carbon $at): string
    {
        $path = 'selfies/'.$at->format('Y-m').'/'
            .$user->id.'-'.$at->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.jpg';

        Storage::disk(self::DISK)->put($path, $bytes);

        return $path;
    }
}
