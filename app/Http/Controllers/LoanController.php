<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoansExport;
use App\Imports\LoansImport;
use App\Models\LoanLedger;


class LoanController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE SECTION
    |--------------------------------------------------------------------------
    */

    public function apply()
    {
        return view('loan.apply');
    }

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

    public function employeeLedger($id)
{
    $loan = Loan::with('ledgers')
        ->where('user_id', auth()->id())
        ->findOrFail($id);

    return view('loan.employee-ledger', compact('loan'));
}

    public function myLoan()
{
    $loan = Loan::where('user_id', auth()->id())
                ->latest()
                ->first();

    if (!$loan) {
        return view('loan.my', ['loan' => null]);
    }

    $allLoans = Loan::where('user_id', auth()->id())->get();
    $loanIds = $allLoans->pluck('id');

    // Consolidated attributes
    $loan->amount = $allLoans->sum('amount');
    $loan->opening_balance = $allLoans->sum('opening_balance');
    $loan->remaining_balance = $allLoans->sum('remaining_balance');

    $hasActive = $allLoans->contains(function($l){ return in_array($l->status, ['approved', 'pending']); });
    $loan->status = $hasActive ? 'approved' : 'closed';

    // Merge and sort all ledger history
    $loan->ledgers = LoanLedger::whereIn('loan_id', $loanIds)
        ->orderBy('created_at', 'asc')
        ->get();

    return view('loan.my', compact('loan'));
}




    /*
    |--------------------------------------------------------------------------
    | ADMIN SECTION
    |--------------------------------------------------------------------------
    */

    public function index()
{
    $loans = Loan::with('user')
        ->latest()
        ->get();

    return view('loan.admin-index', [
        'loans' => $loans
    ]);
}

    public function create(Request $request)
{
    $selectedUserId = $request->user_id;
    $employees = \App\Models\User::where('role','employee')->get();
    return view('loan.create',compact('employees', 'selectedUserId'));
}

    public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'amount' => 'required|numeric|min:0',
        'installments' => 'nullable|integer|min:1',
        'opening_balance' => 'nullable|numeric|min:0',
        'loan_date' => 'required|date',
    ]);

    // Opening balance (old loan from previous system)
    $opening = $request->opening_balance ?? 0;

    // New loan issued now
    $newLoan = $request->amount;

    // Total loan balance
    $totalLoan = $opening + $newLoan;

    // Monthly deduction based on total loan
    $monthly = 0;
    if ($request->installments && $request->installments > 0) {
        $monthly = $totalLoan / $request->installments;
    }

    // Create loan record
    $loan = Loan::create([
        'user_id' => $request->user_id,
        'amount' => $newLoan,
        'opening_balance' => $opening,
        'installments' => $request->installments,
        'monthly_deduction' => $monthly,
        'remaining_balance' => $totalLoan,
        'status' => 'approved',
        'loan_date' => $request->loan_date
    ]);

    /*
    |--------------------------------------------------------------------------
    | Ledger Entries
    |--------------------------------------------------------------------------
    */

    // Opening loan balance (previous system)
    if ($opening > 0) {
        $ledger = LoanLedger::create([
            'loan_id' => $loan->id,
            'amount' => $opening,
            'type' => 'opening',
            'remarks' => 'Opening balance from previous records',
        ]);
        $ledger->timestamps = false;
        $ledger->created_at = $request->loan_date . ' 00:00:00';
        $ledger->updated_at = $request->loan_date . ' 00:00:00';
        $ledger->save();
    }

    // New loan issued
    if ($newLoan > 0) {
        $ledger = LoanLedger::create([
            'loan_id' => $loan->id,
            'amount' => $newLoan,
            'type' => 'loan',
            'remarks' => 'New loan issued',
        ]);
        $ledger->timestamps = false;
        $ledger->created_at = $request->loan_date . ' 00:00:00';
        $ledger->updated_at = $request->loan_date . ' 00:00:00';
        $ledger->save();
    }

    return redirect()->route('admin.loan.index')
        ->with('success','Loan created successfully');
}
    public function ledger($id)
{
    $loan = Loan::with('user')->findOrFail($id);
    $user = $loan->user;

    // Fetch all loans of this user
    $allLoans = Loan::where('user_id', $user->id)->get();
    $loanIds = $allLoans->pluck('id');

    // Consolidated attributes
    $loan->amount = $allLoans->sum('amount');
    $loan->opening_balance = $allLoans->sum('opening_balance');
    $loan->remaining_balance = $allLoans->sum('remaining_balance');

    $hasActive = $allLoans->contains(function($l){ return in_array($l->status, ['approved', 'pending']); });
    $loan->status = $hasActive ? 'approved' : 'closed';

    // Merge and sort all ledger history
    $loan->ledgers = LoanLedger::whereIn('loan_id', $loanIds)
        ->orderBy('created_at', 'asc')
        ->get();

    return view('loan.ledger', compact('loan'));
}

    public function approve($id)
    {
        $loan = Loan::findOrFail($id);

        $monthly = 0;
        if ($loan->installments && $loan->installments > 0) {
            $monthly = $loan->amount / $loan->installments;
        }

        $loan->update([
            'status' => 'approved',
            'monthly_deduction' => $monthly,
            'remaining_balance' => $loan->amount,
            'loan_date' => $loan->loan_date ?? today()->format('Y-m-d')
        ]);

        return back()->with('success', 'Loan Approved');
    }

    public function reject($id)
    {
        Loan::findOrFail($id)
            ->update(['status' => 'rejected']);

        return back()->with('success', 'Loan Rejected');
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT / EXPORT
    |--------------------------------------------------------------------------
    */

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

        return back()->with('success', 'Loans Imported Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT / UPDATE / DELETE
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $loan = Loan::findOrFail($id);
        return view('loan.edit', compact('loan'));
    }

    public function update(Request $request, $id)
{
    $loan = Loan::findOrFail($id);

    $request->validate([
        'opening_balance' => 'nullable|numeric|min:0',
        'amount' => 'required|numeric|min:0',
        'installments' => 'nullable|integer|min:1',
        'loan_date' => 'required|date',
    ]);

    $opening = $request->opening_balance ?? 0;
    $amount = $request->amount;

    $totalLoan = $opening + $amount;

    $monthly = 0;
    if ($request->installments && $request->installments > 0) {
        $monthly = round($totalLoan / $request->installments, 2);
    }

    // Calculate deductions already paid to avoid overwriting them
    $paid = \App\Models\LoanLedger::where('loan_id', $loan->id)
        ->where('type', 'deduction')
        ->sum('amount');

    $remaining = max(0, $totalLoan - $paid);

    $status = $loan->status;
    if ($status === 'approved' && $remaining <= 0) {
        $status = 'closed';
    } elseif ($status === 'closed' && $remaining > 0) {
        $status = 'approved';
    }

    $loan->update([
        'opening_balance' => $opening,
        'amount' => $amount,
        'installments' => $request->installments,
        'monthly_deduction' => $monthly,
        'remaining_balance' => $remaining,
        'status' => $status,
        'loan_date' => $request->loan_date,
    ]);

    // Sync ledger entries
    if ($opening > 0) {
        $ledger = \App\Models\LoanLedger::updateOrCreate(
            ['loan_id' => $loan->id, 'type' => 'opening'],
            [
                'amount' => $opening,
                'remarks' => 'Opening balance from previous records',
            ]
        );
        $ledger->timestamps = false;
        $ledger->created_at = $request->loan_date . ' 00:00:00';
        $ledger->updated_at = $request->loan_date . ' 00:00:00';
        $ledger->save();
    } else {
        \App\Models\LoanLedger::where('loan_id', $loan->id)->where('type', 'opening')->delete();
    }

    if ($amount > 0) {
        $ledger = \App\Models\LoanLedger::updateOrCreate(
            ['loan_id' => $loan->id, 'type' => 'loan'],
            [
                'amount' => $amount,
                'remarks' => 'New loan issued',
            ]
        );
        $ledger->timestamps = false;
        $ledger->created_at = $request->loan_date . ' 00:00:00';
        $ledger->updated_at = $request->loan_date . ' 00:00:00';
        $ledger->save();
    } else {
        \App\Models\LoanLedger::where('loan_id', $loan->id)->where('type', 'loan')->delete();
    }

    return redirect()->route('admin.loan.index')
        ->with('success','Loan updated successfully');
}
    public function destroy($id)
    {
        Loan::findOrFail($id)->delete();

        return redirect()->route('admin.loan.index')
            ->with('success', 'Loan Deleted Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO DEDUCTION WHEN SALARY POSTED
    |--------------------------------------------------------------------------
    */

    public function deductLoan($loanId, $month, $year)
    {
        $loan = Loan::findOrFail($loanId);

        if ($loan->status !== 'approved' || $loan->remaining_balance <= 0) {
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
