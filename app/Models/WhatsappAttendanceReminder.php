<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappAttendanceReminder extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'shift_start',
        'mobile',
        'chat_id',
        'message',
        'status',
        'response',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
