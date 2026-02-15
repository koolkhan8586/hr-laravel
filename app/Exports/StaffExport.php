<?php

namespace App\Exports;

use App\Models\Staff;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Staff::with('user')->get()->map(function ($staff) {
            return [
                'Employee ID' => $staff->employee_id,
                'Name'        => $staff->user->name ?? '',
                'Email'       => $staff->user->email ?? '',
                'Department'  => $staff->department,
                'Designation' => $staff->designation,
                'Salary'      => $staff->salary,
                'Joining Date'=> $staff->joining_date,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Email',
            'Department',
            'Designation',
            'Salary',
            'Joining Date'
        ];
    }
}
