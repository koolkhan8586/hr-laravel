<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\User;
use App\Models\Loan;
use App\Models\LoanLedger;
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
        ->where('is_posted', 1)
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
| Store Salary (Draft Only)
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

    $totalEarnings =
        ($request->basic_salary ?? 0)
        + ($request->invigilation ?? 0)
        + ($request->t_payment ?? 0)
        + ($request->eidi ?? 0)
        + ($request->increment ?? 0)
        + ($request->other_earnings ?? 0);

    $loanDeduction = 0;

    $loan = Loan::where('user_id',$request->user_id)
        ->where('remaining_balance','>',0)
        ->first();

    if($loan){
        $loanDeduction = $loan->monthly_deduction;
    }

    $totalDeductions =
        ($request->income_tax ?? 0)
        + ($request->insurance ?? 0)
        + ($request->extra_leaves ?? 0)
        + ($request->other_deductions ?? 0)
        + $loanDeduction;

    $netSalary = $totalEarnings - $totalDeductions;

    Salary::create([
        'user_id'=>$request->user_id,
        'month'=>$request->month,
        'year'=>$request->year,
        'basic_salary'=>$request->basic_salary,
        'invigilation'=>$request->invigilation ?? 0,
        't_payment'=>$request->t_payment ?? 0,
        'eidi'=>$request->eidi ?? 0,
        'increment'=>$request->increment ?? 0,
        'other_earnings'=>$request->other_earnings ?? 0,
        'income_tax'=>$request->income_tax ?? 0,
        'insurance'=>$request->insurance ?? 0,
        'extra_leaves'=>$request->extra_leaves ?? 0,
        'other_deductions'=>$request->other_deductions ?? 0,
        'loan_deduction'=>$loanDeduction,
        'net_salary'=>$netSalary,
        'status'=>'draft',
        'is_posted'=>0
    ]);

    return redirect()->route('admin.salary.index')
        ->with('success','Salary Created Successfully');
}

/*
|--------------------------------------------------------------------------
| Post Salary
|--------------------------------------------------------------------------
*/

public function post($id)
{
    $salary = Salary::findOrFail($id);

    if($salary->is_posted){
        return back()->with('error','Salary already posted');
    }

    $loan = Loan::where('user_id',$salary->user_id)
        ->where('remaining_balance','>',0)
        ->first();

    if($loan && $salary->loan_deduction>0){

        $ledgerExists = LoanLedger::where('salary_id',$salary->id)->exists();

        if(!$ledgerExists){

            $deduction = $salary->loan_deduction;

            if($loan->remaining_balance < $deduction){
                $deduction = $loan->remaining_balance;
            }

            $loan->remaining_balance -= $deduction;

            if($loan->remaining_balance <= 0){
                $loan->status='closed';
            }

            $loan->save();

            LoanLedger::create([
                'loan_id'=>$loan->id,
                'salary_id'=>$salary->id,
                'amount'=>$deduction,
                'type'=>'deduction',
                'remarks'=>'Salary deduction '.$salary->month.'/'.$salary->year
            ]);
        }
    }

    $salary->update([
        'is_posted'=>1,
        'status'=>'posted',
        'posted_at'=>now()
    ]);

    try{
        Mail::to($salary->user->email)
            ->send(new SalaryPostedMail($salary));
    }catch(\Exception $e){
        \Log::error($e->getMessage());
    }

    return back()->with('success','Salary posted successfully');
}

/*
|--------------------------------------------------------------------------
| Unpost Salary
|--------------------------------------------------------------------------
*/

public function unpost($id)
{
    $salary = Salary::findOrFail($id);

    if (!$salary->is_posted) {
        return back()->with('error','Salary already draft');
    }

    $ledger = LoanLedger::where('salary_id',$salary->id)->first();

    if($ledger){

        $loan = Loan::find($ledger->loan_id);

        if($loan){

            $loan->remaining_balance += $ledger->amount;

            if($loan->remaining_balance > 0){
                $loan->status = 'approved';
            }

            $loan->save();
        }

        $ledger->delete();
    }

    $salary->update([
        'is_posted'=>0,
        'status'=>'draft',
        'posted_at'=>null
    ]);

    return back()->with('success','Salary unposted successfully');
}

/*
|--------------------------------------------------------------------------
| Bulk Post
|--------------------------------------------------------------------------
*/

public function bulkPost(Request $request)
{
    $salaries = Salary::whereIn('id',$request->salary_ids)->get();

    foreach($salaries as $salary){
        $this->post($salary->id);
    }

    return back()->with('success','Selected salaries posted successfully');
}

/*
|--------------------------------------------------------------------------
| Bulk Unpost
|--------------------------------------------------------------------------
*/

public function bulkUnpost(Request $request)
{
    if (!$request->salary_ids) {
        return back()->with('error', 'No salaries selected');
    }

    $salaries = Salary::whereIn('id',$request->salary_ids)->get();

    foreach ($salaries as $salary) {

        if(!$salary->is_posted){
            continue;
        }

        $ledger = LoanLedger::where('salary_id',$salary->id)->first();

        if($ledger){

            $loan = Loan::find($ledger->loan_id);

            if($loan){

                $loan->remaining_balance += $ledger->amount;

                if($loan->remaining_balance > 0){
                    $loan->status = 'approved';
                }

                $loan->save();
            }

            $ledger->delete();
        }

        $salary->update([
            'is_posted'=>0,
            'status'=>'draft',
            'posted_at'=>null
        ]);
    }

    return back()->with('success','Selected salaries unposted successfully');
}

/*
|--------------------------------------------------------------------------
| Delete Salary
|--------------------------------------------------------------------------
*/

public function destroy($id)
{
    $salary = Salary::findOrFail($id);

    $ledger = LoanLedger::where('salary_id',$salary->id)->first();

    if($ledger){

        $loan = Loan::find($ledger->loan_id);

        if($loan){
            $loan->remaining_balance += $ledger->amount;
            $loan->save();
        }

        $ledger->delete();
    }

    $salary->delete();

    return redirect()->route('admin.salary.index')
        ->with('success','Salary deleted successfully');
}

/*
|--------------------------------------------------------------------------
| Bulk Delete
|--------------------------------------------------------------------------
*/

public function bulkDelete(Request $request)
{
    $salaries = Salary::whereIn('id',$request->salary_ids)->get();

    foreach($salaries as $salary){
        $this->destroy($salary->id);
    }

    return back()->with('success','Selected salaries deleted successfully');
}

    public function download($id)
{
    $salary = Salary::with('user')->findOrFail($id);

    // security check
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
