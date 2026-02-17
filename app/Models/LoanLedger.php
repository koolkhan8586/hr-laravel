<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanLedger extends Model
{
    protected $fillable = [
        'loan_id',
        'amount',
        'type',
        'remarks'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
