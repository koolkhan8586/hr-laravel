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
        'decided_via',
        'decided_by_email',
        'whatsapp_notify_status',
        'whatsapp_notified_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'float',
        'calculated_days' => 'float',
        'whatsapp_notified_at' => 'datetime',
    ];

    // ✅ ADD THIS
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
