<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Salary;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SalaryImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $user = User::where('email', $row['employee_email'])->first();

        if (!$user) {
            return null;
        }

        $basic = $row['basic_salary'] ?? 0;
        $allowance = $row['allowance'] ?? 0;
        $deduction = $row['deduction'] ?? 0;

        $net = $basic + $allowance - $deduction;

        return new Salary([
            'user_id' => $user->id,
            'month' => $row['month'],
            'year' => $row['year'],
            'basic_salary' => $basic,
            'allowance' => $allowance,
            'deduction' => $deduction,
            'net_salary' => $net,
            'status' => 'draft'
        ]);
    }
}
