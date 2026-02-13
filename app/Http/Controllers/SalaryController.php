<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\User;
use Illuminate\Http\Request;
use PDF;
use Mail;

class SalaryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin Salary List
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
    $totalPosted = $salaries->count();
    $totalNet = $salaries->sum('net_salary');
    $totalDeductions = $salaries->sum('total_deductions');
    $draftCount = $salaries->where('is_posted', false)->count();

    $employees = User::where('role', 'employee')->get();

    return view('salary.admin-index', compact(
        'salaries',
        'employees',
        'totalPosted',
        'totalNet',
        'totalDeductions',
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
    | Store Salary
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required',
        'month' => 'required',
        'year' => 'required',
        'basic_salary' => 'required|numeric'
    ]);

    // 🔴 Prevent duplicate salary
    $exists = Salary::where('user_id', $request->user_id)
        ->where('month', $request->month)
        ->where('year', $request->year)
        ->exists();

    if ($exists) {
        return back()->with('error', 'Salary already posted for this employee for selected month.');
    }

    // Earnings
    $gross =
        $request->basic_salary
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

    Salary::create([
        'user_id' => $request->user_id,
        'month' => $request->month,
        'year' => $request->year,
        'basic_salary' => $request->basic_salary,
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

    return redirect()->route('admin.salary.index')
        ->with('success', 'Salary Posted Successfully');
    
}

/*
|--------------------------------------------------------------------------
| View Salary Details (Admin)
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
    | Download Payslip
    |--------------------------------------------------------------------------
    */
    public function download($id)
    {
        $salary = Salary::with('user')->findOrFail($id);

        $pdf = PDF::loadView('salary.payslip', compact('salary'));

        return $pdf->download('Payslip_'.$salary->month.'_'.$salary->year.'.pdf');
    }
}
