<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [

        'user_id',
        'month',
        'year',

        // Earnings
        'basic_salary',
        'invigilation',
        't_payment',
        'eidi',
        'increment',
        'other_earnings',

        // Deductions
        'extra_leaves',
        'income_tax',
        'loan_deduction',
        'insurance',
        'other_deductions',

        // Totals
        'gross_total',
        'total_deductions',
        'net_salary',

        'is_posted'
    ];

    protected $casts = [
        'is_posted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
