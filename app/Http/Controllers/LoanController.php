<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;

class LoanController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Employee Loan Application Form
    |--------------------------------------------------------------------------
    */
    public function apply()
    {
        return view('loan.apply');
    }


    /*
    |--------------------------------------------------------------------------
    | Store Employee Loan Request
    |--------------------------------------------------------------------------
    */
    public function storeRequest(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'installments' => 'required|integer|min:1'
        ]);

        Loan::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'installments' => $request->installments,
            'monthly_deduction' => $request->amount / $request->installments,
            'remaining_balance' => $request->amount,
            'status' => 'pending'
        ]);

        return redirect()->route('loan.my')
            ->with('success', 'Loan Request Submitted Successfully');
    }


    /*
    |--------------------------------------------------------------------------
    | Employee Loan Status Page
    |--------------------------------------------------------------------------
    */
    public function myLoan()
    {
        $loan = Loan::where('user_id', auth()->id())
                    ->where('status', 'approved')
                    ->first();

        return view('loan.my', compact('loan'));
    }


    /*
    |--------------------------------------------------------------------------
    | Admin Loan List
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $loans = Loan::with('user')
                    ->orderByDesc('created_at')
                    ->get();

        return view('loan.admin-index', compact('loans'));
    }


    /*
    |--------------------------------------------------------------------------
    | Admin Create Loan (Optional Direct Approval)
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $users = User::where('role', 'employee')->get();
        return view('loan.create', compact('users'));
    }


    /*
    |--------------------------------------------------------------------------
    | Admin Store Loan
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'amount' => 'required|numeric',
            'installments' => 'required|integer'
        ]);

        Loan::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'installments' => $request->installments,
            'monthly_deduction' => $request->amount / $request->installments,
            'remaining_balance' => $request->amount,
            'status' => 'approved'
        ]);

        return redirect()->route('admin.loan.index')
            ->with('success', 'Loan Created Successfully');
    }


    /*
    |--------------------------------------------------------------------------
    | Approve Loan
    |--------------------------------------------------------------------------
    */
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


    /*
    |--------------------------------------------------------------------------
    | Reject Loan
    |--------------------------------------------------------------------------
    */
    public function reject($id)
    {
        $loan = Loan::findOrFail($id);

        $loan->update([
            'status' => 'rejected'
        ]);

        return back()->with('success', 'Loan Rejected');
    }
}
