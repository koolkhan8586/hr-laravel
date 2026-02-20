<?php

namespace App\Imports;

use App\Models\Salary;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SalaryImport implements ToCollection
{
    public array $errors = [];

    public function collection(Collection $rows)
    {
        $header = true;

        foreach ($rows as $index => $row) {

            if ($header) {
                $header = false;
                continue;
            }

            $employeeCode = trim($row[0] ?? null);

            if (!$employeeCode) {
                $this->errors[] = "Row ".($index+1)." - Employee ID missing";
                continue;
            }

            // 🔥 FIND BY employee_id COLUMN
            $user = User::where('employee_id', $employeeCode)->first();

            if (!$user) {
                $this->errors[] = "Row ".($index+1)." - Employee ID not found ({$employeeCode})";
                continue;
            }

            $month = (int)($row[1] ?? 0);
            $year  = (int)($row[2] ?? 0);

            if (!$month || !$year) {
                $this->errors[] = "Row ".($index+1)." - Invalid month/year";
                continue;
            }

            $exists = Salary::where('user_id', $user->id)
                ->where('month', $month)
                ->where('year', $year)
                ->exists();

            if ($exists) {
                $this->errors[] = "Row ".($index+1)." - Salary already exists";
                continue;
            }

            Salary::create([
                'user_id'          => $user->id,
                'month'            => $month,
                'year'             => $year,
                'basic_salary'     => (float)($row[3] ?? 0),
                'invigilation'     => (float)($row[4] ?? 0),
                't_payment'        => (float)($row[5] ?? 0),
                'eidi'             => (float)($row[6] ?? 0),
                'increment'        => (float)($row[7] ?? 0),
                'other_earnings'   => (float)($row[8] ?? 0),
                'extra_leaves'     => (float)($row[9] ?? 0),
                'income_tax'       => (float)($row[10] ?? 0),
                'loan_deduction'   => (float)($row[11] ?? 0),
                'insurance'        => (float)($row[12] ?? 0),
                'other_deductions' => (float)($row[13] ?? 0),
                'is_posted'        => 0
            ]);
        }
    }
}
