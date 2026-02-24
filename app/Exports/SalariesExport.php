<?php

namespace App\Exports;

use App\Models\Salary;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalaryExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Salary::with('user')->get()->map(function ($salary) {
            return [
                'employee_code'   => $salary->user->employee_code ?? '',
                'employee_name'   => $salary->user->name ?? '',
                'month'           => $salary->month,
                'year'            => $salary->year,
                'basic_salary'    => $salary->basic_salary,
                'invigilation'    => $salary->invigilation,
                't_payment'       => $salary->t_payment,
                'eidi'            => $salary->eidi,
                'increment'       => $salary->increment,
                'other_earnings'  => $salary->other_earnings,
                'extra_leaves'    => $salary->extra_leaves,
                'income_tax'      => $salary->income_tax,
                'loan_deduction'  => $salary->loan_deduction,
                'insurance'       => $salary->insurance,
                'other_deductions'=> $salary->other_deductions,
                'net_salary'      => $salary->net_salary,
                'status'          => $salary->is_posted ? 'Posted' : 'Draft',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee Code',
            'Employee Name',
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
            'Net Salary',
            'Status',
        ];
    }
}
