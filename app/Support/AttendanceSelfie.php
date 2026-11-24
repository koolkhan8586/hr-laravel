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
