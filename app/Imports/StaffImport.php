<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StaffImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Safe fallback if column missing
        $joiningDate = $row['joining_date'] ?? now();

        $password = 'Welcome@123';

        $user = User::create([
            'name' => $row['name'] ?? 'No Name',
            'email' => $row['email'],
            'role' => 'employee',
            'password' => Hash::make($password),
            'force_password_change' => true
        ]);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $row['employee_id'] ?? null,
            'department' => $row['department'] ?? null,
            'designation' => $row['designation'] ?? null,
            'salary' => $row['salary'] ?? 0,
            'joining_date' => $joiningDate,
            'status' => 'active'
        ]);

        // Send welcome email
        Mail::raw(
            "Welcome to HR System\n\nLogin URL: " . url('/login') .
            "\nEmail: {$row['email']}\nPassword: {$password}",
            function ($message) use ($row) {
                $message->to($row['email'])
                        ->subject('Welcome to Company');
            }
        );

        return $user;
    }
}
