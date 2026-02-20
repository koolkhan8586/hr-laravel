<?php

namespace App\Exports;

use App\Models\Salary;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalariesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Salary::with('user')->get()->map(function ($salary) {
            return [
                'Employee ID'     => $salary->user->id ?? '',
                'Employee' => $salary->user->name ?? '',
                'Month' => $salary->month,
                'Year' => $salary->year,
                'Basic Salary' => $salary->basic_salary,
                'Invigilation' => $salary->invigilation,
                'T Payment' => $salary->t_payment,
                'Eidi' => $salary->eidi,
                'Increment' => $salary->increment,
                'Other Earnings' => $salary->other_earnings,
                'Extra Leaves' => $salary->extra_leaves,
                'Income Tax' => $salary->income_tax,
                'Loan Deduction' => $salary->loan_deduction,
                'Insurance' => $salary->insurance,
                'Other Deductions' => $salary->other_deductions,
                'Gross Total' => $salary->gross_total,
                'Total Deductions' => $salary->total_deductions,
                'Net Salary' => $salary->net_salary,
                'Status' => $salary->is_posted ? 'Posted' : 'Draft',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Month',
            'Year',
            'Basic Salary',
            'Invigilation',
            'T Payment',
            'Eidi',
            'Increment',
            'Other Earnings',
            'Extra Leaves',
            'Income Tax',
            'Loan Deduction',
            'Insurance',
            'Other Deductions',
            'Gross Total',
            'Total Deductions',
            'Net Salary',
            'Status'
        ];
    }
}
