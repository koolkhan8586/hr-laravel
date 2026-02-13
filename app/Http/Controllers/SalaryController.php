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
    public function index()
    {
        $salaries = Salary::with('user')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('salary.admin-index', compact('salaries'));
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
        $gross = $request->basic_salary
            + $request->invigilation
            + $request->t_payment
            + $request->increment;

        $deductions = $request->extra_leaves
            + $request->income_tax
            + $request->loan_deduction
            + $request->insurance
            + $request->others;

        $net = $gross - $deductions;

        Salary::create([
            'user_id' => $request->user_id,
            'month' => $request->month,
            'year' => $request->year,
            'basic_salary' => $request->basic_salary,
            'invigilation' => $request->invigilation,
            't_payment' => $request->t_payment,
            'others' => $request->others,
            'Eidi' => $request->eidi,          
            'increment' => $request->increment,
            'extra_leaves' => $request->extra_leaves,
            'income_tax' => $request->income_tax,
            'loan_deduction' => $request->loan_deduction,
            'insurance' => $request->insurance,
            'others' => $request->others,
            'gross_total' => $gross,
            'total_deductions' => $deductions,
            'net_salary' => $net,
            'is_posted' => true,
        ]);

        return redirect()->route('salary.admin')
            ->with('success', 'Salary Posted Successfully');
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
