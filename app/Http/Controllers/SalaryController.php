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
    // FIND ACTIVE LOAN
    // ==========================
    $loanDeduction = 0;

    $loan = Loan::where('user_id', $request->user_id)
        ->where('status', 'approved')
        ->where('remaining_balance', '>', 0)
        ->first();

    if ($loan) {
        $loanDeduction = $loan->monthly_deduction;

        // prevent over deduction
        if ($loan->remaining_balance < $loanDeduction) {
            $loanDeduction = $loan->remaining_balance;
        }
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
    $salary = Salary::create([
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



    return redirect()->route('admin.salary.index')
        ->with('success','Salary Created Successfully');
}

    /*
    |--------------------------------------------------------------------------
    | SALARY SHEET (grid entry for a whole month, like the Excel sheet)
    |--------------------------------------------------------------------------
    */
    public function sheet(Request $request)
    {
        $month    = (int) ($request->month ?? now()->month);
        $year     = (int) ($request->year ?? now()->year);
        $category = $request->category === 'teacher' ? 'teacher' : 'staff';

        $users = User::with('staff')
            ->where('role', 'employee')
            ->where('salary_category', $category)
            ->orderBy('name')
            ->get();

        // Existing rows for this period, keyed by user so the sheet reopens
        // with whatever was last saved.
        $existing = Salary::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('user_id');

        $columns   = \App\Models\SalaryColumn::forCategory($category);
        $taxSlabs  = \App\Models\TaxSlab::activeSlabs();
        $taxBasis  = \App\Models\TaxSlab::basis();

        return view('salary.sheet', compact(
            'users',
            'existing',
            'month',
            'year',
            'category',
            'columns',
            'taxSlabs',
            'taxBasis'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Copy the previous month's sheet into this one
    |--------------------------------------------------------------------------
    */
    public function sheetCopyPrevious(Request $request)
    {
        $request->validate([
            'month'    => 'required|integer|min:1|max:12',
            'year'     => 'required|integer|min:2000|max:2100',
            'category' => 'required|in:teacher,staff',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;

        $from = \Carbon\Carbon::create($year, $month, 1)->subMonth();

        $userIds = User::where('role', 'employee')
            ->where('salary_category', $request->category)
            ->pluck('id');

        $previous = Salary::where('month', $from->month)
            ->where('year', $from->year)
            ->whereIn('user_id', $userIds)
            ->get();

        if ($previous->isEmpty()) {
            return back()->with('error',
                'Nothing to copy - there is no '.$from->format('F Y').' sheet for this category.');
        }

        $copied  = 0;
        $skipped = 0;

        foreach ($previous as $row) {

            $target = Salary::where('user_id', $row->user_id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            // Never touch a month that is already finalised.
            if ($target && $target->isPosted()) {
                $skipped++;
                continue;
            }

            $values = [
                'basic_salary'     => $row->basic_salary,
                'extra_load'       => $row->extra_load,
                'invigilation'     => $row->invigilation,
                't_payment'        => $row->t_payment,
                'eidi'             => $row->eidi,
                'increment'        => $row->increment,
                'other_earnings'   => $row->other_earnings,

                'extra_leaves'     => $row->extra_leaves,
                'income_tax'       => $row->income_tax,
                'loan_deduction'   => $row->loan_deduction,
                'insurance'        => $row->insurance,
                'other_deductions' => $row->other_deductions,

                'cheque_amount'    => $row->cheque_amount,
                'custom_values'    => $row->custom_values,
            ];

            if ($target) {
                $target->update($values);
            } else {
                Salary::create($values + [
                    'user_id' => $row->user_id,
                    'month'   => $month,
                    'year'    => $year,
                    'status'  => 'draft',
                ]);
            }

            $copied++;
        }

        $message = "Copied {$copied} row(s) from ".$from->format('F Y').'.';

        if ($skipped) {
            $message .= " {$skipped} already-posted row(s) were left unchanged.";
        }

        return redirect()->route('admin.salary.sheet', [
            'month'    => $month,
            'year'     => $year,
            'category' => $request->category,
        ])->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Post the whole sheet (loan deduction + employee email)
    |--------------------------------------------------------------------------
    */
    public function sheetPost(Request $request)
    {
        $request->validate([
            'month'    => 'required|integer|min:1|max:12',
            'year'     => 'required|integer|min:2000|max:2100',
            'category' => 'required|in:teacher,staff',
        ]);

        $userIds = User::where('role', 'employee')
            ->where('salary_category', $request->category)
            ->pluck('id');

        $drafts = Salary::with('user')
            ->where('month', $request->month)
            ->where('year', $request->year)
            ->whereIn('user_id', $userIds)
            ->where('status', 'draft')
            ->get();

        if ($drafts->isEmpty()) {
            return back()->with('error', 'There are no draft salaries to post for this sheet.');
        }

        $posted = 0;
        $failedMail = 0;

        foreach ($drafts as $salary) {

            // Handles the loan ledger entry and flips the row to posted.
            $this->processLoanDeductionAndPost($salary);

            try {
                \Mail::to($salary->user->email)
                    ->queue(new \App\Mail\SalaryPostedMail($salary));
            } catch (\Exception $e) {
                $failedMail++;
                \Log::error('Salary post mail failed: '.$e->getMessage());
            }

            $posted++;
        }

        $message = "Posted {$posted} salaries. Employees can now see them in their portal.";

        if ($failedMail) {
            $message .= " {$failedMail} notification email(s) could not be sent - check the mail log.";
        }

        return redirect()->route('admin.salary.sheet', [
            'month'    => $request->month,
            'year'     => $request->year,
            'category' => $request->category,
        ])->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | Save Salary Sheet (bulk create / update)
    |--------------------------------------------------------------------------
    */
    public function sheetStore(Request $request)
    {
        $request->validate([
            'month'          => 'required|integer|min:1|max:12',
            'year'           => 'required|integer|min:2000|max:2100',
            'category'       => 'required|in:teacher,staff',
            'rows'           => 'required|array',
            'rows.*.user_id' => 'required|exists:users,id',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;

        $columnIds = \App\Models\SalaryColumn::forCategory($request->category)
            ->pluck('id')
            ->all();

        $saved   = 0;
        $skipped = 0;

        foreach ($request->rows as $row) {

            $amount = fn ($key) => round((float) str_replace(',', '', $row[$key] ?? 0), 2);

            $existing = Salary::where('user_id', $row['user_id'])
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            // Never silently overwrite a salary that has already been posted.
            if ($existing && $existing->isPosted()) {
                $skipped++;
                continue;
            }

            $values = [
                'basic_salary'     => $amount('basic_salary'),
                'extra_load'       => $amount('extra_load'),
                'invigilation'     => $amount('invigilation'),
                't_payment'        => $amount('t_payment'),
                'eidi'             => $amount('eidi'),
                'increment'        => $amount('increment'),
                'other_earnings'   => $amount('other_earnings'),

                'extra_leaves'     => $amount('extra_leaves'),
                'income_tax'       => $amount('income_tax'),
                'loan_deduction'   => $amount('loan_deduction'),
                'insurance'        => $amount('insurance'),
                'other_deductions' => $amount('other_deductions'),

                'cheque_amount'    => $amount('cheque_amount'),
            ];

            // Admin-defined columns arrive as rows[i][custom][columnId].
            $custom = [];

            foreach ($columnIds as $columnId) {
                $raw = $row['custom'][$columnId] ?? null;
                $val = round((float) str_replace(',', '', $raw ?? 0), 2);

                if ($val != 0) {
                    $custom[$columnId] = $val;
                }
            }

            // Skip untouched rows so blank employees don't create empty records.
            if (!$existing && empty($custom) && collect($values)->every(fn ($v) => $v == 0)) {
                continue;
            }

            $values['custom_values'] = $custom ?: null;

            if ($existing) {
                $existing->update($values);
            } else {
                Salary::create($values + [
                    'user_id' => $row['user_id'],
                    'month'   => $month,
                    'year'    => $year,
                    'status'  => 'draft',
                ]);
            }

            $saved++;
        }

        $message = "Salary sheet saved ({$saved} employees).";

        if ($skipped) {
            $message .= " {$skipped} already-posted row(s) were left unchanged.";
        }

        return redirect()->route('admin.salary.sheet', [
            'month'    => $month,
            'year'     => $year,
            'category' => $request->category,
        ])->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | BANK SHEET (ANNEXURE-A)
    |--------------------------------------------------------------------------
    */
    public function bankSheet(Request $request)
    {
        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year ?? now()->year);

        $salaries = Salary::with('user')
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            // Only employees actually receiving money through the bank.
            ->filter(fn ($s) => $s->user && $s->bank_amount > 0)
            ->sortBy(fn ($s) => $s->user->name)
            ->values();

        $grandTotal = $salaries->sum(fn ($s) => $s->bank_amount);

        // Reconciliation figures matching the footer of the Excel sheets.
        $allForPeriod = Salary::with('user')
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $summary = [
            'teacher_net' => $allForPeriod
                ->filter(fn ($s) => $s->user && $s->user->salary_category === 'teacher')
                ->sum('net_salary'),
            'staff_net' => $allForPeriod
                ->filter(fn ($s) => $s->user && $s->user->salary_category === 'staff')
                ->sum('net_salary'),
            'cheque_total' => $allForPeriod->sum('cheque_amount'),
        ];

        return view('salary.bank-sheet', compact(
            'salaries',
            'grandTotal',
            'summary',
            'month',
            'year'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Single Post
    |--------------------------------------------------------------------------
    */
    public function post($id)
{
    $salary = Salary::findOrFail($id);

    // prevent double posting
    if ($salary->is_posted) {
        return back()->with('error','Salary already posted');
    }

    $this->processLoanDeductionAndPost($salary);

    // send email to employee
    try {
        \Mail::to($salary->user->email)
            ->queue(new \App\Mail\SalaryPostedMail($salary));
    } catch (\Exception $e) {
        \Log::error($e->getMessage());
    }

    return back()->with('success','Salary posted successfully');
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
        return back()->with('error','Salary already draft');
    }

    // ==========================
    // RESTORE LOAN
    // ==========================

    $ledger = \App\Models\LoanLedger::where('salary_id',$salary->id)->first();

    if($ledger){

        $loan = \App\Models\Loan::find($ledger->loan_id);

        if($loan){

            $loan->remaining_balance += $ledger->amount;

            if($loan->remaining_balance > 0){
                $loan->status = 'approved';
            }

            $loan->save();
        }

        // remove ledger
        $ledger->delete();
    }

    // mark salary draft
    $salary->update([
        'is_posted' => 0,
        'status' => 'draft',
        'posted_at' => null
    ]);

    return back()->with('success','Salary unposted and loan restored');
}

    /*
    |--------------------------------------------------------------------------
    | Bulk Post
    |--------------------------------------------------------------------------
    */
    public function bulkPost(Request $request)
{
    if (!$request->salary_ids) {
        return back()->with('error', 'No salaries selected');
    }

    $salaries = Salary::whereIn('id', $request->salary_ids)->get();

    foreach ($salaries as $salary) {

        if ($salary->is_posted) {
            continue;
        }

        $this->processLoanDeductionAndPost($salary);

        // send email
        try {
            \Mail::to($salary->user->email)
                ->queue(new \App\Mail\SalaryPostedMail($salary));
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }
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

        $ledger = \App\Models\LoanLedger::where('salary_id',$salary->id)->first();

        if($ledger){

            $loan = \App\Models\Loan::find($ledger->loan_id);

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
            'is_posted' => 0,
            'status' => 'draft',
            'posted_at' => null
        ]);
    }

    return back()->with('success','Selected salaries unposted and loans restored');
}
    /*
    |--------------------------------------------------------------------------
    | Bulk Delete
    |--------------------------------------------------------------------------
    */
    public function bulkDelete(Request $request)
{
    if (!$request->salary_ids) {
        return back()->with('error', 'No salaries selected');
    }

    $salaries = Salary::whereIn('id', $request->salary_ids)->get();

    foreach ($salaries as $salary) {

        if($salary->loan_deduction > 0){

            $loan = \App\Models\Loan::where('user_id',$salary->user_id)->first();

            if($loan){

                $loan->remaining_balance += $salary->loan_deduction;

                if($loan->remaining_balance > 0){
                    $loan->status = 'approved';
                }

                $loan->save();

                \App\Models\LoanLedger::where('loan_id',$loan->id)
                    ->where('type','deduction')
                    ->where('remarks','LIKE','%'.$salary->month.'/'.$salary->year.'%')
                    ->delete();
            }
        }

        $salary->delete();
    }

    return back()->with('success','Selected salaries deleted and loans restored');
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
            $this->processLoanDeductionAndPost($salary);

            try {
                Mail::to($salary->user->email)
                    ->queue(new SalaryPostedMail($salary));
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }

        return back()->with('success','All draft salaries posted.');
    }

    public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,csv'
    ]);

    $import = new SalaryImport();
    Excel::import($import, $request->file('file'));

    if (!empty($import->errors)) {
        return back()->with('error', implode(' | ', $import->errors));
    }

    // 🔹 Store preview in session
    session(['salary_preview' => $import->rows]);

    return view('salary.preview', [
        'rows' => $import->rows
    ]);
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

    // Only proceed if there was a loan deduction
    if ($salary->loan_deduction > 0) {

        $loan = \App\Models\Loan::where('user_id', $salary->user_id)->first();

        if ($loan) {

            // Restore loan balance
            $loan->remaining_balance += $salary->loan_deduction;
            $loan->save();

            // Remove the ledger entry related to this salary
            \App\Models\LoanLedger::where('loan_id', $loan->id)
                ->where('type', 'deduction')
                ->where(function ($q) use ($salary) {
                    $q->where('remarks', 'LIKE', '%'.$salary->month.'/'.$salary->year.'%')
                      ->orWhere('remarks', 'LIKE', '%'.$salary->month.'-'.$salary->year.'%');
                })
                ->delete();
        }
    }

    // Delete salary record
    $salary->delete();

    return redirect()->route('admin.salary.index')
        ->with('success', 'Salary deleted and loan restored successfully');
}
    
    public function confirmImport()
{
    $rows = session('salary_preview');

    if (!$rows) {
        return redirect()->route('admin.salary.index')
            ->with('error','No preview data found');
    }

    foreach ($rows as $row) {

        // only create salary
        Salary::create($row);

    }

    session()->forget('salary_preview');

    return redirect()->route('admin.salary.index')
        ->with('success','Salary Imported Successfully');
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

    /*
    |--------------------------------------------------------------------------
    | Reusable helper method to process loan deductions and post a salary
    |--------------------------------------------------------------------------
    */
    private function processLoanDeductionAndPost(Salary $salary)
    {
        $loan = \App\Models\Loan::where('user_id', $salary->user_id)
            ->where('remaining_balance', '>', 0)
            ->first();

        if ($loan && $salary->loan_deduction > 0) {
            $ledgerExists = \App\Models\LoanLedger::where('salary_id', $salary->id)->exists();

            if (!$ledgerExists) {
                $deduction = $salary->loan_deduction;

                // prevent over deduction
                if ($loan->remaining_balance < $deduction) {
                    $deduction = $loan->remaining_balance;
                }

                // update loan balance
                $loan->remaining_balance -= $deduction;

                // auto close loan
                if ($loan->remaining_balance <= 0) {
                    $loan->status = 'closed';
                }

                $loan->save();

                // insert ledger entry
                \App\Models\LoanLedger::create([
                    'loan_id' => $loan->id,
                    'salary_id' => $salary->id,
                    'amount' => $deduction,
                    'type' => 'deduction',
                    'remarks' => 'Salary deduction '.$salary->month.'/'.$salary->year
                ]);
            }
        }

        $salary->update([
            'is_posted' => 1,
            'status' => 'posted',
            'posted_at' => now()
        ]);
    }
}
