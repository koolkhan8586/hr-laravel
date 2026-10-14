<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\LoanPayment;

class Loan extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'opening_balance',
        'installments',
        'monthly_deduction',
        'remaining_balance',
        'status',
        'loan_date',
        'purpose',
        'deduction_start_month',
        'requested_at',
        'decided_by',
        'decided_via',
        'decided_at',
        'decision_remarks',
        'whatsapp_notify_status',
        'whatsapp_notified_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
        'whatsapp_notified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function ledgers()
{
    return $this->hasMany(\App\Models\LoanLedger::class);
}

    /**
     * An application the employee submitted themselves, rather than a balance
     * an admin entered on their behalf.
     *
     * An opening balance or a loan an admin adds is created already approved,
     * so anything still pending came in through the apply form.
     */
    public function isApplication(): bool
    {
        return (bool) $this->requested_at;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** "LN-000123", for the printed application. */
    public function reference(): string
    {
        return 'LN-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Applications only, newest first - what the approvers screen lists.
     */
    public function scopeApplications($query)
    {
        return $query->whereNotNull('requested_at');
    }
}
