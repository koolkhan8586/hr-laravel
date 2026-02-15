<?php

namespace App\Imports;

use App\Models\Salary;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;

class SalaryImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $rowNumber = 1;

        foreach ($rows as $row) {

            // Skip header row
            if ($rowNumber == 1) {
                $rowNumber++;
                continue;
            }

            $employeeEmail = trim($row[0] ?? null);
            $month         = trim($row[1] ?? null);
            $year          = trim($row[2] ?? null);
            $basic         = $row[3] ?? 0;
            $allowance     = $row[4] ?? 0;
            $deduction     = $row[5] ?? 0;

            // Validate required fields
            if (!$employeeEmail || !$month || !$year) {
                session()->flash('error', "Row {$rowNumber} - Missing required fields.");
                return;
            }

            // Find employee by email
            $user = User::where('email', $employeeEmail)
                        ->where('role', 'employee')
                        ->first();

            if (!$user) {
                session()->flash('error', "Row {$rowNumber} - Employee not found.");
                return;
            }

            // Convert month name to number if needed
            if (!is_numeric($month)) {
                try {
                    $month = Carbon::parse("1 {$month} {$year}")->month;
                } catch (\Exception $e) {
                    session()->flash('error', "Row {$rowNumber} - Invalid month format.");
                    return;
                }
            }

            $netSalary = ($basic + $allowance) - $deduction;

            Salary::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'month'   => $month,
                    'year'    => $year,
                ],
                [
                    'basic_salary' => $basic,
                    'allowance'    => $allowance,
                    'deduction'    => $deduction,
                    'net_salary'   => $netSalary,
                    'is_posted'    => false
                ]
            );

            $rowNumber++;
        }
    }
}
