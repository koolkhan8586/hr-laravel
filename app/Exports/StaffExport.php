<?php

namespace App\Exports;

use App\Models\Staff;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffExport implements FromCollection, WithHeadings
{
    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Email',
            'Department',
            'Designation',
            'Salary',
            'Joining Date',
            'Status'
        ];
    }

    public function collection()
    {
        return Staff::with('user')->get()->map(function ($staff) {
            return [
                $staff->employee_id,
                $staff->user->name,
                $staff->user->email,
                $staff->department,
                $staff->designation,
                $staff->salary,
                $staff->joining_date,
                $staff->status
            ];
        });
    }
}
