<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSheet extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'annual_salary',
        'salary_overridden',
        'additional_income',
        'tax_adjustment',
    ];

    protected $casts = [
        'annual_salary'     => 'float',
        'salary_overridden' => 'boolean',
        'additional_income' => 'float',
        'tax_adjustment'    => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Medical allowance is exempt from salary, so only the rest is taxed.
     * Additional income is already a yearly figure and carries no medical
     * component, so it is taxed in full.
     */
    public function taxableIncome(float $medicalDivisor = 1.1): float
    {
        $fromSalary = $medicalDivisor > 0
            ? $this->annual_salary / $medicalDivisor
            : $this->annual_salary;

        return round($fromSalary + $this->additional_income, 2);
    }
}
