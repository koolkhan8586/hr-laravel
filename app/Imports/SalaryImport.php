<?php

namespace App\Imports;

use App\Models\Salary;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class SalaryImport implements ToCollection, WithHeadingRow
{
    public $errors = [];
    public $rows = [];

    public function collection(Collection $collection)
    {
        foreach ($collection as $index => $row) {

            $user = User::where('employee_id', $row['employee_id'] ?? null)
                        ->orWhere('id', $row['employee_id'] ?? null)
                        ->first();

            if (!$user) {
                $this->errors[] = "Row ".($index+2)." - Employee not found";
                continue;
            }

            // Prevent duplicate salary
            $exists = Salary::where('user_id', $user->id)
                ->where('month', $row['month'])
                ->where('year', $row['year'])
                ->exists();

            if ($exists) {
                $this->errors[] = "Row ".($index+2)." - Salary already exists";
                continue;
            }

            // Earnings
            $gross =
                ($row['basic_salary'] ?? 0)
                + ($row['invigilation'] ?? 0)
                + ($row['t_payment'] ?? 0)
                + ($row['eidi'] ?? 0)
                + ($row['increment'] ?? 0)
                + ($row['other_earnings'] ?? 0);

            // Deductions
            $totalDeductions =
                ($row['extra_leaves'] ?? 0)
                + ($row['income_tax'] ?? 0)
                + ($row['loan_deduction'] ?? 0)
                + ($row['insurance'] ?? 0)
                + ($row['other_deductions'] ?? 0);

            $net = $gross - $totalDeductions;

            $this->rows[] = [
                'user_id' => $user->id,
                'month' => $row['month'],
                'year' => $row['year'],

                'basic_salary' => $row['basic_salary'] ?? 0,
                'invigilation' => $row['invigilation'] ?? 0,
                't_payment' => $row['t_payment'] ?? 0,
                'eidi' => $row['eidi'] ?? 0,
                'increment' => $row['increment'] ?? 0,
                'other_earnings' => $row['other_earnings'] ?? 0,

                'extra_leaves' => $row['extra_leaves'] ?? 0,
                'income_tax' => $row['income_tax'] ?? 0,
                'loan_deduction' => $row['loan_deduction'] ?? 0,
                'insurance' => $row['insurance'] ?? 0,
                'other_deductions' => $row['other_deductions'] ?? 0,

                'gross_total' => $gross,
                'total_deductions' => $totalDeductions,
                'net_salary' => $net,

                'is_posted' => false, // IMPORTANT
                'status' => 'draft'
            ];
        }
    }
}
