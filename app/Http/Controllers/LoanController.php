<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoansExport;

class LoanController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE SECTION
    |--------------------------------------------------------------------------
    */

    // Loan Application Form
    public function apply()
    {
        return view('loan.apply');
    }

    // Store Employee Loan Request
    public function storeRequest(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'installments' => 'required|integer|min:1'
        ]);

        $monthly = $request->amount / $request->installments;

        Loan::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'installments' => $request->installments,
            'monthly_deduction' => $monthly,
            'remaining_balance' => $request->amount,
            'status' => 'pending'
        ]);

        return redirect()->route('loan.my')
            ->with('success', 'Loan Request Submitted Successfully');
    }

    // Employee Loan Dashboard
    public function myLoan()
    {
        $loan = Loan::with('payments')
                    ->where('user_id', auth()->id())
                    ->whereIn('status', ['approved', 'completed'])
                    ->first();

        return view('loan.my', compact('loan'));
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN SECTION
    |--------------------------------------------------------------------------
    */

    // Loan List
    public function index()
    {
        $loans = Loan::with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('loan.admin-index', compact('loans'));
    }

    // Admin Create Form
    public function create()
    {
        $users = User::where('role', 'employee')->get();
        return view('loan.create', compact('users'));
    }

    // Store Loan (Admin Direct Approval)
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'amount' => 'required|numeric|min:1',
            'installments' => 'required|integer|min:1',
            'remaining_balance' => 'nullable|numeric|min:0'
        ]);

        $monthly = $request->amount / $request->installments;

        Loan::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'installments' => $request->installments,
            'monthly_deduction' => $monthly,
            'remaining_balance' => $request->remaining_balance ?? $request->amount,
            'status' => 'approved'
        ]);

        return redirect()->route('admin.loan.index')
            ->with('success', 'Loan Created Successfully');
    }

    // Approve Loan
    public function approve($id)
    {
        $loan = Loan::findOrFail($id);

        $loan->update([
            'status' => 'approved',
            'monthly_deduction' => $loan->amount / $loan->installments,
            'remaining_balance' => $loan->amount
        ]);

        return back()->with('success', 'Loan Approved');
    }

    public function export()
{
    return Excel::download(new LoansExport, 'loans.xlsx');
}
    public function importForm()
{
    return view('loan.import');
}

    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,csv'
    ]);

    Excel::import(new LoansImport, $request->file('file'));

    return back()->with('success','Loans Imported Successfully');
}

    // Reject Loan
    public function reject($id)
    {
        $loan = Loan::findOrFail($id);

        $loan->update([
            'status' => 'rejected'
        ]);

        return back()->with('success', 'Loan Rejected');
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT / UPDATE / DELETE
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $loan = Loan::findOrFail($id);
        $users = User::where('role', 'employee')->get();

        return view('loan.edit', compact('loan','users'));
    }

    public function update(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'installments' => 'required|integer|min:1',
            'remaining_balance' => 'required|numeric|min:0'
        ]);

        $monthly = $request->amount / $request->installments;

        $loan->update([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'installments' => $request->installments,
            'monthly_deduction' => $monthly,
            'remaining_balance' => $request->remaining_balance,
        ]);

        return redirect()->route('admin.loan.index')
            ->with('success', 'Loan Updated Successfully');
    }

    public function destroy($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();

        return redirect()->route('admin.loan.index')
            ->with('success', 'Loan Deleted Successfully');
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT PROCESSING (Auto Deduction)
    |--------------------------------------------------------------------------
    | Call this when salary is posted
    |--------------------------------------------------------------------------
    */

    public function deductLoan($loanId, $month, $year)
    {
        $loan = Loan::findOrFail($loanId);

        if ($loan->status !== 'approved') {
            return;
        }

        if ($loan->remaining_balance <= 0) {
            $loan->update(['status' => 'completed']);
            return;
        }

        $deduction = min(
            $loan->monthly_deduction,
            $loan->remaining_balance
        );

        $newBalance = $loan->remaining_balance - $deduction;

        LoanPayment::create([
            'loan_id' => $loan->id,
            'amount_paid' => $deduction,
            'remaining_balance' => $newBalance,
            'month' => $month,
            'year' => $year
        ]);

        $loan->update([
            'remaining_balance' => $newBalance,
            'status' => $newBalance <= 0 ? 'completed' : 'approved'
        ]);
    }
}
