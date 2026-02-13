<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\SalaryPostedMail;

class SalaryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Salary List (With Filters + Summary)
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

        // Summary Cards
        $totalSalaries = $salaries->count();
        $totalNet = $salaries->sum('net_salary');
        $postedCount = $salaries->where('is_posted', true)->count();

        $employees = User::where('role', 'employee')->get();

        return view('salary.admin-index', compact(
            'salaries',
            'employees',
            'totalSalaries',
            'totalNet',
            'postedCount'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Create Salary (Admin)
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $users = User::where('role', 'employee')->get();
        return view('salary.create', compact('users'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store Salary (Admin)
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

        // Earnings
        $gross =
            ($request->basic_salary ?? 0)
            + ($request->invigilation ?? 0)
            + ($request->t_payment ?? 0)
            + ($request->eidi ?? 0)
            + ($request->increment ?? 0)
            + ($request->other_earnings ?? 0);

        // Deductions
        $deductions =
            ($request->extra_leaves ?? 0)
            + ($request->income_tax ?? 0)
            + ($request->loan_deduction ?? 0)
            + ($request->insurance ?? 0)
            + ($request->other_deductions ?? 0);

        $net = $gross - $deductions;

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
            'loan_deduction' => $request->loan_deduction ?? 0,
            'insurance' => $request->insurance ?? 0,
            'other_deductions' => $request->other_deductions ?? 0,

            'gross_total' => $gross,
            'total_deductions' => $deductions,
            'net_salary' => $net,
            'is_posted' => true
        ]);

        // Send Email Automatically
        Mail::to($salary->user->email)
            ->send(new SalaryPostedMail($salary));

        return redirect()->route('admin.salary.index')
            ->with('success', 'Salary Posted & Email Sent Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Show Salary (Admin View)
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

    /*
    |--------------------------------------------------------------------------
    | Download Payslip (PDF)
    |--------------------------------------------------------------------------
    */
    public function download($id)
    {
        $salary = Salary::with('user')->findOrFail($id);

        // Security Check
        if (auth()->user()->role !== 'admin' &&
            $salary->user_id !== auth()->id()) {
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

        $salary->update([
            'is_posted' => true
        ]);

        Mail::to($salary->user->email)
            ->send(new SalaryPostedMail($salary));

        return back()->with('success', 'Salary Posted & Email Sent');
    }
}
