<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklySchedule extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'shift_id'
    ];

    /** Canonical spelling of each day, as stored. */
    public const DAYS = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Reading and writing a day
    |--------------------------------------------------------------------------
    |
    | The admin grid and the employee's own page used to resolve a day their
    | own way - one took the first matching row, the other the last - so a
    | duplicate or differently spelled row made the two screens disagree about
    | the same employee. Everything now goes through here.
    |
    */

    /** "saturday", " SATURDAY " and "Sat" all come back as "Saturday". */
    public static function normaliseDay($day): ?string
    {
        $needle = strtolower(trim((string) $day));

        foreach (self::DAYS as $canonical) {
            if ($needle === strtolower($canonical) || $needle === strtolower(substr($canonical, 0, 3))) {
                return $canonical;
            }
        }

        return null;
    }

    /**
     * An employee's week, keyed by canonical day name.
     *
     * Where duplicates still exist the most recently updated row wins, which
     * is the last thing somebody actually saved.
     *
     * @return array<string, WeeklySchedule>
     */
    public static function forUser($userId): array
    {
        $rows = static::with('shift')
            ->where('user_id', $userId)
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get();

        $week = [];

        foreach ($rows as $row) {
            if ($day = self::normaliseDay($row->day_of_week)) {
                $week[$day] = $row;
            }
        }

        return $week;
    }

    /**
     * Set one day for one employee, leaving exactly one row behind.
     *
     * The OFF option on the grid posts an empty string, not null, which is
     * not a shift id any foreign key will accept.
     */
    public static function setFor($userId, $day, $shiftId): void
    {
        $day = self::normaliseDay($day);

        if (!$day) {
            return;
        }

        $shiftId = ($shiftId === '' || $shiftId === null) ? null : (int) $shiftId;

        // Clear any stray rows for this day, whatever they were spelled,
        // so a leftover cannot outlive the value being saved.
        static::where('user_id', $userId)
            ->whereRaw('LOWER(TRIM(day_of_week)) = ?', [strtolower($day)])
            ->delete();

        static::create([
            'user_id'     => $userId,
            'day_of_week' => $day,
            'shift_id'    => $shiftId,
        ]);
    }
}
