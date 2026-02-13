<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\User;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\SalaryPostedMail;

class SalaryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Salary List (Filters + Summary Cards)
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Salary::with('user');

        // Filters
        if ($request->month) {
            $query->where('month', $request->month);
        }

        if ($request->year) {
            $query->where('year', $request->year);
        }

        if ($request->employee) {
            $query->where('user_id', $request->employee);
        }

        $salaries = $query->orderByDesc('year')
                          ->orderByDesc('month')
                          ->get();

        $employees = User::where('role', 'employee')->get();

        // Summary Cards
        $totalSalaries   = $salaries->count();
        $totalNet        = $salaries->sum('net_salary');
        $totalDeductions = $salaries->sum('total_deductions');
        $totalPosted     = $salaries->where('is_posted', true)->count();
        $draftCount      = $salaries->where('is_posted', false)->count();

        return view('salary.admin-index', compact(
            'salaries',
            'employees',
            'totalSalaries',
            'totalNet',
            'totalDeductions',
            'totalPosted',
            'draftCount'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Create Salary
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $users = User::where('role', 'employee')->get();
        return view('salary.create', compact('users'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store Salary (With Auto Loan Deduction)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'basic_salary' => 'required|numeric'
        ]);

        // Prevent Duplicate Salary
        $exists = Salary::where('user_id', $request->user_id)
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Salary already exists for this month.');
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Earnings
        |--------------------------------------------------------------------------
        */
        $gross =
            ($request->basic_salary ?? 0)
            + ($request->invigilation ?? 0)
            + ($request->t_payment ?? 0)
            + ($request->eidi ?? 0)
            + ($request->increment ?? 0)
            + ($request->other_earnings ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Auto Loan Deduction
        |--------------------------------------------------------------------------
        */
        $loan = Loan::where('user_id', $request->user_id)
            ->where('status', 'approved')
            ->where('remaining_balance', '>', 0)
            ->first();

        $loanDeduction = 0;

        if ($loan) {

            $loanDeduction = min(
                $loan->monthly_deduction,
                $loan->remaining_balance
            );

            $loan->remaining_balance -= $loanDeduction;

            if ($loan->remaining_balance <= 0) {
                $loan->remaining_balance = 0;
            }

            $loan->save();
        }

        /*
        |--------------------------------------------------------------------------
        | Deductions
        |--------------------------------------------------------------------------
        */
        $deductions =
            ($request->extra_leaves ?? 0)
            + ($request->income_tax ?? 0)
            + ($request->insurance ?? 0)
            + ($request->other_deductions ?? 0)
            + $loanDeduction;

        $net = $gross - $deductions;

        /*
        |--------------------------------------------------------------------------
        | Create Salary Record
        |--------------------------------------------------------------------------
        */
        $salary = Salary::create([
            'user_id' => $request->user_id,
            'month' => $request->month,
            'year' => $request->year,

            'basic_salary' => $request->basic_salary ?? 0,
            'invigilation' => $request->invigilation ?? 0,
            't_payment' => $request->t_payment ?? 0,
            'eidi' => $request->eidi ?? 0,
            'increment' => $request->increment ?? 0,
            'other_earnings' => $request->other_earnings ?? 0,

            'extra_leaves' => $request->extra_leaves ?? 0,
            'income_tax' => $request->income_tax ?? 0,
            'loan_deduction' => $loanDeduction,
            'insurance' => $request->insurance ?? 0,
            'other_deductions' => $request->other_deductions ?? 0,

            'gross_total' => $gross,
            'total_deductions' => $deductions,
            'net_salary' => $net,
            'is_posted' => true
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save Loan Payment History
        |--------------------------------------------------------------------------
        */
        if ($loan && $loanDeduction > 0) {
            LoanPayment::create([
                'loan_id' => $loan->id,
                'salary_id' => $salary->id,
                'amount_paid' => $loanDeduction,
                'remaining_balance' => $loan->remaining_balance
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Send Email
        |--------------------------------------------------------------------------
        */
        Mail::to($salary->user->email)
            ->send(new SalaryPostedMail($salary));

        return redirect()->route('admin.salary.index')
            ->with('success', 'Salary Posted & Email Sent Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Show Salary (Admin)
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $salary = Salary::with('user')->findOrFail($id);
        return view('salary.show', compact('salary'));
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Salary List
    |--------------------------------------------------------------------------
    */
    public function employeeIndex()
    {
        $salaries = auth()->user()
            ->salaries()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('salary.employee-index', compact('salaries'));
    }

    public function edit($id)
{
    $salary = Salary::findOrFail($id);
    $users = User::where('role','employee')->get();

    return view('salary.edit', compact('salary','users'));
}

    /*
    |--------------------------------------------------------------------------
    | Download Payslip
    |--------------------------------------------------------------------------
    */
    public function download($id)
    {
        $salary = Salary::with('user')->findOrFail($id);

        if (auth()->user()->role !== 'admin'
            && $salary->user_id !== auth()->id()) {
            abort(403);
        }

        $pdf = Pdf::loadView('salary.payslip-pdf', compact('salary'));

        return $pdf->download(
            'Salary_Slip_'.$salary->month.'_'.$salary->year.'.pdf'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Post Salary (Optional Separate Action)
    |--------------------------------------------------------------------------
    */
    public function post($id)
    {
        $salary = Salary::findOrFail($id);

        $salary->update(['is_posted' => true]);

        Mail::to($salary->user->email)
            ->send(new SalaryPostedMail($salary));

        return back()->with('success', 'Salary Posted & Email Sent');
    }
}
