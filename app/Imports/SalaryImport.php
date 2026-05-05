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

    /**
     * ✅ Clean numeric values (handles commas, empty, NaN)
     */
    private function cleanNumber($value)
    {
        if (is_null($value) || $value === '' || strtolower($value) === 'nan') {
            return 0;
        }

        return (float) str_replace(',', '', $value);
    }

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
            Excel Format:

            0 = employee_code
            1 = month
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

            $employeeCode = trim($row[0] ?? '');

            if (!$employeeCode) {
                $this->errors[] = "Row ".($index+1)." - Empty Employee Code";
                continue;
            }

            $user = User::where('employee_code', $employeeCode)->first();

            if (!$user) {
                $this->errors[] = "Row ".($index+1)." - Invalid Employee Code: {$employeeCode}";
                continue;
            }

            $month = (int) ($row[1] ?? 0);
            $year  = (int) ($row[2] ?? 0);

            if (!$month || !$year) {
                $this->errors[] = "Row ".($index+1)." - Invalid Month/Year";
                continue;
            }

            // Prevent duplicate salary
            $exists = Salary::where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($exists) {
                $this->errors[] = "Row ".($index+1)." - Salary already exists for {$employeeCode}";
                continue;
            }

            // ✅ Earnings (FIXED)
            $basic          = $this->cleanNumber($row[3] ?? 0);
            $invigilation   = $this->cleanNumber($row[4] ?? 0);
            $t_payment      = $this->cleanNumber($row[5] ?? 0);
            $eidi           = $this->cleanNumber($row[6] ?? 0);
            $increment      = $this->cleanNumber($row[7] ?? 0);
            $other_earnings = $this->cleanNumber($row[8] ?? 0);

            // ✅ Deductions (FIXED)
            $extra_leaves     = $this->cleanNumber($row[9] ?? 0);
            $income_tax       = $this->cleanNumber($row[10] ?? 0);
            $loan_deduction   = $this->cleanNumber($row[11] ?? 0);
            $insurance        = $this->cleanNumber($row[12] ?? 0);
            $other_deductions = $this->cleanNumber($row[13] ?? 0);

            // ✅ Calculations (SAFE NOW)
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
