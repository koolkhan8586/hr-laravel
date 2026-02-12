<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StaffImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $password = '12345678';

        $user = User::create([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => Hash::make($password),
            'role' => 'employee',
            'annual_leave_balance' => 14
        ]);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $row['employee_id'],
            'department' => $row['department'],
            'designation' => $row['designation'],
            'salary' => $row['salary'],
            'joining_date' => $row['joining_date'],
        ]);

        return $user;
    }
}
