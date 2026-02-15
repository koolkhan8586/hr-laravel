<?php

namespace App\Imports;

use App\Models\Salary;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class SalaryImport implements ToCollection
{
    public $rows = [];
    public $errors = [];

    public function collection(Collection $collection)
    {
        foreach ($collection as $index => $row) {

            if ($index == 0) continue; // skip header

            $email = $row[0] ?? null;

            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->errors[] = "Row " . ($index + 1) . " - Employee not found";
                continue;
            }

            if (!$row[1] || !$row[2]) {
                $this->errors[] = "Row " . ($index + 1) . " - Month/Year missing";
                continue;
            }

            $basic = $row[3] ?? 0;
            $allowance = $row[4] ?? 0;
            $deduction = $row[5] ?? 0;

            $net = $basic + $allowance - $deduction;

            $this->rows[] = [
                'user_id' => $user->id,
                'month' => $row[1],
                'year' => $row[2],
                'basic_salary' => $basic,
                'allowance' => $allowance,
                'deduction' => $deduction,
                'net_salary' => $net,
                'status' => 'draft'
            ];
        }
    }
}
