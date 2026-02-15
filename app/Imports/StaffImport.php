<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StaffImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $password = 'Welcome@123';

        // ✅ FIX: Handle Excel date serial
        if (!empty($row['joining_date'])) {

            if (is_numeric($row['joining_date'])) {
                $joiningDate = Date::excelToDateTimeObject(
                    $row['joining_date']
                )->format('Y-m-d');
            } else {
                $joiningDate = date('Y-m-d',
                    strtotime($row['joining_date']));
            }

        } else {
            $joiningDate = now()->format('Y-m-d');
        }

        // Create user
        $user = User::create([
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => 'employee',
            'password' => Hash::make($password),
            'force_password_change' => true
        ]);

        // Create staff
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
                        ->subject('Welcome to HR System');
            }
        );

        return $user;
    }
}
