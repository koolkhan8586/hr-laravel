<?php

namespace App\Exports;

use App\Models\Salary;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalariesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    /**
     * Fetch Data
     */
    public function collection()
    {
        return Salary::with('user')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
    }

    /**
     * Excel Headings
     */
    public function headings(): array
    {
        return [
            'Employee Name',
            'Month',
            'Year',

            // Earnings
            'Basic Salary',
            'Invigilation',
            'T Payment',
            'Eidi',
            'Increment',
            'Other Earnings',

            // Deductions
            'Extra Leaves',
            'Income Tax',
            'Loan Deduction',
            'Insurance',
            'Other Deductions',

            // Totals
            'Gross Total',
            'Total Deductions',
            'Net Salary',

            'Status',
            'Created At'
        ];
    }

    /**
     * Map Columns
     */
    public function map($salary): array
    {
        return [
            $salary->user->name ?? '-',
            $salary->month,
            $salary->year,

            $salary->basic_salary,
            $salary->invigilation,
            $salary->t_payment,
            $salary->eidi,
            $salary->increment,
            $salary->other_earnings,

            $salary->extra_leaves,
            $salary->income_tax,
            $salary->loan_deduction,
            $salary->insurance,
            $salary->other_deductions,

            $salary->gross_total,
            $salary->total_deductions,
            $salary->net_salary,

            $salary->is_posted ? 'Posted' : 'Draft',
            $salary->created_at->format('Y-m-d')
        ];
    }
}
