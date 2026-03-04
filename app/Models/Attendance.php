<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
    | Automatically set date when clock_in is saved
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attendance) {

            if (!$attendance->date && $attendance->clock_in) {
                $attendance->date = Carbon::parse($attendance->clock_in)->toDateString();
            }

        });
    }
}
