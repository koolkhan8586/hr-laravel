<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'duration',
        'duration_type',
        'half_day_type',
        'days',
        'calculated_days',
        'reason',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'float',
        'calculated_days' => 'float',
    ];

    // ✅ ADD THIS
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
