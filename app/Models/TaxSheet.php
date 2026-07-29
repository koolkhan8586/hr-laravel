<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSheet extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'annual_salary',
        'tax_adjustment',
    ];

    protected $casts = [
        'annual_salary'  => 'float',
        'tax_adjustment' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Medical allowance is exempt, so the taxable part is the rest. */
    public function taxableIncome(float $medicalDivisor = 1.1): float
    {
        if ($medicalDivisor <= 0) {
            return $this->annual_salary;
        }

        return round($this->annual_salary / $medicalDivisor, 2);
    }
}
