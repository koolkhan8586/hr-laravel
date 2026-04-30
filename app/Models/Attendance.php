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
        'status',
        'total_hours'
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

            // 🔒 PREVENT clock_in CHANGE AFTER FIRST SAVE
            if ($attendance->isDirty('clock_in')) {

                // restore original value
                $attendance->clock_in = $attendance->getOriginal('clock_in');
            }

        });
    }

}
