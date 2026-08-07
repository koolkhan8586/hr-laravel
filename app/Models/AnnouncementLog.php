<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementLog extends Model
{
    protected $fillable = [
        'sent_by',
        'subject',
        'message',
        'via_whatsapp',
        'via_email',
        'audience',
        'user_ids',
        'whatsapp_sent',
        'whatsapp_failed',
        'email_sent',
        'email_failed',
    ];

    protected $casts = [
        'via_whatsapp' => 'boolean',
        'via_email' => 'boolean',
        'user_ids' => 'array',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
