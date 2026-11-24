<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Attendance extends Model
{

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        // inside | override | anywhere: why the location check let this mark
        // through. It was never listed here, so create() quietly threw it
        // away and every first clock-in was saved without it.
        'location_status',
        'status',
        'total_hours',
        // Set by Allow Overtime: the day ends later than the shift says, so
        // nothing is late or missing until this has passed.
        'overtime_allowed_until',

        // What marked it. Location alone cannot catch somebody who connects
        // to an office computer from home: the machine is in the office, so
        // its position and its address are both right. The machine is what
        // gives it away.
        'device_id',
        'device_kind',
        'device_ip',
        'device_agent',

        // Who put it on the day: the employee themselves, or an admin by hand.
        'marked_via',
        'marked_by',
        'edited_by',
        'edited_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | User Relationship
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method (PROTECTED + TRACKING)
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        /*
        |--------------------------------------------------------------------------
        | On CREATE
        |--------------------------------------------------------------------------
        */

        static::creating(function ($attendance) {

            if (!$attendance->date && $attendance->clock_in) {
                $attendance->date = Carbon::parse($attendance->clock_in)->toDateString();
            }

        });

        /*
        |--------------------------------------------------------------------------
        | On UPDATE (CRITICAL FIX)
        |--------------------------------------------------------------------------
        */

        static::updating(function ($attendance) {

            // 🔍 LOG WHO IS UPDATING (DEBUG)
            Log::info('ATTENDANCE UPDATE DETECTED', [
                'user_id' => $attendance->user_id,
                'old_clock_in' => $attendance->getOriginal('clock_in'),
                'new_clock_in' => $attendance->clock_in,
                'old_clock_out' => $attendance->getOriginal('clock_out'),
                'new_clock_out' => $attendance->clock_out,
                'updated_at' => now(),
                'trigger' => app()->runningInConsole() ? 'CLI / Scheduler' : request()->fullUrl(),
            ]);

            // 🔒 PREVENT clock_in CHANGE AFTER FIRST SAVE (unless updated by admin or console/seeder)
            if ($attendance->isDirty('clock_in')) {
                $isConsole = app()->runningInConsole();
                $isAdmin = auth()->check() && auth()->user()->isAdmin();
                if (!$isConsole && !$isAdmin) {
                    // restore original value
                    $attendance->clock_in = $attendance->getOriginal('clock_in');
                }
            }

        });
    }

}
