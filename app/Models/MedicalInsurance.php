<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalInsurance extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'total_amount',
        'total_formula',
        'lsaf_portion',
        'employee_portion',
    ];

    protected $casts = [
        'year'              => 'integer',
        'total_amount'      => 'float',
        'lsaf_portion'      => 'float',
        'employee_portion'  => 'float',
    ];

    /** First month this feature applies: August 2026 onward. */
    public const START_YEAR  = 2026;
    public const START_MONTH = 8;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Calendar months shown on the yearly sheet (2026 starts in August). */
    public static function monthsForYear(int $year): array
    {
        if ($year < self::START_YEAR) {
            return [];
        }

        if ($year === self::START_YEAR) {
            return range(self::START_MONTH, 12);
        }

        return range(1, 12);
    }

    /** Salary sheets before August 2026 do not carry medical insurance. */
    public static function appliesToSalaryMonth(int $year, int $month): bool
    {
        if ($year > self::START_YEAR) {
            return true;
        }

        return $year === self::START_YEAR && $month >= self::START_MONTH;
    }

    /**
     * Split a yearly premium 50/50 between LSAF and the employee.
     *
     * The two halves always add back to the total, even when the figure
     * does not divide evenly into paisa. The monthly figure is the
     * employee's half spread over 12 months, matching the tax sheet.
     *
     * @return array{
     *   total_amount: float,
     *   lsaf_portion: float,
     *   employee_portion: float,
     *   monthly_portion: float
     * }
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
            'monthly_portion'  => static::monthlyPortion($employee),
        ];
    }

    /** Employee half spread across the year, for the salary sheet. */
    public static function monthlyPortion(float $employeeYearly): float
    {
        return round($employeeYearly / 12, 2);
    }
}
