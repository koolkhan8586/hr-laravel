<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * A shift set against one particular date, overriding the weekly pattern.
 *
 * This is how an alternating Saturday is expressed: the weekly pattern says
 * what usually happens, and a row here says what happens on that one date -
 * including a row with no shift at all, meaning the employee is off.
 */
class EmployeeSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'shift_id'
    ];

    protected $casts = [
        'date' => 'date',
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
    | Reading and writing a date
    |--------------------------------------------------------------------------
    */

    /**
     * An employee's overrides between two dates, keyed by Y-m-d.
     *
     * Where duplicates exist the most recently updated row wins, which is the
     * last thing somebody actually saved.
     *
     * @return array<string, EmployeeSchedule>
     */
    public static function forUserBetween($userId, $from, $to): array
    {
        $rows = static::with('shift')
            ->where('user_id', $userId)
            // whereDate, not whereBetween: the date cast writes
            // "2026-10-31 00:00:00", which sorts after the plain string
            // "2026-10-31" and would drop the last day of the range.
            ->whereDate('date', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('date', '<=', Carbon::parse($to)->toDateString())
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get();

        $byDate = [];

        foreach ($rows as $row) {
            $byDate[Carbon::parse($row->date)->toDateString()] = $row;
        }

        return $byDate;
    }

    /**
     * Same, for several employees at once.
     *
     * @return array<int, array<string, EmployeeSchedule>>
     */
    public static function forUsersBetween($userIds, $from, $to): array
    {
        $rows = static::with('shift')
            ->whereIn('user_id', $userIds)
            // whereDate, not whereBetween: the date cast writes
            // "2026-10-31 00:00:00", which sorts after the plain string
            // "2026-10-31" and would drop the last day of the range.
            ->whereDate('date', '>=', Carbon::parse($from)->toDateString())
            ->whereDate('date', '<=', Carbon::parse($to)->toDateString())
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get();

        $byUser = [];

        foreach ($rows as $row) {
            $byUser[$row->user_id][Carbon::parse($row->date)->toDateString()] = $row;
        }

        return $byUser;
    }

    /**
     * Override one date, leaving exactly one row behind.
     *
     * A null shift is meaningful here: it is an employee being taken off a
     * day they would normally work.
     */
    public static function setFor($userId, $date, $shiftId): void
    {
        $date = Carbon::parse($date)->toDateString();

        $shiftId = ($shiftId === '' || $shiftId === null) ? null : (int) $shiftId;

        static::where('user_id', $userId)->whereDate('date', $date)->delete();

        static::create([
            'user_id'  => $userId,
            'date'     => $date,
            'shift_id' => $shiftId,
        ]);
    }

    /** Drop the override, putting the date back on the weekly pattern. */
    public static function clearFor($userId, $date): void
    {
        static::where('user_id', $userId)
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->delete();
    }
}
