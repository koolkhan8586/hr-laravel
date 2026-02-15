<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalarySampleExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'employee_email',
            'month',
            'year',
            'basic_salary',
            'allowance',
            'deduction',
        ];
    }

    public function array(): array
    {
        return [
            [
                'zubair@email.com',
                'February',
                '2026',
                '70000',
                '5000',
                '2000',
            ],
        ];
    }
}
