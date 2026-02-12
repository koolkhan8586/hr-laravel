<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::with('user')->get();
        return view('loan.index', compact('loans'));
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
            'amount' => 'required|numeric',
            'installments' => 'required|numeric',
            'start_date' => 'required|date'
        ]);

        $monthly = $request->amount / $request->installments;

        Loan::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'installments' => $request->installments,
            'monthly_deduction' => $monthly,
            'remaining_amount' => $request->amount,
            'start_date' => $request->start_date,
        ]);

        return redirect()->route('loan.index')->with('success', 'Loan Assigned Successfully');
    }
    //
}
