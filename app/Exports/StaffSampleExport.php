<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StaffSampleExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'name',
            'email',
            'employee_id',
            'department',
            'designation',
            'salary'
        ];
    }

    public function array(): array
    {
        return [
            [
                'John Doe',
                'john@example.com',
                'EMP001',
                'HR',
                'Manager',
                '50000'
            ]
        ];
    }
}
