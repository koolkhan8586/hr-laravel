<?php

namespace App\Imports;

use App\Models\Loan;
use App\Models\User;
use App\Models\LoanLedger;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LoansImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        /*
        Expected Excel Format (with headings):

        email | amount | installments | opening_balance
        */

        $user = User::where('email', $row['email'])->first();

        if (!$user) {
            return null; // Skip if user not found
        }

        $amount        = (float) $row['amount'];
        $installments  = (int) $row['installments'];
        $opening       = isset($row['opening_balance'])
                            ? (float) $row['opening_balance']
                            : 0;

        if ($installments <= 0 || $amount <= 0) {
            return null; // Skip invalid rows
        }

        $monthly = $amount / $installments;

        // Create Loan
        $loan = Loan::create([
            'user_id'          => $user->id,
            'amount'           => $amount,
            'opening_balance'  => $opening,
            'installments'     => $installments,
            'monthly_deduction'=> round($monthly, 2),
            'remaining_balance'=> $opening + $amount,
            'status'           => 'approved'
        ]);

        // Create Ledger Entry for Opening Balance
        if ($opening > 0) {
            LoanLedger::create([
                'loan_id' => $loan->id,
                'amount'  => $opening,
                'type'    => 'opening',
                'remarks' => 'Opening balance imported via Excel'
            ]);
        }

        return $loan;
    }
}
