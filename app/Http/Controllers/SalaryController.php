<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\User;
use App\Models\Loan;
use App\Models\LoanLedger;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\SalaryPostedMail;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SalaryImport;
use App\Exports\SalariesExport;
use App\Exports\SalarySampleExport;

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

        $totalSalaries   = $salaries->count();
        $totalNet        = $salaries->where('is_posted', true)->sum('net_salary');
        $totalDeductions = $salaries->sum('total_deductions');
        $draftCount      = $salaries->where('is_posted', false)->count();
        $totalPosted     = $salaries->where('is_posted', true)->count();

        return view('salary.admin-index', compact(
            'salaries',
            'employees',
            'totalSalaries',
            'totalNet',
            'totalDeductions',
            'draftCount',
            'totalPosted'
        ));
    }

    /*
|--------------------------------------------------------------------------
| Employee Salary List
|--------------------------------------------------------------------------
*/
public function employeeIndex()
{
    $salaries = Salary::where('user_id', auth()->id())
        ->where('is_posted', 1)   // only posted salaries
        ->orderByDesc('year')
        ->orderByDesc('month')
        ->get();

    return view('salary.employee-index', compact('salaries'));
}
    public function downloadSample()
{
    return Excel::download(new SalarySampleExport, 'salary_sample.xlsx');
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
    | Store Salary (Draft First)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'month'   => 'required',
        'year'    => 'required',
        'basic_salary' => 'required|numeric'
    ]);

    // ==========================
    // CALCULATE EARNINGS
    // ==========================
    $totalEarnings =
        ($request->basic_salary ?? 0)
        + ($request->invigilation ?? 0)
        + ($request->t_payment ?? 0)
        + ($request->eidi ?? 0)
        + ($request->increment ?? 0)
        + ($request->other_earnings ?? 0);

    // ==========================
    // LOAN DEDUCTION
    // ==========================
    $loanDeduction = 0;
    $loan = \App\Models\Loan::where('user_id', $request->user_id)
                ->where('status','approved')
                ->where('remaining_balance','>',0)
                ->first();

    if($loan){
        $loanDeduction = $loan->monthly_installment;
    }

    // ==========================
    // TOTAL DEDUCTIONS
    // ==========================
    $totalDeductions =
        ($request->income_tax ?? 0)
        + ($request->insurance ?? 0)
        + ($request->extra_leaves ?? 0)
        + ($request->other_deductions ?? 0)
        + $loanDeduction;

    $netSalary = $totalEarnings - $totalDeductions;

    // ==========================
    // CREATE SALARY
    // ==========================
    $salary = \App\Models\Salary::create([
        'user_id' => $request->user_id,
        'month'   => $request->month,
        'year'    => $request->year,

        'basic_salary'   => $request->basic_salary,
        'invigilation'   => $request->invigilation ?? 0,
        't_payment'      => $request->t_payment ?? 0,
        'eidi'           => $request->eidi ?? 0,
        'increment'      => $request->increment ?? 0,
        'other_earnings' => $request->other_earnings ?? 0,

        'income_tax'       => $request->income_tax ?? 0,
        'insurance'        => $request->insurance ?? 0,
        'extra_leaves'     => $request->extra_leaves ?? 0,
        'other_deductions' => $request->other_deductions ?? 0,
        'loan_deduction'   => $loanDeduction,

        'net_salary' => $netSalary,
        'status'     => 'draft'
    ]);

    // ==========================
    // UPDATE LOAN BALANCE
    // ==========================
    if($loan && $loanDeduction > 0){

        $loan->remaining_balance -= $loanDeduction;
        $loan->save();

        \App\Models\LoanLedger::create([
            'loan_id' => $loan->id,
            'user_id' => $request->user_id,
            'salary_id' => $salary->id,
            'amount' => $loanDeduction,
            'type'   => 'deduction'
        ]);
    }

    return redirect()->route('admin.salary.index')
        ->with('success','Salary Created Successfully');
}


    /*
    |--------------------------------------------------------------------------
    | Single Post
    |--------------------------------------------------------------------------
    */
    public function post($id)
{
    $salary = Salary::findOrFail($id);

    if ($salary->is_posted) {
        return back()->with('error', 'Salary already posted');
    }

    $salary->is_posted = 1;
    $salary->posted_at = now();
    $salary->save();

    try {
        \Mail::raw(
            "Your salary for {$salary->month}/{$salary->year} has been posted.\n\nNet Salary: Rs {$salary->net_salary}",
            function ($message) use ($salary) {
                $message->to($salary->user->email)
                        ->subject('Salary Posted');
            }
        );
    } catch (\Exception $e) {
        \Log::error($e->getMessage());
    }

    return back()->with('success', 'Salary posted successfully');
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
    | Single Unpost
    |--------------------------------------------------------------------------
    */
    public function unpost($id)
{
    $salary = Salary::findOrFail($id);

    if (!$salary->is_posted) {
        return back()->with('error', 'Salary already in draft');
    }

    $salary->is_posted = 0;
    $salary->posted_at = null;
    $salary->save();

    return back()->with('success', 'Salary moved to draft');
}


    /*
    |--------------------------------------------------------------------------
    | Bulk Post
    |--------------------------------------------------------------------------
    */
    public function bulkPost(Request $request)
{
    if (!$request->salary_ids) {
        return back()->with('error','No salary selected.');
    }

    Salary::whereIn('id', $request->salary_ids)
        ->update(['is_posted' => 1]);

    return back()->with('success','Selected salaries posted successfully.');
}


    /*
    |--------------------------------------------------------------------------
    | Bulk Unpost
    |--------------------------------------------------------------------------
    */
    public function bulkUnpost(Request $request)
{
    if (!$request->salary_ids) {
        return back()->with('error','No salary selected.');
    }

    Salary::whereIn('id', $request->salary_ids)
        ->update(['is_posted' => 0]);

    return back()->with('success','Selected salaries unposted.');
}


    /*
    |--------------------------------------------------------------------------
    | Bulk Delete
    |--------------------------------------------------------------------------
    */
    public function bulkDelete(Request $request)
    {
        if (!$request->salary_ids) {
            return back()->with('error','No salary selected.');
        }

        Salary::whereIn('id', $request->salary_ids)->delete();

        return back()->with('success','Selected salaries deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | Post All Drafts
    |--------------------------------------------------------------------------
    */
    public function postAllDrafts()
    {
        $drafts = Salary::where('is_posted', false)->get();

        foreach ($drafts as $salary) {
            $salary->update(['is_posted' => true]);

            Mail::to($salary->user->email)
                ->queue(new SalaryPostedMail($salary));
        }

        return back()->with('success','All draft salaries posted.');
    }

    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,csv'
    ]);

    $import = new \App\Imports\SalaryImport;

    \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

    if (!empty($import->errors)) {
        return back()->with('error', implode(' | ', $import->errors));
    }

    foreach ($import->rows as $row) {
        \App\Models\Salary::create($row);
    }

    return back()->with('success', 'Salary Imported Successfully as Draft');
}

    /*
|--------------------------------------------------------------------------
| Delete Salary
|--------------------------------------------------------------------------
*/

