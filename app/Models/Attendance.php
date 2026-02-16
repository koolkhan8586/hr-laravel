<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'latitude',
        'longitude',
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


