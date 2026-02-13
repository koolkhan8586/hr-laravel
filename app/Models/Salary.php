<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'year',
        'basic_salary',
        'invigilation',
        't_payment',
        'Others',
        'Eidi',
        'increment',
        'extra_leaves',
        'income_tax',
        'loan_deduction',
        'insurance',
        'others',
        'gross_total',
        'total_deductions',
        'net_salary',
        'is_posted'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
