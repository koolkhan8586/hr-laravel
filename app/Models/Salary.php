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
        'extra_load',
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

        // NEW (add these)
        'gross_total',
        'total_deductions',

        // Final
        'net_salary',
        'cheque_amount',
        'custom_values',

        // Status
        'is_posted',
        'status',
        'posted_at',
    ];

    protected $casts = [
        'is_posted'     => 'boolean',
        'posted_at'     => 'datetime',
        'custom_values' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($salary) {

            $totalEarnings =
                ($salary->basic_salary ?? 0) +
                ($salary->extra_load ?? 0) +
                ($salary->invigilation ?? 0) +
                ($salary->t_payment ?? 0) +
                ($salary->eidi ?? 0) +
                ($salary->increment ?? 0) +
                ($salary->other_earnings ?? 0);

            $totalDeductions =
                ($salary->extra_leaves ?? 0) +
                ($salary->income_tax ?? 0) +
                ($salary->loan_deduction ?? 0) +
                ($salary->insurance ?? 0) +
                ($salary->other_deductions ?? 0);

            // Admin-defined columns contribute to the same two buckets.
            $totalEarnings   += $salary->customTotal('earning');
            $totalDeductions += $salary->customTotal('deduction');

            // SAVE THESE ALSO
            $salary->gross_total = $totalEarnings;
            $salary->total_deductions = $totalDeductions;

            $salary->net_salary = $totalEarnings - $totalDeductions;

            // Sync status
            $salary->is_posted = $salary->status === 'posted';
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        $this->update([
            'status'    => 'posted',
            'is_posted' => true,
            'posted_at' => now(),
        ]);
    }

    public function unpost()
    {
        $this->update([
            'status'    => 'draft',
            'is_posted' => false,
            'posted_at' => null,
        ]);
    }

    /**
     * Value stored against one admin-defined column.
     */
    public function customValue($columnId)
    {
        return (float) (($this->custom_values ?? [])[$columnId] ?? 0);
    }

    /**
     * Sum of the admin-defined columns of a given type ("earning"/"deduction").
     */
    public function customTotal(string $type): float
    {
        $values = $this->custom_values ?? [];

        if (empty($values)) {
            return 0.0;
        }

        // Memoised per request: a sheet save runs this once per row.
        static $idsByType = null;

        if ($idsByType === null) {
            $idsByType = SalaryColumn::all(['id', 'type'])
                ->groupBy('type')
                ->map(fn ($rows) => $rows->pluck('id')->all())
                ->all();
        }

        $ids = $idsByType[$type] ?? [];

        $total = 0.0;

        foreach ($values as $columnId => $amount) {
            if (in_array((int) $columnId, $ids)) {
                $total += (float) $amount;
            }
        }

        return $total;
    }

    /**
     * Amount actually transferred through the bank sheet: whatever is left
     * of the net salary once the cheque-paid portion is taken out.
     */
    public function getBankAmountAttribute()
    {
        return max(0, ($this->net_salary ?? 0) - ($this->cheque_amount ?? 0));
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isPosted()
    {
        return $this->status === 'posted';
    }
}
