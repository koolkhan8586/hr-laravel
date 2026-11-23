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
        // Set when the system charged this leave itself - leaving early, or
        // never marking a clock-out - rather than somebody applying for it.
        'auto_reason',
        'auto_charged_at',
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
        'auto_charged_at' => 'datetime',
        'whatsapp_notified_at' => 'datetime',
    ];

    /** Charged by the system rather than applied for. */
    /**
     * An approved leave covers the day, so a half day the system charged for
     * the same day is taken back.
     *
     * Somebody who applies for a half day and clocks out at one o'clock is
     * leaving when they were given leave to. If the clock-out came before the
     * approval, the system had already charged half a day for it, and the two
     * stacked: the same hours paid for twice.
     */
    protected static function booted(): void
    {
        static::saved(function (Leave $leave) {

            if ($leave->status === 'approved' && ! $leave->isAutoCharged()) {
                \App\Support\HalfDayCharge::releaseCoveredBy($leave);
            }
        });
    }

    public function isAutoCharged(): bool
    {
        return filled($this->auto_reason);
    }

    /** "Left early" / "No clock-out marked", for the screens. */
    public function autoReasonLabel(): ?string
    {
        return $this->isAutoCharged()
            ? \App\Support\AttendanceRules::label($this->auto_reason)
            : null;
    }

    /**
     * Where this half day was taken from, in a few words.
     *
     * Annual comes off the leave balance and shows on the transaction
     * report; without pay comes off the salary instead, and never reaches
     * that report because no balance moved.
     */
    public function chargedAgainstLabel(): ?string
    {
        if (!$this->isAutoCharged()) {
            return null;
        }

        return $this->type === 'annual'
            ? 'Taken off the leave balance'
            : 'No balance left, so taken without pay';
    }

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
        // Charged by the system rather than decided by a person, so say so
        // instead of leaving a dash where the approver's name goes.
        if ($this->isAutoCharged()) {
            return 'System';
        }

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
        if ($this->isAutoCharged()) {
            return $this->autoReasonLabel();
        }

        return match ($this->decided_via) {
            'dashboard' => 'Dashboard',
            'email' => 'Email',
            'whatsapp' => 'WhatsApp',
            default => $this->decided_via ? ucfirst($this->decided_via) : null,
        };
    }
}
