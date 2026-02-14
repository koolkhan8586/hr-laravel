<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    protected $fillable = [
        'loan_id',
        'amount_paid',
        'remaining_balance',
        'month',
        'year'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
