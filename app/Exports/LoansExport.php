<?php

namespace App\Exports;

use App\Models\Loan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoansExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Loan::with('user')
            ->get()
            ->map(function ($loan) {
                return [
                    'Employee Name'     => $loan->user->name ?? '',
                    'Amount'            => $loan->amount,
                    'Installments'      => $loan->installments,
                    'Monthly Deduction' => $loan->monthly_deduction,
                    'Remaining Balance' => $loan->remaining_balance,
                    'Status'            => $loan->status,
                    'Created At'        => $loan->created_at->format('Y-m-d')
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Amount',
            'Installments',
            'Monthly Deduction',
            'Remaining Balance',
            'Status',
            'Created Date'
        ];
    }
}
