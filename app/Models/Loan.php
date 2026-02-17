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
        'status'
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

}
