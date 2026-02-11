<?php

namespace App\Exports;

use App\Models\LeaveTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveTransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return LeaveTransaction::with(['user', 'leave'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Leave Type',
            'Days Deducted',
            'Balance Before',
            'Balance After',
            'Action',
            'Processed By',
            'Date'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->user->name,
            $transaction->leave->type,
            $transaction->days,
            $transaction->balance_before,
            $transaction->balance_after,
            ucfirst($transaction->action),
            $transaction->processed_by,
            $transaction->created_at->format('Y-m-d H:i'),
        ];
    }
}

