<?php

namespace App\Imports;

use App\Models\Salary;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SalaryImport implements ToCollection
{
    public array $rows = [];
    public array $errors = [];

    public function collection(Collection $rows)
    {
        $header = true;

        foreach ($rows as $index => $row) {

            if ($header) {
                $header = false;
                continue;
            }

            $userId = (int) $row[0];

            $user = \App\Models\User::find($userId);

            if (!$user) {
                $this->errors[] = "Row ".($index+1)." - Invalid User ID ({$userId})";
                continue;
            }

            // Duplicate prevention
            $exists = \App\Models\Salary::where('user_id', $userId)
                ->where('month', $row[1])
                ->where('year', $row[2])
                ->exists();

            if ($exists) {
                $this->errors[] = "Row ".($index+1)." - Salary already exists for this month/year";
                continue;
            }

            $this->rows[] = [
                'user_id' => $userId,
                'month'   => $row[1],
                'year'    => $row[2],
                'basic_salary' => $row[3] ?? 0,
                'invigilation' => $row[4] ?? 0,
                't_payment'    => $row[5] ?? 0,
                'eidi'         => $row[6] ?? 0,
                'increment'    => $row[7] ?? 0,
                'other_earnings' => $row[8] ?? 0,
                'extra_leaves'   => $row[9] ?? 0,
                'income_tax'     => $row[10] ?? 0,
                'loan_deduction' => $row[11] ?? 0,
                'insurance'      => $row[12] ?? 0,
                'other_deductions' => $row[13] ?? 0,
                'status' => 'draft',
                'is_posted' => 0
            ];
        }
    }
}
