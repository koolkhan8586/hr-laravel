<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Somebody who is told about a loan application on WhatsApp and may decide it.
 */
class LoanApprover extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Everybody currently set to receive loan applications. */
    public static function active()
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }
}
