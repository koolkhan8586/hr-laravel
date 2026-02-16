    <?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoansExport;
use App\Imports\LoansImport;

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

    public function index()
    {
        $loans = Loan::with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('loan.admin-index', compact('loans'));
    }

    public function create()
    {
        $employees = User::where('role', 'employee')->get();
return view('loan.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'amount' => 'required|numeric|min:1',
            'opening_balance' => 'nullable|numeric|min:0'
            'installments' => 'required|integer|min:1',
        ]);

        $monthly = $request->amount / $request->installments;

        Loan::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'opening_balance' => $opening,
            'installments' => $request->installments,
            'monthly_deduction' => $monthly,
            'remaining_balance' => $request->amount,
            'status' => 'approved'
        ]);

        return redirect()->route('admin.loan.index')
            ->with('success', 'Loan Created Successfully');
    }

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
            'amount' => 'required|numeric|min:1',
            'opening_balance' => 'nullable|numeric|min:0'
            'installments' => 'required|integer|min:1',
        ]);

          $openingBalance = $request->opening_balance ?? 0;

        $monthly = $request->amount / $request->installments;
        

        // If no payments yet → reset remaining balance
        if ($loan->payments()->count() == 0) {
            $remaining = $request->amount;
        } else {
            // Keep remaining balance unchanged
            $remaining = $loan->remaining_balance;
        }

        $loan->update([
            'amount' => $request->amount,
            'opening_balance' => 'nullable|numeric|min:0',
            'installments' => $request->installments,
            'monthly_deduction' => $monthly,
            'remaining_balance' => $remaining,
        ]);

        return redirect()->route('admin.loan.index')
            ->with('success', 'Loan Updated Successfully');
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
