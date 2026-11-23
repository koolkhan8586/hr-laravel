<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * What one employee's missed time cost them in one month.
 *
 * Written once, at the end of the month, by the job that totals up the
 * minutes. Its real job is to be the thing that already exists: running the
 * month again finds this row and leaves it alone, so nobody is ever charged
 * twice for the same November.
 *
 * The minutes are kept alongside the days so the figure can always be
 * checked by hand - nine hours and twenty minutes is why one day came off,
 * and an employee asking "why" can be shown the arithmetic rather than a
 * number.
 */
class ShortfallCharge extends Model
{
    protected $table = 'attendance_shortfall_charges';

    protected $fillable = [
        'user_id',
        'month',
        'minutes',
        'late_minutes',
        'early_minutes',
        'missing_minutes',
        'days_charged',
        'days_from_balance',
        'days_without_pay',
        'charged_at',
    ];

    protected function casts(): array
    {
        return [
            'month'             => 'date',
            'charged_at'        => 'datetime',
            'days_charged'      => 'float',
            'days_from_balance' => 'float',
            'days_without_pay'  => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** "9 h 20 m". */
    public function timeLabel(): string
    {
        return \App\Support\TimeShortfall::label((int) $this->minutes);
    }
}