public function destroy($id)
{
    $salary = \App\Models\Salary::find($id);

    if (!$salary) {
        return redirect()->route('admin.salary.index')
            ->with('error', 'Salary not found');
    }

    $salary->delete();

    return redirect()->route('admin.salary.index')
        ->with('success', 'Salary deleted successfully');
}
    public function edit($id)
{
    $salary = Salary::findOrFail($id);
    $users = User::where('role','employee')->get();

    return view('salary.edit', compact('salary','users'));
}
    public function update(Request $request, $id)
{
    $salary = Salary::findOrFail($id);

    $request->validate([
        'user_id' => 'required|exists:users,id',
        'month' => 'required|integer|min:1|max:12',
        'year' => 'required|integer',
    ]);

    // Earnings
    $basic = $request->basic_salary ?? 0;
    $invigilation = $request->invigilation ?? 0;
    $t_payment = $request->t_payment ?? 0;
    $eidi = $request->eidi ?? 0;
    $increment = $request->increment ?? 0;
    $other_earnings = $request->other_earnings ?? 0;

    // Deductions
    $extra_leaves = $request->extra_leaves ?? 0;
    $income_tax = $request->income_tax ?? 0;
    $loan_deduction = $request->loan_deduction ?? 0;
    $insurance = $request->insurance ?? 0;
    $other_deductions = $request->other_deductions ?? 0;

    // Calculations
    $gross_total =
        $basic +
        $invigilation +
        $t_payment +
        $eidi +
        $increment +
        $other_earnings;

    $total_deductions =
        $extra_leaves +
        $income_tax +
        $loan_deduction +
        $insurance +
        $other_deductions;

    $net_salary = $gross_total - $total_deductions;

    $salary->update([
        'user_id' => $request->user_id,
        'month' => $request->month,
        'year' => $request->year,

        'basic_salary' => $basic,
        'invigilation' => $invigilation,
        't_payment' => $t_payment,
        'eidi' => $eidi,
        'increment' => $increment,
        'other_earnings' => $other_earnings,

        'extra_leaves' => $extra_leaves,
        'income_tax' => $income_tax,
        'loan_deduction' => $loan_deduction,
        'insurance' => $insurance,
        'other_deductions' => $other_deductions,

        'gross_total' => $gross_total,
        'total_deductions' => $total_deductions,
        'net_salary' => $net_salary,
    ]);

    return redirect()->route('admin.salary.index')
        ->with('success', 'Salary updated successfully');
}

    /*
    |--------------------------------------------------------------------------
    | Export Excel
    |--------------------------------------------------------------------------
    */
    public function export()
    {
        return Excel::download(new SalariesExport, 'salaries.xlsx');
    }

    /*
    |--------------------------------------------------------------------------
    | Download PDF Payslip
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
}
