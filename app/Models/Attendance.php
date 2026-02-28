<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',

        // Time fields
        'clock_in',
        'clock_out',

        // Clock In Location
        'clock_in_latitude',
        'clock_in_longitude',

        // Clock Out Location
        'clock_out_latitude',
        'clock_out_longitude',

        // Other fields
        'total_hours',
        'status'
    ];

    protected $casts = [
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
