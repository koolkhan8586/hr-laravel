<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Loan List
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $loans = Loan::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('loan.index', compact('loans'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create Loan Form
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $employees = User::where('role', 'employee')->get();
        return view('loan.create', compact('employees'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store Loan
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
            'remaining_amount' => $request->amount,
            'installments' => $request->installments,
            'status' => 'active'
        ]);

        return redirect()->route('loan.index')
            ->with('success', 'Loan Assigned Successfully');
    }
}
