<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'leave_id',
        'days',
        'balance_before',
        'balance_after',
        'action',
        'processed_by',
    ];

    protected $casts = [
        'days' => 'float',
        'balance_before' => 'float',
        'balance_after' => 'float',
    ];

public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}

public function leave()
{
    return $this->belongsTo(\App\Models\Leave::class);
}

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
