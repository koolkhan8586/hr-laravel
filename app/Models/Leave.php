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
        'decided_at',
        'whatsapp_notify_status',
        'whatsapp_notified_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'float',
        'calculated_days' => 'float',
        'decided_at' => 'datetime',
        'whatsapp_notified_at' => 'datetime',
    ];

    // ✅ ADD THIS
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Human-readable who decided this leave (name if known, else email/mobile).
     */
    public function decidedByLabel(): ?string
    {
        if (!filled($this->decided_by_email)) {
            return null;
        }

        $identity = trim((string) $this->decided_by_email);
        $key = strtolower($identity);

        static $cache = null;

        if ($cache === null) {
            $cache = [];
            foreach (User::query()->get(['name', 'email', 'mobile']) as $user) {
                if (filled($user->email)) {
                    $cache[strtolower((string) $user->email)] = $user->name;
                }
                if (filled($user->mobile)) {
                    $digits = preg_replace('/\D+/', '', (string) $user->mobile);
                    $cache[strtolower((string) $user->mobile)] = $user->name;
                    if ($digits) {
                        $cache[$digits] = $user->name;
                    }
                }
            }
        }

        $identityDigits = preg_replace('/\D+/', '', $identity);

        return $cache[$key]
            ?? ($identityDigits ? ($cache[$identityDigits] ?? null) : null)
            ?? $identity;
    }

    /**
     * Channel used for the decision: Dashboard / Email / WhatsApp.
     */
    public function decidedViaLabel(): ?string
    {
        return match ($this->decided_via) {
            'dashboard' => 'Dashboard',
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            default => $this->decided_via ? ucfirst($this->decided_via) : null,
        };
    }
}
