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
| User Relationship
|--------------------------------------------------------------------------
*/

public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}

/*
|--------------------------------------------------------------------------
| Auto set date
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
