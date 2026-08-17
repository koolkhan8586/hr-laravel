<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalInsurance extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'year',
        'total_amount',
        'lsaf_portion',
        'employee_portion',
    ];

    protected $casts = [
        'month'             => 'integer',
        'year'              => 'integer',
        'total_amount'      => 'float',
        'lsaf_portion'      => 'float',
        'employee_portion'  => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Split a premium 50/50 between LSAF and the employee.
     *
     * The two halves always add back to the total, even when the figure
     * does not divide evenly into paisa.
     *
     * @return array{total_amount: float, lsaf_portion: float, employee_portion: float}
     */
    public static function splitTotal(float $total): array
    {
        $total    = round($total, 2);
        $employee = round($total / 2, 2);
        $lsaf     = round($total - $employee, 2);

        return [
            'total_amount'     => $total,
            'lsaf_portion'     => $lsaf,
            'employee_portion' => $employee,
        ];
    }
}
