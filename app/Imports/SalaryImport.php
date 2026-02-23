<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Salary;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SalaryImport implements ToCollection
{
    public $rows = [];
    public $errors = [];

    public function collection(Collection $rows)
    {
        $header = true;

        foreach ($rows as $index => $row) {

            // Skip header row
            if ($header) {
                $header = false;
                continue;
            }

            /*
            Excel Format Should Be:

            0 = employee_code (EMP008)
            1 = month (1-12)
            2 = year
            3 = basic_salary
            4 = invigilation
            5 = t_payment
            6 = eidi
            7 = increment
            8 = other_earnings
            9 = extra_leaves
            10 = income_tax
            11 = loan_deduction
            12 = insurance
            13 = other_deductions
            */

            $employeeCode = trim($row[0]);

            $user = User::where('employee_code', $employeeCode)->first();

            if (!$user) {
                $this->errors[] = "Row ".($index+1)." - Invalid Employee Code: {$employeeCode}";
                continue;
            }

            $month = (int) $row[1];
            $year  = (int) $row[2];

            // Prevent duplicate salary
            $exists = Salary::where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($exists) {
                $this->errors[] = "Row ".($index+1)." - Salary already exists for {$employeeCode}";
                continue;
            }

            // Calculate earnings
            $basic          = $row[3] ?? 0;
            $invigilation   = $row[4] ?? 0;
            $t_payment      = $row[5] ?? 0;
            $eidi           = $row[6] ?? 0;
            $increment      = $row[7] ?? 0;
            $other_earnings = $row[8] ?? 0;

            $extra_leaves   = $row[9] ?? 0;
            $income_tax     = $row[10] ?? 0;
            $loan_deduction = $row[11] ?? 0;
            $insurance      = $row[12] ?? 0;
            $other_deductions = $row[13] ?? 0;

            $gross_total =
                $basic +
                $invigilation +
                $t_payment +
                $eidi +
                $increment +
                $other_earnings;

            $total_deductions =
                $extra_leaves +
                $income_tax +
                $loan_deduction +
                $insurance +
                $other_deductions;

            $net_salary = $gross_total - $total_deductions;

            $this->rows[] = [
                'user_id' => $user->id,
                'month' => $month,
                'year' => $year,

                'basic_salary' => $basic,
                'invigilation' => $invigilation,
                't_payment' => $t_payment,
                'eidi' => $eidi,
                'increment' => $increment,
                'other_earnings' => $other_earnings,

                'extra_leaves' => $extra_leaves,
                'income_tax' => $income_tax,
                'loan_deduction' => $loan_deduction,
                'insurance' => $insurance,
                'other_deductions' => $other_deductions,

                'gross_total' => $gross_total,
                'total_deductions' => $total_deductions,
                'net_salary' => $net_salary,

                'status' => 'draft',
                'is_posted' => 0
            ];
        }
    }
}
