<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class SalarySampleExport implements FromArray
{
    public function array(): array
    {
        return [
            [
                'employee_email',
                'month',
                'year',
                'basic_salary',
                'allowance',
                'deduction'
            ],
            [
                'employee@email.com',
                'February',
                '2026',
                '50000',
                '5000',
                '2000'
            ]
        ];
    }
}
