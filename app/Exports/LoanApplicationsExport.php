<?php

namespace App\Exports;

use App\Models\Loan;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * The loan applications employees submitted, for the Export button on the
 * applications screen. Optionally narrowed to one status.
 */
class LoanApplicationsExport implements FromCollection, WithHeadings
{
    public function __construct(protected ?string $status = null)
    {
    }

    public function collection()
    {
        return Loan::with('user')
            ->applications()
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('requested_at')
            ->get()
            ->map(fn (Loan $loan) => [
                'Reference'         => $loan->reference(),
                'Employee Name'     => $loan->user->name ?? '',
                'Employee Code'     => $loan->user->employee_code ?? '',
                'Applied On'        => $loan->requested_at?->format('Y-m-d H:i'),
                'Amount'            => $loan->amount,
                'Installments'      => $loan->installments,
                'Monthly Deduction' => $loan->monthly_deduction,
                'Deductions From'   => $loan->deduction_start_month
                    ? Carbon::parse($loan->deduction_start_month)->format('M Y')
                    : '',
                'Purpose'           => $loan->purpose,
                'Status'            => ucfirst($loan->status),
                'Decided By'        => $loan->decided_by,
                'Decided Via'       => $loan->decided_via,
                'Decided At'        => $loan->decided_at?->format('Y-m-d H:i'),
                'Decision Remarks'  => $loan->decision_remarks,
            ]);
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Employee Name',
            'Employee Code',
            'Applied On',
            'Amount',
            'Installments',
            'Monthly Deduction',
            'Deductions From',
            'Purpose',
            'Status',
            'Decided By',
            'Decided Via',
            'Decided At',
            'Decision Remarks',
        ];
    }
}
