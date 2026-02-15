<?php

namespace App\Imports;

use App\Models\Salary;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SalaryImport implements ToCollection
{
    public array $errors = [];

    public function collection(Collection $rows)
    {
        $header = true;

        foreach ($rows as $index => $row) {

            // Skip header row
            if ($header) {
                $header = false;
                continue;
            }

            $employeeCode = trim($row[0] ?? null); // Employee ID column

            if (!$employeeCode) {
                $this->errors[] = "Row ".($index+1)." - Employee ID missing";
                continue;
            }

            // 🔹 Find staff by employee_id
            $staff = Staff::where('employee_id', $employeeCode)->first();

            if (!$staff) {
                $this->errors[] = "Row ".($index+1)." - Employee not found ({$employeeCode})";
                continue;
            }

            // Prevent duplicate salary
            $exists = Salary::where('user_id', $staff->user_id)
                ->where('month', $row[1])
                ->where('year', $row[2])
                ->exists();

            if ($exists) {
                $this->errors[] = "Row ".($index+1)." - Salary already exists";
                continue;
            }

            // 🔹 Salary calculations
            $basic        = (float)($row[3] ?? 0);
            $invigilation = (float)($row[4] ?? 0);
            $tPayment     = (float)($row[5] ?? 0);
            $eidi         = (float)($row[6] ?? 0);
            $increment    = (float)($row[7] ?? 0);
            $otherEarn    = (float)($row[8] ?? 0);

            $extraLeave   = (float)($row[9] ?? 0);
            $tax          = (float)($row[10] ?? 0);
            $loan         = (float)($row[11] ?? 0);
            $insurance    = (float)($row[12] ?? 0);
            $otherDed     = (float)($row[13] ?? 0);

            $gross = $basic + $invigilation + $tPayment + $eidi + $increment + $otherEarn;
            $deductions = $extraLeave + $tax + $loan + $insurance + $otherDed;
            $net = $gross - $deductions;

            Salary::create([
                'user_id'          => $staff->user_id,
                'month'            => $row[1],
                'year'             => $row[2],
                'basic_salary'     => $basic,
                'invigilation'     => $invigilation,
                't_payment'        => $tPayment,
                'eidi'             => $eidi,
                'increment'        => $increment,
                'other_earnings'   => $otherEarn,
                'extra_leaves'     => $extraLeave,
                'income_tax'       => $tax,
                'loan_deduction'   => $loan,
                'insurance'        => $insurance,
                'other_deductions' => $otherDed,
                'gross_salary'     => $gross,
                'total_deductions' => $deductions,
                'net_salary'       => $net,
                'status'           => 'draft', // IMPORTANT
            ]);
        }
    }
}
