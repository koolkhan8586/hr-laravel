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

        // Sortable columns for the list view
        $sort = in_array($request->sort, ['code', 'employee', 'month', 'net', 'status'])
            ? $request->sort
            : 'period';

        $dir = $request->dir === 'desc' ? 'desc' : 'asc';

        match ($sort) {
            'code' => $query->leftJoin('users', 'users.id', '=', 'salaries.user_id')
                ->select('salaries.*')
                ->orderByRaw("users.employee_code IS NULL OR users.employee_code = ''")
                ->orderBy('users.employee_code', $dir),

            'employee' => $query->leftJoin('users', 'users.id', '=', 'salaries.user_id')
                ->select('salaries.*')
                ->orderBy('users.name', $dir),

            'month'  => $query->orderBy('year', $dir)->orderBy('month', $dir),
            'net'    => $query->orderBy('net_salary', $dir),
            'status' => $query->orderBy('status', $dir),

            default  => $query->orderByDesc('year')->orderByDesc('month'),
        };

        $salaries = $query->get();

        $employees = User::where('role', 'employee')->get();

        $totalSalaries   = $salaries->count();
        $totalNet        = $salaries->where('is_posted', true)->sum('net_salary');
        $totalDeductions = $salaries->sum('total_deductions');
        $draftCount      = $salaries->where('is_posted', false)->count();
        $totalPosted     = $salaries->where('is_posted', true)->count();

        return view('salary.admin-index', compact(
            'salaries',
            'employees',
            'sort',
            'dir',
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
        $category = in_array($request->category, ['teacher', 'staff', 'all'])
            ? $request->category
            : 'staff';

        [$sort, $dir] = $this->sheetSort($request);

        $query = User::with('staff')->where('role', 'employee');

        if ($category !== 'all') {
            $query->where('salary_category', $category);
        }

        $this->applySheetSort($query, $sort, $dir);

        $users = $query->get();

        // Existing rows for this period, keyed by user so the sheet reopens
        // with whatever was last saved.
        $existing = Salary::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('user_id');

        $columns   = \App\Models\SalaryColumn::forCategory($category);
        $taxSlabs  = \App\Models\TaxSlab::activeSlabs();
        $taxBasis  = \App\Models\TaxSlab::basis();

        // What each employee still owes, so the Loan column can show who has a
        // balance and how much is left to take.
        $loanBalances = $this->loanBalances($users->pluck('id'));

        // Employee monthly medical-insurance portion (yearly half ÷ 12), shown
        // as a grey hint on the Insurance column until someone types a figure.
        $insurancePortions = $this->insurancePortions($users->pluck('id'), $month, $year);

        // Anyone on the payroll who won't appear above, so a missing employee
        // can be explained rather than silently dropped.
        $shownIds = $users->pluck('id');

        $missing = User::whereNotIn('id', $shownIds)
            ->where(function ($q) {
                $q->where('role', 'employee')
                  ->orWhereHas('staff');
            })
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'user'   => $u,
                'reason' => $u->role !== 'employee'
                    ? 'Role is "'.$u->role.'", not Employee'
                    : 'On the '.ucfirst($u->salary_category ?: 'staff').' sheet',
            ]);

        return view('salary.sheet', compact(
            'users',
            'existing',
            'month',
            'year',
            'category',
            'columns',
            'taxSlabs',
            'taxBasis',
            'sort',
            'dir',
            'missing',
            'loanBalances',
            'insurancePortions'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Sheet ordering
    |--------------------------------------------------------------------------
    */

    /** Whitelisted sort column and direction from the request. */
    private function sheetSort(Request $request): array
    {
        $sort = in_array($request->sort, ['code', 'name', 'doj'])
            ? $request->sort
            : 'code';

        $dir = $request->dir === 'desc' ? 'desc' : 'asc';

        return [$sort, $dir];
    }

    /** Employees without the sorted value always sink to the bottom. */
    private function applySheetSort($query, string $sort, string $dir): void
    {
        if ($sort === 'name') {
            $query->orderBy('users.name', $dir);
            return;
        }

        if ($sort === 'doj') {
            $query->leftJoin('staff', 'staff.user_id', '=', 'users.id')
                ->select('users.*')
                ->orderByRaw('staff.joining_date IS NULL')
                ->orderBy('staff.joining_date', $dir)
                ->orderBy('users.name');
            return;
        }

        // Codes are zero padded (EMP001...), so a plain string sort is right.
        $query->orderByRaw("users.employee_code IS NULL OR users.employee_code = ''")
            ->orderBy('users.employee_code', $dir)
            ->orderBy('users.name');
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
            'category' => 'required|in:teacher,staff,all',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;

        $from = \Carbon\Carbon::create($year, $month, 1)->subMonth();

        $userIds = User::where('role', 'employee')
            ->when($request->category !== 'all',
                fn ($q) => $q->where('salary_category', $request->category))
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

                // Carry the workings over too, so a copied month can be
                // adjusted rather than re-derived from scratch.
                'formulas'         => $row->formulas,
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
            'category' => 'required|in:teacher,staff,all',
        ]);

        $userIds = User::where('role', 'employee')
            ->when($request->category !== 'all',
                fn ($q) => $q->where('salary_category', $request->category))
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

        // A loan deduction with nothing left to repay used to be taken off the
        // pay and never reach the ledger. Stop the whole sheet and name the
        // rows to correct rather than post something that cannot be recorded.
        if ($problems = $this->loanDeductionProblems($drafts)) {
            return back()->with('error',
                'Nothing was posted. Fix the Loan column first: '.implode(' ', $problems));
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
            'category'       => 'required|in:teacher,staff,all',
            'rows'           => 'required|array',
            'rows.*.user_id' => 'required|exists:users,id',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;

        $columnIds = \App\Models\SalaryColumn::forCategory($request->category)
            ->pluck('id')
            ->all();

        // The cells a formula may be stored against: the fixed columns of the
        // sheet plus whichever custom ones this category has.
        $formulaFields = array_merge([
            'basic_salary', 'extra_load', 'invigilation', 't_payment', 'eidi',
            'increment', 'other_earnings',
            'extra_leaves', 'income_tax', 'loan_deduction', 'insurance',
            'other_deductions', 'cheque_amount',
        ], array_map(fn ($id) => 'custom_'.$id, $columnIds));

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

            // How each figure was worked out, kept so "=6*87" is still there
            // when the sheet is reopened. Only the expression is stored; the
            // amount itself stays a plain number for everything downstream.
            $formulas = [];

            foreach (($row['formulas'] ?? []) as $field => $expression) {

                $expression = trim((string) $expression);

                if ($expression === '' || !str_starts_with($expression, '=')) {
                    continue;
                }

                // Only the sheet's own columns, so nothing unexpected is kept.
                if (!in_array($field, $formulaFields, true)) {
                    continue;
                }

                $formulas[$field] = mb_substr($expression, 0, 200);
            }

            // Skip untouched rows so blank employees don't create empty records.
            if (!$existing && empty($custom) && empty($formulas)
                && collect($values)->every(fn ($v) => $v == 0)) {
                continue;
            }

            $values['custom_values'] = $custom ?: null;
            $values['formulas']      = $formulas ?: null;

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

        $sort = in_array($request->sort, ['code', 'name', 'amount']) ? $request->sort : 'code';
        $dir  = $request->dir === 'desc' ? 'desc' : 'asc';

        $allForPeriod = Salary::with('user.bankPayee')
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->filter(fn ($s) => $s->user);

        $rows = $this->buildBankRows($allForPeriod);

        // A bank sheet is a list of transfers to make, so by default it only
        // carries lines the bank can actually act on: a real account and a
        // real amount. "All" keeps everyone for checking against the sheet.
        $show = $request->show === 'all' ? 'all' : 'payable';

        $excluded = collect();

        if ($show === 'payable') {
            [$rows, $excluded] = $rows->partition(
                fn ($r) => $r['total'] > 0 && filled($r['user']->bank_account_no)
            );
        }

        $key = match ($sort) {
            'name'   => fn ($r) => $r['user']->name,
            'amount' => fn ($r) => $r['total'],
            // Blank codes sort last whichever direction is chosen.
            default  => fn ($r) => ($r['user']->employee_code ?: 'zzzzzzzz'),
        };

        $salaries = ($dir === 'desc'
            ? $rows->sortByDesc($key)
            : $rows->sortBy($key))->values();

        $grandTotal = $salaries->sum('total');


        return view('salary.bank-sheet', compact(
            'salaries',
            'grandTotal',
            'month',
            'year',
            'sort',
            'dir',
            'show',
            'excluded'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | TAX SHEET
    |--------------------------------------------------------------------------
    | Yearly working that produces each employee's monthly tax deduction:
    |
    |   taxable  = salary & wages / medical divisor   (medical is exempt)
    |   payable  = tax on taxable, from the configured slabs
    |   net      = payable - tax adjustment
    |   monthly  = net / 12
    */
    public function taxSheet(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);

        // Month whose salary sheet seeds the yearly figure
        $sourceMonth = (int) ($request->source_month ?? now()->month);

        [$sort, $dir] = $this->sheetSort($request);

        $query = User::with('staff')->where('role', 'employee');

        $category = in_array($request->category, ['teacher', 'staff', 'all'])
            ? $request->category
            : 'all';

        if ($category !== 'all') {
            $query->where('salary_category', $category);
        }

        $this->applySheetSort($query, $sort, $dir);

        $users = $query->get();

        $sheets = \App\Models\TaxSheet::where('year', $year)
            ->get()
            ->keyBy('user_id');

        // Monthly basic from the chosen month, used when a row has no figure yet
        $monthlyBasic = Salary::where('year', $year)
            ->where('month', $sourceMonth)
            ->get()
            ->keyBy('user_id');

        $medicalDivisor = (float) \App\Models\AppSetting::get('tax_medical_divisor', 1.1);
        $taxSlabs       = \App\Models\TaxSlab::activeSlabs();
        $taxBasis       = \App\Models\TaxSlab::basis();

        // Tax actually deducted, taken from posted salaries only, so the sheet
        // records what was really collected rather than what was planned.
        $deducted = Salary::where('year', $year)
            ->where('status', 'posted')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('income_tax', 'month')
                ->map(fn ($v) => (float) $v)
                ->all());

        $resynced = 0;

        $rows = $users->map(function ($user) use ($sheets, $monthlyBasic, $medicalDivisor, $deducted, &$resynced) {

            $sheet = $sheets[$user->id] ?? null;

            // What the salary sheet says the year is worth right now.
            $derived = round((float) ($monthlyBasic[$user->id]->basic_salary ?? 0) * 12, 2);

            if (!$sheet) {
                $annual = $derived;
            } elseif ($sheet->salary_overridden) {
                // Someone typed their own figure; leave it alone.
                $annual = (float) $sheet->annual_salary;
            } else {
                // Track the salary sheet, so a change of wage flows through.
                // Only when there is a salary to read, or a month without one
                // would wipe a good figure.
                if ($derived > 0 && abs($sheet->annual_salary - $derived) > 0.01) {
                    $sheet->update(['annual_salary' => $derived]);
                    $resynced++;
                }

                $annual = $sheet->annual_salary > 0 ? (float) $sheet->annual_salary : $derived;
            }

            // Additional income is a yearly figure carrying no medical
            // component, so it is taxed in full.
            $additional = (float) ($sheet->additional_income ?? 0);

            $taxable = ($medicalDivisor > 0 ? $annual / $medicalDivisor : $annual)
                + $additional;
            $taxable = round($taxable, 2);

            $payable = \App\Models\TaxSlab::annualTaxFor($taxable);

            $adjustment = (float) ($sheet->tax_adjustment ?? 0);
            $net        = round($payable - $adjustment, 2);

            // Deducted as whole rupees, matching what is written to the sheet.
            $monthly = round(max(0, $net) / 12);

            $paidByMonth = $deducted[$user->id] ?? [];
            $paid        = round(array_sum($paidByMonth), 2);

            return [
                'user'          => $user,
                'annual'        => $annual,
                'derived'       => $derived,
                'overridden'    => (bool) ($sheet->salary_overridden ?? false),
                'additional'    => $additional,
                'taxable'       => $taxable,
                'payable'       => $payable,
                'adjustment'    => $adjustment,
                'net'           => $net,
                'monthly'       => $monthly,
                'paid_by_month' => $paidByMonth,
                'paid'          => $paid,
                // Payable tax (which already includes additional income)
                // less the adjustment, less what has been collected.
                'balance'       => round($net - $paid, 2),
            ];
        });

        return view('salary.tax-sheet', compact(
            'rows',
            'resynced',
            'year',
            'sourceMonth',
            'category',
            'sort',
            'dir',
            'medicalDivisor',
            'taxSlabs',
            'taxBasis'
        ));
    }

    /**
     * Save the editable columns: yearly salary and the tax adjustment.
     */
    public function taxSheetStore(Request $request)
    {
        $request->validate([
            'year'           => 'required|integer|min:2000|max:2100',
            'rows'           => 'required|array',
            'rows.*.user_id' => 'required|exists:users,id',
        ]);

        $year        = (int) $request->year;
        $sourceMonth = (int) ($request->source_month ?? now()->month);
        $saved       = 0;

        // What the salary sheet says each year is worth, to tell a typed
        // figure apart from one that simply tracks the salary sheet.
        $derivedFor = Salary::where('year', $year)
            ->where('month', $sourceMonth)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->user_id => round((float) $s->basic_salary * 12, 2)]);

        foreach ($request->rows as $row) {

            $amount = fn ($k) => round((float) str_replace(',', '', $row[$k] ?? 0), 2);

            $annual     = $amount('annual_salary');
            $additional = $amount('additional_income');
            $adjustment = $amount('tax_adjustment');

            $existing = \App\Models\TaxSheet::where('user_id', $row['user_id'])
                ->where('year', $year)
                ->first();

            // Don't create empty rows for employees nobody has touched.
            if (!$existing && $annual == 0 && $additional == 0 && $adjustment == 0) {
                continue;
            }

            $derived = $derivedFor[$row['user_id']] ?? 0;

            \App\Models\TaxSheet::updateOrCreate(
                ['user_id' => $row['user_id'], 'year' => $year],
                [
                    'annual_salary'     => $annual,
                    // Only a figure that differs from the salary sheet counts
                    // as an override; anything matching keeps tracking it.
                    'salary_overridden' => $derived > 0 && abs($annual - $derived) > 0.01,
                    'additional_income' => $additional,
                    'tax_adjustment'    => $adjustment,
                ]
            );

            $saved++;
        }

        return redirect()->route('admin.salary.tax.sheet', $request->only([
            'year', 'category', 'source_month', 'sort', 'dir',
        ]))->with('success', "Tax sheet saved ({$saved} employees).");
    }

    /**
     * Drop any typed yearly salaries and take the salary sheet's figures.
     */
    public function taxSheetResync(Request $request)
    {
        $request->validate([
            'year'         => 'required|integer|min:2000|max:2100',
            'source_month' => 'required|integer|min:1|max:12',
        ]);

        $year  = (int) $request->year;
        $month = (int) $request->source_month;

        $derivedFor = Salary::where('year', $year)
            ->where('month', $month)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->user_id => round((float) $s->basic_salary * 12, 2)]);

        $updated = 0;

        foreach (\App\Models\TaxSheet::where('year', $year)->get() as $sheet) {

            $derived = $derivedFor[$sheet->user_id] ?? 0;

            if ($derived <= 0) {
                continue;
            }

            $sheet->update([
                'annual_salary'     => $derived,
                'salary_overridden' => false,
            ]);

            $updated++;
        }

        $period = \Carbon\Carbon::create($year, $month, 1)->format('F Y');

        return back()->with('success',
            "Yearly salary taken from the {$period} salary sheet for {$updated} employee(s).");
    }

    /**
     * Push the calculated monthly tax onto a month's salary sheet.
     */
    public function taxSheetApply(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        return $this->writeTaxFromTaxSheet(
            (int) $request->year,
            (int) $request->month
        );
    }

    /**
     * Printable page: income tax deducted for one month (from posted salaries).
     */
    public function taxSheetMonthlyDeduction(Request $request, int $year, int $month)
    {
        abort_unless($month >= 1 && $month <= 12, 404);

        $data = $this->monthlyTaxDeductionData($request, $year, $month);

        return view('salary.tax-monthly-deduction', $data);
    }

    /**
     * Download PDF of income tax deducted for one month.
     */
    public function taxSheetMonthlyDeductionPdf(Request $request, int $year, int $month)
    {
        abort_unless($month >= 1 && $month <= 12, 404);

        $data = $this->monthlyTaxDeductionData($request, $year, $month);
        $data['orgName'] = \App\Models\AppSetting::get(
            'org_name',
            'The University of Lahore (City Campus)'
        );

        $period = \Carbon\Carbon::create($year, $month, 1)->format('F_Y');

        $pdf = Pdf::loadView('salary.tax-monthly-deduction-pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("Tax_Deducted_{$period}.pdf");
    }

    /**
     * Posted salary tax deductions for one calendar month.
     *
     * Rows match the Tax Sheet working columns, with Monthly Tax set to
     * that month's posted deduction. Employees with 0 tax are excluded.
     *
     * @return array{
     *   rows: \Illuminate\Support\Collection,
     *   total: float,
     *   year: int,
     *   month: int,
     *   category: string,
     *   sort: string,
     *   dir: string,
     *   medicalDivisor: float
     * }
     */
    private function monthlyTaxDeductionData(Request $request, int $year, int $month): array
    {
        [$sort, $dir] = $this->sheetSort($request);

        $category = in_array($request->category, ['teacher', 'staff', 'all'], true)
            ? $request->category
            : 'all';

        $medicalDivisor = (float) \App\Models\AppSetting::get('tax_medical_divisor', 1.1);

        $salaries = Salary::with('user')
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'posted')
            ->where('income_tax', '>', 0)
            ->whereHas('user', function ($q) use ($category) {
                $q->where('role', 'employee');
                if ($category !== 'all') {
                    $q->where('salary_category', $category);
                }
            })
            ->get();

        $sheets = \App\Models\TaxSheet::where('year', $year)
            ->whereIn('user_id', $salaries->pluck('user_id'))
            ->get()
            ->keyBy('user_id');

        $rows = $salaries->map(function ($salary) use ($sheets, $medicalDivisor) {
            $user  = $salary->user;
            $sheet = $sheets[$user->id] ?? null;

            // Prefer the saved tax sheet yearly working; fall back to this
            // month's basic × 12 when no sheet row exists yet.
            $annual = $sheet
                ? (float) $sheet->annual_salary
                : round((float) ($salary->basic_salary ?? 0) * 12, 2);

            $additional = (float) ($sheet->additional_income ?? 0);
            $adjustment = (float) ($sheet->tax_adjustment ?? 0);

            $taxable = ($medicalDivisor > 0 ? $annual / $medicalDivisor : $annual)
                + $additional;
            $taxable = round($taxable, 2);

            $payable = \App\Models\TaxSlab::annualTaxFor($taxable);
            $net     = round($payable - $adjustment, 2);

            // That month's actual posted deduction (never the planned /12).
            $monthlyDeducted = round((float) $salary->income_tax, 2);

            // Posted basic for this month (monthly salary on the salary sheet).
            $monthlySalary = round((float) ($salary->basic_salary ?? 0), 2);

            return (object) [
                'user'           => $user,
                'monthly_salary' => $monthlySalary,
                'annual'         => $annual,
                'additional'     => $additional,
                'taxable'        => $taxable,
                'payable'        => $payable,
                'adjustment'     => $adjustment,
                'net'            => $net,
                'monthly'        => $monthlyDeducted,
                'income_tax'     => $monthlyDeducted,
            ];
        })->sortBy(function ($row) use ($sort) {
            $user = $row->user;
            if ($sort === 'name') {
                return strtolower((string) ($user->name ?? ''));
            }

            $code = trim((string) ($user->employee_code ?? ''));

            return $code === '' ? 'zzz_'.$user->name : strtolower($code);
        }, SORT_REGULAR, $dir === 'desc')->values();

        $total = round((float) $rows->sum('monthly'), 2);
        $totalMonthlySalary = round((float) $rows->sum('monthly_salary'), 2);

        return compact(
            'rows',
            'total',
            'totalMonthlySalary',
            'year',
            'month',
            'category',
            'sort',
            'dir',
            'medicalDivisor'
        );
    }

    /**
     * Monthly deduction the tax sheet works out for one employee.
     * Shared so the tax sheet and the salary sheet can never disagree.
     *
     * @return array{monthly: float, clamped: bool}
     */
    private function monthlyTaxFromSheet(\App\Models\TaxSheet $sheet): array
    {
        $divisor = (float) \App\Models\AppSetting::get('tax_medical_divisor', 1.1);

        $taxable = $sheet->taxableIncome($divisor);

        $net = \App\Models\TaxSlab::annualTaxFor($taxable) - $sheet->tax_adjustment;

        // An adjustment larger than the tax would otherwise write a negative
        // deduction, which would quietly increase net pay.
        $clamped = $net < 0;

        if ($clamped) {
            $net = 0;
        }

        // Payroll deducts whole rupees, so 363.64 comes off as 364.
        return ['monthly' => round($net / 12), 'clamped' => $clamped];
    }

    /**
     * Write the tax sheet's monthly figure onto a month's salary rows.
     * Optionally limited to one sheet category.
     */
    private function writeTaxFromTaxSheet(int $year, int $month, ?string $category = null)
    {
        $sheets = \App\Models\TaxSheet::with('user')->where('year', $year)->get();

        if ($sheets->isEmpty()) {
            return back()->with('error',
                'There is no saved tax sheet for '.$year.' yet. Fill in the Tax Sheet and save it first.');
        }

        if ($category && $category !== 'all') {
            $sheets = $sheets->filter(
                fn ($s) => $s->user && $s->user->salary_category === $category
            );
        }

        $applied = 0;
        $skipped = 0;
        $clamped = 0;
        $noRow   = 0;

        foreach ($sheets as $sheet) {

            $salary = Salary::where('user_id', $sheet->user_id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if (!$salary) {
                $noRow++;
                continue;
            }

            // A posted salary is finalised; leave it alone.
            if ($salary->isPosted()) {
                $skipped++;
                continue;
            }

            $result = $this->monthlyTaxFromSheet($sheet);

            $salary->update(['income_tax' => $result['monthly']]);

            if ($result['clamped']) {
                $clamped++;
            }

            $applied++;
        }

        $period = \Carbon\Carbon::create($year, $month, 1)->format('F Y');

        $message = "Tax column updated from the tax sheet for {$applied} employee(s) on {$period}.";

        if ($skipped) {
            $message .= " {$skipped} posted row(s) were left unchanged.";
        }

        if ($clamped) {
            $message .= " {$clamped} row(s) had an adjustment larger than the tax due and were set to zero.";
        }

        if ($noRow) {
            $message .= " {$noRow} employee(s) on the tax sheet have no salary row for {$period}.";
        }

        return back()->with('success', $message);
    }

    /**
     * Pull the tax column on the salary sheet from the saved tax sheet.
     */
    public function sheetPullTax(Request $request)
    {
        $request->validate([
            'month'    => 'required|integer|min:1|max:12',
            'year'     => 'required|integer|min:2000|max:2100',
            'category' => 'required|in:teacher,staff,all',
        ]);

        return $this->writeTaxFromTaxSheet(
            (int) $request->year,
            (int) $request->month,
            $request->category
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MEDICAL INSURANCE
    |--------------------------------------------------------------------------
    | Yearly working, same shape as the tax sheet. Enter the total premium;
    | LSAF and the employee each take half, and the employee half is spread
    | over 12 months. That monthly figure is hinted on the salary sheet
    | Insurance column. Posted salaries fill the month columns.
    */
    public function medicalInsurance(Request $request)
    {
        $year = (int) ($request->year ?? max(now()->year, \App\Models\MedicalInsurance::START_YEAR));

        if ($year < \App\Models\MedicalInsurance::START_YEAR) {
            $year = \App\Models\MedicalInsurance::START_YEAR;
        }

        $months = \App\Models\MedicalInsurance::monthsForYear($year);

        $category = in_array($request->category, ['teacher', 'staff', 'all'])
            ? $request->category
            : 'all';

        [$sort, $dir] = $this->sheetSort($request);

        $query = User::with('staff')->where('role', 'employee');

        if ($category !== 'all') {
            $query->where('salary_category', $category);
        }

        $this->applySheetSort($query, $sort, $dir);

        $users = $query->get();

        $existing = \App\Models\MedicalInsurance::where('year', $year)
            ->get()
            ->keyBy('user_id');

        // Insurance actually taken off posted pay, keyed user → month → amount.
        // 2026 only counts August onward.
        $deductedQuery = Salary::where('year', $year)
            ->where('status', 'posted');

        if ($year === \App\Models\MedicalInsurance::START_YEAR) {
            $deductedQuery->where('month', '>=', \App\Models\MedicalInsurance::START_MONTH);
        }

        $deducted = $deductedQuery
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('insurance', 'month')
                ->map(fn ($v) => (float) $v)
                ->all());

        $rows = $users->map(function ($user) use ($existing, $deducted) {

            $sheet    = $existing[$user->id] ?? null;
            $total    = (float) ($sheet->total_amount ?? 0);
            $split    = \App\Models\MedicalInsurance::splitTotal($total);
            $paidByMonth = $deducted[$user->id] ?? [];
            $paid     = round(array_sum($paidByMonth), 2);

            return [
                'user'             => $user,
                'total'            => $total,
                'lsaf'             => $split['lsaf_portion'],
                'employee'         => $split['employee_portion'],
                'monthly'          => $split['monthly_portion'],
                'paid_by_month'    => $paidByMonth,
                'paid'             => $paid,
                'balance'          => round($split['employee_portion'] - $paid, 2),
            ];
        });

        return view('salary.medical-insurance', compact(
            'rows',
            'year',
            'months',
            'category',
            'sort',
            'dir'
        ));
    }

    public function medicalInsuranceStore(Request $request)
    {
        $request->validate([
            'year'           => 'required|integer|min:'.\App\Models\MedicalInsurance::START_YEAR.'|max:2100',
            'rows'           => 'required|array',
            'rows.*.user_id' => 'required|exists:users,id',
        ]);

        $year  = (int) $request->year;
        $saved = 0;

        foreach ($request->rows as $row) {

            $total = round((float) str_replace(',', '', $row['total_amount'] ?? 0), 2);

            $split = \App\Models\MedicalInsurance::splitTotal($total);
            unset($split['monthly_portion']);

            \App\Models\MedicalInsurance::updateOrCreate(
                [
                    'user_id' => $row['user_id'],
                    'year'    => $year,
                ],
                $split
            );

            $saved++;
        }

        return redirect()->route('admin.salary.medical', $request->only([
            'year', 'category', 'sort', 'dir',
        ]))->with('success', "Medical insurance sheet saved ({$saved} employees).");
    }

    public function medicalInsuranceCopyPrevious(Request $request)
    {
        $request->validate([
            'year'     => 'required|integer|min:'.\App\Models\MedicalInsurance::START_YEAR.'|max:2100',
            'category' => 'required|in:teacher,staff,all',
        ]);

        $year = (int) $request->year;

        $userIds = User::where('role', 'employee')
            ->when($request->category !== 'all',
                fn ($q) => $q->where('salary_category', $request->category))
            ->pluck('id');

        $previous = \App\Models\MedicalInsurance::where('year', $year - 1)
            ->whereIn('user_id', $userIds)
            ->get();

        if ($previous->isEmpty()) {
            return back()->with('error',
                'Nothing to copy - there is no '.($year - 1).' medical insurance sheet for this category.');
        }

        $copied = 0;

        foreach ($previous as $row) {

            \App\Models\MedicalInsurance::updateOrCreate(
                [
                    'user_id' => $row->user_id,
                    'year'    => $year,
                ],
                [
                    'total_amount'     => $row->total_amount,
                    'lsaf_portion'     => $row->lsaf_portion,
                    'employee_portion' => $row->employee_portion,
                ]
            );

            $copied++;
        }

        return redirect()->route('admin.salary.medical', [
            'year'     => $year,
            'category' => $request->category,
        ])->with('success', "Copied {$copied} row(s) from ".($year - 1).'.');
    }

    /*
    |--------------------------------------------------------------------------
    | BANK LETTER
    |--------------------------------------------------------------------------
    | Covering letter that goes to the bank with the annexure. Prints bare so
    | it can go onto pre-printed letterhead.
    */
    public function bankLetter(Request $request)
    {
        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year ?? now()->year);

        // Same rule as the bank sheet: only lines the bank can act on.
        $amount = $this->buildBankRows(
            Salary::with('user.bankPayee')
                ->where('month', $month)
                ->where('year', $year)
                ->get()
                ->filter(fn ($s) => $s->user)
        )
        ->filter(fn ($r) => $r['total'] > 0 && filled($r['user']->bank_account_no))
        ->sum('total');

        // What the bank sheet says for this month, always recomputed.
        $sheetAmount = round($amount, 2);

        // The amount box is an override and is normally left empty. Only a
        // figure actually typed into it wins, otherwise changing the month
        // would keep resubmitting whatever the box happened to be holding.
        $override = $request->input('amount');
        $hasOverride = $override !== null && trim((string) $override) !== '';

        $amount = $hasOverride
            ? round((float) str_replace(',', '', $override), 2)
            : $sheetAmount;

        $letterDate = $request->filled('letter_date')
            ? \Carbon\Carbon::parse($request->letter_date)
            : now();

        $chequeDate = $request->filled('cheque_date')
            ? \Carbon\Carbon::parse($request->cheque_date)
            : $letterDate->copy();

        $chequeNo = $request->cheque_no ?? '';

        $settings = [
            'bank'      => \App\Models\AppSetting::get('bank_letter_bank', 'Al Baraka Bank Ltd.'),
            'branch'    => \App\Models\AppSetting::get('bank_letter_branch', 'Gulberg III Lahore'),
            'signatory' => \App\Models\AppSetting::get('bank_letter_signatory', 'Mohammad Maqbool'),
            'top_mm'    => (int) \App\Models\AppSetting::get('bank_letter_top_mm', 45),
        ];

        return view('salary.bank-letter', compact(
            'month',
            'year',
            'amount',
            'sheetAmount',
            'hasOverride',
            'letterDate',
            'chequeDate',
            'chequeNo',
            'settings'
        ));
    }

    /**
     * Bank, branch, signatory and the gap left for the letterhead.
     */
    public function bankLetterSettings(Request $request)
    {
        $request->validate([
            'bank'      => 'required|string|max:150',
            'branch'    => 'nullable|string|max:150',
            'signatory' => 'nullable|string|max:150',
            'top_mm'    => 'required|integer|min:0|max:150',
        ]);

        \App\Models\AppSetting::put('bank_letter_bank', $request->bank);
        \App\Models\AppSetting::put('bank_letter_branch', $request->branch);
        \App\Models\AppSetting::put('bank_letter_signatory', $request->signatory);
        \App\Models\AppSetting::put('bank_letter_top_mm', $request->top_mm);

        return back()->with('success', 'Letter details saved.');
    }

    /*
    |--------------------------------------------------------------------------
    | Turn a month's salaries into bank sheet lines
    |--------------------------------------------------------------------------
    | One line per account that gets credited. An employee who is set to be
    | paid into someone else's account contributes to that person's line
    | instead of getting one of their own. Employees paid entirely by cheque
    | still appear, with no amount, so the sheet stays a full roster.
    */
    private function buildBankRows($salaries)
    {
        $rows = [];

        $touch = function (&$rows, User $user) {
            if (!isset($rows[$user->id])) {
                $rows[$user->id] = [
                    'user'         => $user,
                    'total'        => 0.0,
                    'own'          => 0.0,
                    'contributors' => [],
                ];
            }
        };

        foreach ($salaries as $salary) {

            $employee = $salary->user;
            $amount   = (float) $salary->bank_amount;

            // Guard against a payee chain pointing back at the employee.
            $payee = $employee->bankPayee;

            if ($payee && $payee->id !== $employee->id) {
                $touch($rows, $payee);
                $rows[$payee->id]['total'] += $amount;

                if ($amount > 0) {
                    $rows[$payee->id]['contributors'][] = [
                        'user'   => $employee,
                        'amount' => $amount,
                    ];
                }

                continue;
            }

            $touch($rows, $employee);
            $rows[$employee->id]['total'] += $amount;
            $rows[$employee->id]['own']   += $amount;
        }

        return collect($rows)->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Bank sheet as CSV (opens straight in Excel)
    |--------------------------------------------------------------------------
    */
    public function bankSheetExport(Request $request)
    {
        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year ?? now()->year);

        $period = \Carbon\Carbon::create($year, $month, 1);

        $salaries = $this->buildBankRows(
                Salary::with('user.bankPayee')
                    ->where('month', $month)
                    ->where('year', $year)
                    ->get()
                    ->filter(fn ($s) => $s->user)
            )
            ->filter(fn ($r) => $r['total'] > 0 && filled($r['user']->bank_account_no))
            ->sortBy(fn ($r) => ($r['user']->employee_code ?: 'zzzzzzzz'))
            ->values();

        $filename = 'bank-sheet-'.$period->format('Y-m').'.csv';

        return response()->streamDownload(function () use ($salaries, $period) {

            $out = fopen('php://output', 'w');

            // Excel needs the BOM to read non-ASCII names correctly.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['ANNEXURE-A']);
            fputcsv($out, ['Salaries to be Credited']);
            fputcsv($out, [$period->format('F Y')]);
            fputcsv($out, []);
            fputcsv($out, ['SR NO.', 'Employee ID', 'Name of Employee', 'Account No.', 'Amount']);

            $total = 0;

            foreach ($salaries as $i => $row) {
                $total += $row['total'];

                $name = $row['user']->name;

                if (!empty($row['contributors'])) {
                    $name .= ' (incl. '.collect($row['contributors'])
                        ->map(fn ($c) => $c['user']->name)->implode(', ').')';
                }

                fputcsv($out, [
                    $i + 1,
                    $row['user']->employee_code,
                    $name,
                    // Leading apostrophe stops Excel mangling long account
                    // numbers into scientific notation.
                    $row['user']->bank_account_no ? "'".$row['user']->bank_account_no : '',
                    $row['total'] > 0 ? round($row['total'], 2) : '',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', '', 'GRAND TOTAL', '', round($total, 2)]);

            fclose($out);

        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Single Post
    |--------------------------------------------------------------------------
    */
    public function post($id)
{
    $salary = Salary::with('user')->findOrFail($id);

    // prevent double posting
    if ($salary->is_posted) {
        return back()->with('error','Salary already posted');
    }

    if ($problems = $this->loanDeductionProblems(collect([$salary]))) {
        return back()->with('error', 'Not posted. '.implode(' ', $problems));
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

    $this->reverseLoanLedger($salary);

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

    $salaries = Salary::with('user')->whereIn('id', $request->salary_ids)->get();

    $pending = $salaries->reject(fn ($s) => $s->is_posted);

    if ($problems = $this->loanDeductionProblems($pending)) {
        return back()->with('error',
            'Nothing was posted. Fix the Loan column first: '.implode(' ', $problems));
    }

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

        $this->reverseLoanLedger($salary);

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
        $drafts = Salary::with('user')->where('is_posted', false)->get();

        if ($problems = $this->loanDeductionProblems($drafts)) {
            return back()->with('error',
                'Nothing was posted. Fix the Loan column first: '.implode(' ', $problems));
        }

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
        $deduction = round((float) $salary->loan_deduction, 2);

        if ($deduction > 0 && !\App\Models\LoanLedger::where('salary_id', $salary->id)->exists()) {

            // Oldest first, so a second loan is only touched once the first is
            // clear. Anything deducted has to land on a loan; money taken off
            // someone's pay must never disappear without a ledger entry.
            $loans = $this->activeLoans($salary->user_id);

            $left = $deduction;

            foreach ($loans as $loan) {

                if ($left <= 0) {
                    break;
                }

                $take = min($left, (float) $loan->remaining_balance);

                $loan->remaining_balance = round($loan->remaining_balance - $take, 2);

                if ($loan->remaining_balance <= 0) {
                    $loan->remaining_balance = 0;
                    $loan->status = 'closed';
                }

                $loan->save();

                \App\Models\LoanLedger::create([
                    'loan_id'   => $loan->id,
                    'salary_id' => $salary->id,
                    'amount'    => $take,
                    'type'      => 'deduction',
                    'remarks'   => 'Salary deduction '.$salary->month.'/'.$salary->year,
                ]);

                $left = round($left - $take, 2);
            }

            // Nothing outstanding to put it against. Posting is blocked before
            // it gets this far, so reaching here means something is wrong and
            // it must be recorded rather than quietly pocketed.
            if ($left > 0) {
                \Log::error('Loan deduction with nothing to repay', [
                    'salary_id' => $salary->id,
                    'user_id'   => $salary->user_id,
                    'deducted'  => $deduction,
                    'unapplied' => $left,
                ]);
            }
        }

        $salary->update([
            'is_posted' => 1,
            'status' => 'posted',
            'posted_at' => now()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Loans still being repaid
    |--------------------------------------------------------------------------
    */

    /**
     * Undo whatever a salary took off an employee's loans.
     *
     * A single month can touch more than one loan, so every ledger row for the
     * salary is put back, not just the first.
     */
    private function reverseLoanLedger(Salary $salary): void
    {
        $entries = \App\Models\LoanLedger::where('salary_id', $salary->id)->get();

        foreach ($entries as $entry) {

            $loan = \App\Models\Loan::find($entry->loan_id);

            if ($loan) {
                $loan->remaining_balance = round($loan->remaining_balance + $entry->amount, 2);

                if ($loan->remaining_balance > 0) {
                    $loan->status = 'approved';
                }

                $loan->save();
            }

            $entry->delete();
        }
    }

    /** An employee's outstanding loans, oldest first. */
    private function activeLoans(int $userId)
    {
        return \App\Models\Loan::where('user_id', $userId)
            ->where('remaining_balance', '>', 0)
            ->orderBy('id')
            ->get();
    }

    /** Outstanding loan balance per user, for every user id given. */
    private function loanBalances($userIds): array
    {
        return \App\Models\Loan::whereIn('user_id', $userIds)
            ->where('remaining_balance', '>', 0)
            ->selectRaw('user_id, SUM(remaining_balance) as balance')
            ->groupBy('user_id')
            ->pluck('balance', 'user_id')
            ->map(fn ($b) => round((float) $b, 2))
            ->all();
    }

    /** Monthly employee medical-insurance portion per user, from Aug 2026 on. */
    private function insurancePortions($userIds, int $month, int $year): array
    {
        if (! \App\Models\MedicalInsurance::appliesToSalaryMonth($year, $month)) {
            return [];
        }

        return \App\Models\MedicalInsurance::whereIn('user_id', $userIds)
            ->where('year', $year)
            ->where('employee_portion', '>', 0)
            ->pluck('employee_portion', 'user_id')
            ->map(fn ($b) => \App\Models\MedicalInsurance::monthlyPortion((float) $b))
            ->all();
    }

    /**
     * Salaries whose loan deduction cannot be repaid.
     *
     * Returns one readable line per problem row, so posting can stop and say
     * exactly which employees to fix.
     */
    private function loanDeductionProblems($salaries): array
    {
        $problems = [];

        $balances = $this->loanBalances($salaries->pluck('user_id')->unique());

        foreach ($salaries as $salary) {

            $deduction = round((float) $salary->loan_deduction, 2);

            if ($deduction <= 0) {
                continue;
            }

            // Already recorded on a previous post, so it is not double counted.
            if (\App\Models\LoanLedger::where('salary_id', $salary->id)->exists()) {
                continue;
            }

            $balance = $balances[$salary->user_id] ?? 0.0;
            $name    = $salary->user->name ?? ('User #'.$salary->user_id);

            if ($balance <= 0) {
                $problems[] = $name.' has Rs '.number_format($deduction).
                    ' in the Loan column but no loan left to repay.';
                continue;
            }

            if ($deduction > $balance) {
                $problems[] = $name.' has Rs '.number_format($deduction).
                    ' in the Loan column but only Rs '.number_format($balance).' is outstanding.';
            }
        }

        return $problems;
    }
}
