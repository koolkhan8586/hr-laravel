<?php

public function collection(Collection $rows)
{
    $header = true;

    foreach ($rows as $index => $row) {

        if ($header) {
            $header = false;
            continue;
        }

        // Excel Columns:
        // 0 = user_id
        // 1 = employee_name (ignore)
        // 2 = month
        // 3 = year
        // 4 = basic_salary
        // 5 = invigilation
        // 6 = t_payment
        // 7 = eidi
        // 8 = increment
        // 9 = other_earnings
        // 10 = extra_leaves
        // 11 = income_tax
        // 12 = loan_deduction
        // 13 = insurance
        // 14 = other_deductions

        $userId = (int) $row[0];

        $user = \App\Models\User::find($userId);

        if (!$user) {
            $this->errors[] = "Row ".($index+1)." - Invalid User ID";
            continue;
        }

        // Prevent duplicate
        $exists = \App\Models\Salary::where('user_id', $userId)
            ->where('month', $row[2])
            ->where('year', $row[3])
            ->exists();

        if ($exists) {
            $this->errors[] = "Row ".($index+1)." - Salary already exists";
            continue;
        }

        $this->rows[] = [
            'user_id' => $userId,
            'month' => (int) $row[2],
            'year' => (int) $row[3],

            'basic_salary' => $row[4] ?? 0,
            'invigilation' => $row[5] ?? 0,
            't_payment' => $row[6] ?? 0,
            'eidi' => $row[7] ?? 0,
            'increment' => $row[8] ?? 0,
            'other_earnings' => $row[9] ?? 0,

            'extra_leaves' => $row[10] ?? 0,
            'income_tax' => $row[11] ?? 0,
            'loan_deduction' => $row[12] ?? 0,
            'insurance' => $row[13] ?? 0,
            'other_deductions' => $row[14] ?? 0,

            'status' => 'draft',
            'is_posted' => 0
        ];
    }
}
