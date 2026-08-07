<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReportWhatsappNumber extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
