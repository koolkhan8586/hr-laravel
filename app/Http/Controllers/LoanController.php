<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoansExport;
use App\Exports\LoanApplicationsExport;
use App\Imports\LoansImport;
use App\Models\LoanLedger;
use App\Services\LoanWhatsAppNotifier;
use App\Services\WahaService;
use Carbon\Carbon;


class LoanController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE SECTION
    |--------------------------------------------------------------------------
    */

    /**
     * The application form, with whatever the employee has applied for before.
     */
    public function apply()
    {
        $user = auth()->user();

        $applications = Loan::where('user_id', $user->id)
            ->applications()
            ->orderByDesc('requested_at')
            ->get();

        // What they still owe on loans already running, so they can see it
        // before asking for more - and so can whoever approves it.
        $outstanding = (float) Loan::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('remaining_balance');

        return view('loan.apply', compact('applications', 'outstanding'));
    }

    public function storeRequest(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'installments' => 'required|integer|min:1|max:120',
            'purpose' => 'required|string|max:1000',
            'deduction_start_month' => 'nullable|date',
        ], [
            'purpose.required' => 'Please say what the loan is for.',
        ]);

        // One application at a time: a second one while the first is still
        // being looked at only confuses whoever has to decide them.
        $alreadyPending = Loan::where('user_id', auth()->id())
            ->applications()
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()
                ->withInput()
                ->with('error', 'You already have a loan application waiting for a decision.');
        }

        $monthly = round($data['amount'] / $data['installments'], 2);

        $loan = Loan::create([
            'user_id' => auth()->id(),
            'amount' => $data['amount'],
            'installments' => $data['installments'],
            'monthly_deduction' => $monthly,
            // Nothing is owed until it is approved, so no balance is carried
            // and nothing is written to the ledger yet.
            'remaining_balance' => 0,
            'status' => 'pending',
            'purpose' => $data['purpose'],
            // Left blank on the form means "from the next salary", so the
            // key is simply absent rather than empty.
            'deduction_start_month' => filled($data['deduction_start_month'] ?? null)
                ? Carbon::parse($data['deduction_start_month'])->startOfMonth()->toDateString()
                : null,
            'requested_at' => now(),
        ]);

        // Tell the approvers. A WhatsApp that does not get through must not
        // lose the application, so the outcome is only reported, never fatal.
        $notified = app(LoanWhatsAppNotifier::class)->notifyApprovers($loan);

        // The employee gets told whether the approvers were pinged, not why
        // not: the reason is somebody else's setting, and the applications
        // screen says it plainly to the people who can fix it.
        $note = ($notified['sent'] ?? 0) > 0
            ? 'The approvers have been sent a WhatsApp about it.'
            : 'It is on record and waiting to be looked at. The WhatsApp alert to the approvers did not go out, so please follow it up with HR.';

        return redirect()
            ->route('loan.apply')
            ->with('success', 'Loan application submitted. Reference '.$loan->reference().'.')
            ->with(($notified['sent'] ?? 0) > 0 ? 'info' : 'warning', $note);
    }

    /**
     * The employee takes their own application back.
     *
     * Only while it is still waiting: once it has been approved the money is
     * granted and the ledger entry written, and once it has been turned down
     * the decision is a record in its own right. Either way it is no longer
     * theirs to remove, so they are told to speak to HR instead.
     *
     * The row is marked withdrawn rather than deleted, because the approvers
     * were sent a WhatsApp with links to it: deleting it would leave them
     * tapping a link that 404s, instead of being told it was taken back.
     */
    public function cancelApplication($id)
    {
        $loan = Loan::with('user')->findOrFail($id);

        abort_unless($loan->user_id === auth()->id(), 403, 'This loan application is not yours.');

        if (! $loan->isCancellableBy(auth()->user())) {

            $reason = $loan->status === 'approved'
                ? 'This loan has already been approved, so it cannot be cancelled here. Ask HR if it needs to be reversed.'
                : 'This application has already been '.$loan->status.', so there is nothing to cancel.';

            return back()->with('error', $reason);
        }

        $loan->update([
            'status' => 'cancelled',
            'remaining_balance' => 0,
            'decided_by' => $loan->user->name.' (the applicant)',
            'decided_via' => 'employee',
            'decided_at' => now(),
            'decision_remarks' => 'Withdrawn by the employee',
        ]);

        $this->notifyApproversOfWithdrawal($loan);

        return back()->with('success', 'Application '.$loan->reference().' has been cancelled. You can apply again whenever you need to.');
    }

    /**
     * Tell whoever was asked to decide it that they no longer need to.
     *
     * Best effort: the withdrawal stands whether or not the message goes out.
     */
    protected function notifyApproversOfWithdrawal(Loan $loan): void
    {
        $waha = app(WahaService::class);

        if (! $waha->enabled()) {
            return;
        }

        $message = implode("\n", [
            'Loan Application Withdrawn',
            '',
            'Reference: '.$loan->reference(),
            'Employee: '.($loan->user->name ?? 'Employee'),
            'Amount: Rs '.number_format((float) $loan->amount, 2),
            '',
            'The employee has taken this application back, so no decision is needed.',
            '- LSAF HR',
        ]);

        foreach (\App\Models\LoanApprover::active() as $approver) {
            $waha->sendToMobile($approver->mobile, $message);
        }
    }

    /**
     * The printed application form: the employee's own, or any of them for
     * whoever manages loans.
     *
     * Same view for the screen and the PDF, so what is printed is what was
     * seen.
     */
    public function printApplication(Request $request, $id)
    {
        $loan = Loan::with('user.staff')->findOrFail($id);

        abort_unless(
            $loan->user_id === auth()->id() || auth()->user()->canManageLoans(),
            403,
            'This loan application is not yours.'
        );

        $data = [
            'loan' => $loan,
            'outstanding' => (float) Loan::where('user_id', $loan->user_id)
                ->where('id', '!=', $loan->id)
                ->whereIn('status', ['approved', 'completed'])
                ->sum('remaining_balance'),
        ];

        if ($request->boolean('pdf')) {

            // dompdf ignores @media print, so the Print / Download buttons are
            // left out of the PDF by this flag rather than by CSS.
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'loan.application-print',
                $data + ['isPdf' => true]
            )->setPaper('a4');

            return $pdf->download('loan-application-'.$loan->reference().'.pdf');
        }

        return view('loan.application-print', $data);
    }

    public function employeeLedger($id)
{
    $loan = Loan::with('ledgers')
        ->where('user_id', auth()->id())
        ->findOrFail($id);

    return view('loan.employee-ledger', compact('loan'));
}

    public function myLoan()
{
    /*
    | Balances only: an application still waiting for a decision, or one that
    | was turned down, is not money owed. Counting them here showed an
    | employee a Total Loan they had not been given, and dragged the status
    | to Approved the moment they applied. Their applications and where each
    | one stands are on the apply page.
    */
    $decided = fn ($query) => $query->whereNotIn('status', ['pending', 'rejected', 'cancelled']);

    $loan = Loan::where('user_id', auth()->id())
                ->tap($decided)
                ->latest()
                ->first();

    $pendingApplication = Loan::where('user_id', auth()->id())
        ->applications()
        ->where('status', 'pending')
        ->latest('requested_at')
        ->first();

    if (!$loan) {
        return view('loan.my', [
            'loan' => null,
            'pendingApplication' => $pendingApplication,
        ]);
    }

    $allLoans = Loan::where('user_id', auth()->id())->tap($decided)->get();
    $loanIds = $allLoans->pluck('id');

    // Consolidated attributes
    $loan->amount = $allLoans->sum('amount');
    $loan->opening_balance = $allLoans->sum('opening_balance');
    $loan->remaining_balance = $allLoans->sum('remaining_balance');

    $hasActive = $allLoans->contains(function($l){ return $l->status === 'approved'; });
    $loan->status = $hasActive ? 'approved' : 'closed';

    // Merge and sort all ledger history
    $loan->ledgers = LoanLedger::whereIn('loan_id', $loanIds)
        ->orderBy('created_at', 'asc')
        ->get();

    return view('loan.my', compact('loan', 'pendingApplication'));
}




    /*
    |--------------------------------------------------------------------------
    | ADMIN SECTION
    |--------------------------------------------------------------------------
    */

    public function index()
{
    // Loans that exist, not applications: a pending request is money nobody
    // has handed over yet, and a rejected one never will be. Both used to be
    // summed into the employee's outstanding balance here and shown as
    // Approved. They live on the applications screen instead.
    $loans = Loan::with('user')
        ->whereNotIn('status', ['pending', 'rejected', 'cancelled'])
        ->latest()
        ->get()
        ->groupBy('user_id')
        ->map(function ($userLoans) {
            $baseLoan = $userLoans->first();

            $baseLoan->amount = $userLoans->sum('amount');
            $baseLoan->opening_balance = $userLoans->sum('opening_balance');
            $baseLoan->remaining_balance = $userLoans->sum('remaining_balance');
            $baseLoan->monthly_deduction = $userLoans->sum('monthly_deduction');

            $activeLoan = $userLoans->first(function($l) { return $l->remaining_balance > 0; }) ?? $baseLoan;
            $baseLoan->installments = $activeLoan->installments;

            $hasActive = $userLoans->contains(function($l){ return $l->status === 'approved'; });
            $baseLoan->status = $hasActive ? 'approved' : 'closed';

            return $baseLoan;
        })
        ->values();

    return view('loan.admin-index', [
        'loans' => $loans,
        'pendingApplications' => Loan::applications()->where('status', 'pending')->count(),
    ]);
}

    /*
    |--------------------------------------------------------------------------
    | LOAN APPLICATIONS (ADMIN / ACCOUNTANT)
    |--------------------------------------------------------------------------
    */

    /**
     * Everything employees have applied for, waiting ones first.
     */
    public function applications(Request $request)
    {
        $status = $request->query('status');

        $applications = Loan::with('user')
            ->applications()
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true),
                fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('requested_at')
            ->get();

        return view('loan.applications', [
            'applications' => $applications,
            'status' => $status,
            'approverCount' => \App\Models\LoanApprover::active()->count(),
            'wahaEnabled' => app(WahaService::class)->enabled(),
        ]);
    }

    public function exportApplications(Request $request)
    {
        $status = $request->query('status');

        $status = in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)
            ? $status
            : null;

        return Excel::download(
            new LoanApplicationsExport($status),
            'loan-applications'.($status ? '-'.$status : '').'.xlsx'
        );
    }

    /** Send the approvers the WhatsApp again, for a request still waiting. */
    public function resendWhatsApp($id)
    {
        $loan = Loan::with('user')->findOrFail($id);

        if (!$loan->isPending()) {
            return back()->with('error', 'WhatsApp can only be resent for an application still waiting for a decision.');
        }

        $result = app(LoanWhatsAppNotifier::class)->notifyApprovers($loan);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function create(Request $request)
{
    $selectedUserId = $request->user_id;
    $employees = \App\Models\User::where('role','employee')->get();
    return view('loan.create',compact('employees', 'selectedUserId'));
}

    public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'amount' => 'required|numeric|min:0',
        'installments' => 'nullable|integer|min:0',
        'opening_balance' => 'nullable|numeric|min:0',
        'loan_date' => 'required|date',
        'monthly_deduction' => 'nullable|numeric|min:0',
    ]);

    // Opening balance (old loan from previous system)
    $opening = $request->opening_balance ?? 0;

    // New loan issued now
    $newLoan = $request->amount;

    // Total loan balance
    $totalLoan = $opening + $newLoan;

    // Monthly deduction
    $monthly = 0;
    if ($request->filled('monthly_deduction')) {
        $monthly = $request->monthly_deduction;
    } elseif ($request->installments && $request->installments > 0) {
        $monthly = $totalLoan / $request->installments;
    }

    $installments = $request->installments;
    if ($installments === '0' || $installments === 0 || empty($installments)) {
        $installments = null;
    }

    // Create loan record
    $loan = Loan::create([
        'user_id' => $request->user_id,
        'amount' => $newLoan,
        'opening_balance' => $opening,
        'installments' => $installments,
        'monthly_deduction' => $monthly,
        'remaining_balance' => $totalLoan,
        'status' => 'approved',
        'loan_date' => $request->loan_date
    ]);

    /*
    |--------------------------------------------------------------------------
    | Ledger Entries
    |--------------------------------------------------------------------------
    */

    // Opening loan balance (previous system)
    if ($opening > 0) {
        $ledger = LoanLedger::create([
            'loan_id' => $loan->id,
            'amount' => $opening,
            'type' => 'opening',
            'remarks' => 'Opening balance from previous records',
        ]);
        $ledger->timestamps = false;
        $ledger->created_at = $request->loan_date . ' 00:00:00';
        $ledger->updated_at = $request->loan_date . ' 00:00:00';
        $ledger->save();
    }

    // New loan issued
    if ($newLoan > 0) {
        $ledger = LoanLedger::create([
            'loan_id' => $loan->id,
            'amount' => $newLoan,
            'type' => 'loan',
            'remarks' => 'New loan issued',
        ]);
        $ledger->timestamps = false;
        $ledger->created_at = $request->loan_date . ' 00:00:00';
        $ledger->updated_at = $request->loan_date . ' 00:00:00';
        $ledger->save();
    }

    return redirect()->route('admin.loan.index')
        ->with('success','Loan created successfully');
}
    public function ledger($id)
{
    $loan = Loan::findOrFail($id);
    $user = $loan->user;

    // Auto-recalculate all loans of this user to guarantee data integrity
    $allLoans = Loan::where('user_id', $user->id)->get();
    foreach ($allLoans as $l) {
        $this->recalculateLoanBalance($l->id);
    }

    // Reload the updated loans
    $allLoans = Loan::where('user_id', $user->id)->latest()->get();
    $loanIds = $allLoans->pluck('id');

    // Reload primary loan
    $loan = Loan::with('user')->findOrFail($id);

    // Consolidated attributes
    $loan->amount = $allLoans->sum('amount');
    $loan->opening_balance = $allLoans->sum('opening_balance');
    $loan->remaining_balance = $allLoans->sum('remaining_balance');

    $hasActive = $allLoans->contains(function($l){ return in_array($l->status, ['approved', 'pending']); });
    $loan->status = $hasActive ? 'approved' : 'closed';

    // Merge and sort all ledger history
    $loan->ledgers = LoanLedger::whereIn('loan_id', $loanIds)
        ->orderBy('created_at', 'asc')
        ->get();

    return view('loan.ledger', compact('loan', 'allLoans'));
}

    public function approve(Request $request, $id)
    {
        $loan = Loan::with('user')->findOrFail($id);

        if (!$loan->isPending()) {
            return back()->with('error', 'This loan has already been '.$loan->status.'.');
        }

        $this->recordApproval(
            $loan,
            $request->input('amount'),
            $request->input('installments'),
            $request->input('decision_remarks'),
            auth()->user()->name.' ('.auth()->user()->email.')',
            'dashboard'
        );

        return back()->with('success', 'Loan '.$loan->reference().' approved.');
    }

    public function reject(Request $request, $id)
    {
        $loan = Loan::with('user')->findOrFail($id);

        if (!$loan->isPending()) {
            return back()->with('error', 'This loan has already been '.$loan->status.'.');
        }

        $this->recordRejection(
            $loan,
            $request->input('decision_remarks'),
            auth()->user()->name.' ('.auth()->user()->email.')',
            'dashboard'
        );

        return back()->with('success', 'Loan '.$loan->reference().' rejected.');
    }

    /**
     * Approving turns an application into a real loan.
     *
     * The approver may change the amount or the number of installments while
     * they are at it - a loan is often granted for less than was asked for.
     * The ledger entry is written here, which approval used to skip: without
     * it the next recalculation found nothing issued and wiped the balance
     * back to zero.
     */
    protected function recordApproval(
        Loan $loan,
        $amount,
        $installments,
        ?string $remarks,
        string $decidedBy,
        string $via
    ): void {
        $amount = is_numeric($amount) && (float) $amount > 0
            ? round((float) $amount, 2)
            : (float) $loan->amount;

        $installments = is_numeric($installments) && (int) $installments > 0
            ? (int) $installments
            : ($loan->installments ?: null);

        $monthly = $installments
            ? round($amount / $installments, 2)
            : 0;

        $loanDate = $loan->loan_date ?: today()->toDateString();

        $loan->update([
            'status' => 'approved',
            'amount' => $amount,
            'installments' => $installments,
            'monthly_deduction' => $monthly,
            'remaining_balance' => $amount,
            'loan_date' => $loanDate,
            'decided_by' => $decidedBy,
            'decided_via' => $via,
            'decided_at' => now(),
            'decision_remarks' => $remarks,
        ]);

        $ledger = LoanLedger::updateOrCreate(
            ['loan_id' => $loan->id, 'type' => 'loan'],
            [
                'amount' => $amount,
                'remarks' => 'Loan application '.$loan->reference().' approved',
            ]
        );

        $ledger->timestamps = false;
        $ledger->created_at = $loanDate.' 00:00:00';
        $ledger->updated_at = $loanDate.' 00:00:00';
        $ledger->save();

        $this->notifyEmployee($loan, 'approved');
    }

    protected function recordRejection(
        Loan $loan,
        ?string $remarks,
        string $decidedBy,
        string $via
    ): void {
        $loan->update([
            'status' => 'rejected',
            'remaining_balance' => 0,
            'decided_by' => $decidedBy,
            'decided_via' => $via,
            'decided_at' => now(),
            'decision_remarks' => $remarks,
        ]);

        $this->notifyEmployee($loan, 'rejected');
    }

    /**
     * Let the employee know on WhatsApp, if there is a number on file.
     *
     * Best effort: the decision stands whether or not the message goes out.
     */
    protected function notifyEmployee(Loan $loan, string $outcome): void
    {
        $mobile = $loan->user->mobile ?? null;

        if (!filled($mobile)) {
            return;
        }

        $lines = [
            'Loan Application '.ucfirst($outcome),
            '',
            'Reference: '.$loan->reference(),
            'Amount: Rs '.number_format((float) $loan->amount, 2),
        ];

        if ($outcome === 'approved') {
            $lines[] = 'Installments: '.($loan->installments ?: '-');
            $lines[] = 'Monthly deduction: Rs '.number_format((float) $loan->monthly_deduction, 2);
        }

        if (filled($loan->decision_remarks)) {
            $lines[] = 'Remarks: '.$loan->decision_remarks;
        }

        $lines[] = '';
        $lines[] = '- LSAF HR';

        app(WahaService::class)->sendToMobile($mobile, implode("\n", $lines));
    }

    /*
    |--------------------------------------------------------------------------
    | DECIDING FROM THE WHATSAPP LINK (no login)
    |--------------------------------------------------------------------------
    | The link in the message opens a confirmation page; nothing is decided
    | until the Confirm button is pressed. A link preview crawler only ever
    | performs the GET, so it cannot approve a loan by fetching the URL.
    */

    public function whatsappDecision(Request $request, $id, $decision)
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 404);

        $loan = Loan::with('user')->findOrFail($id);

        $confirmUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'loan.whatsapp.decision.submit',
            now()->addHours(72),
            [
                'id' => $loan->id,
                'decision' => $decision,
                'mobile' => $request->query('mobile'),
            ]
        );

        return view('loan.whatsapp-decision-confirm', [
            'loan' => $loan,
            'decision' => $decision,
            'confirmUrl' => $confirmUrl,
        ]);
    }

    public function whatsappDecisionSubmit(Request $request, $id, $decision)
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 404);

        $loan = Loan::with('user')->findOrFail($id);

        if (!$loan->isPending()) {
            return view('loan.whatsapp-decision-result', [
                'loan' => $loan,
                'status' => 'already',
            ]);
        }

        $mobile = $request->query('mobile');

        $approver = \App\Models\LoanApprover::where('mobile', $mobile)->first();

        $decidedBy = trim(($approver->name ?? '').' '.($mobile ? '('.$mobile.')' : '')) ?: 'WhatsApp approver';

        if ($decision === 'approve') {
            $this->recordApproval($loan, null, null, null, $decidedBy, 'whatsapp');
        } else {
            $this->recordRejection($loan, null, $decidedBy, 'whatsapp');
        }

        return view('loan.whatsapp-decision-result', [
            'loan' => $loan->fresh('user'),
            'status' => $decision === 'approve' ? 'approved' : 'rejected',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT / EXPORT
    |--------------------------------------------------------------------------
    */

    public function export()
    {
        return Excel::download(new LoansExport, 'loans.xlsx');
    }

    public function importForm()
    {
        return view('loan.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        Excel::import(new LoansImport, $request->file('file'));

        return back()->with('success', 'Loans Imported Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT / UPDATE / DELETE
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $loan = Loan::findOrFail($id);
        return view('loan.edit', compact('loan'));
    }

    public function update(Request $request, $id)
{
    $loan = Loan::findOrFail($id);

    $request->validate([
        'opening_balance' => 'nullable|numeric|min:0',
        'amount' => 'required|numeric|min:0',
        'installments' => 'nullable|integer|min:0',
        'loan_date' => 'required|date',
        'monthly_deduction' => 'nullable|numeric|min:0',
    ]);

    $opening = $request->opening_balance ?? 0;
    $amount = $request->amount;

    $totalLoan = $opening + $amount;

    // Monthly deduction
    $monthly = 0;
    if ($request->filled('monthly_deduction')) {
        $monthly = $request->monthly_deduction;
    } elseif ($request->installments && $request->installments > 0) {
        $monthly = round($totalLoan / $request->installments, 2);
    }

    $installments = $request->installments;
    if ($installments === '0' || $installments === 0 || empty($installments)) {
        $installments = null;
    }

    // Calculate deductions already paid to avoid overwriting them
    $paid = \App\Models\LoanLedger::where('loan_id', $loan->id)
        ->where('type', 'deduction')
        ->sum('amount');

    $remaining = max(0, $totalLoan - $paid);

    $status = $loan->status;
    if ($status === 'approved' && $remaining <= 0) {
        $status = 'closed';
    } elseif ($status === 'closed' && $remaining > 0) {
        $status = 'approved';
    }

    $loan->update([
        'opening_balance' => $opening,
        'amount' => $amount,
        'installments' => $installments,
        'monthly_deduction' => $monthly,
        'remaining_balance' => $remaining,
        'status' => $status,
        'loan_date' => $request->loan_date,
    ]);

    // Sync ledger entries
    if ($opening > 0) {
        $ledger = \App\Models\LoanLedger::updateOrCreate(
            ['loan_id' => $loan->id, 'type' => 'opening'],
            [
                'amount' => $opening,
                'remarks' => 'Opening balance from previous records',
            ]
        );
        $ledger->timestamps = false;
        $ledger->created_at = $request->loan_date . ' 00:00:00';
        $ledger->updated_at = $request->loan_date . ' 00:00:00';
        $ledger->save();
    } else {
        \App\Models\LoanLedger::where('loan_id', $loan->id)->where('type', 'opening')->delete();
    }

    if ($amount > 0) {
        $ledger = \App\Models\LoanLedger::updateOrCreate(
            ['loan_id' => $loan->id, 'type' => 'loan'],
            [
                'amount' => $amount,
                'remarks' => 'New loan issued',
            ]
        );
        $ledger->timestamps = false;
        $ledger->created_at = $request->loan_date . ' 00:00:00';
        $ledger->updated_at = $request->loan_date . ' 00:00:00';
        $ledger->save();
    } else {
        \App\Models\LoanLedger::where('loan_id', $loan->id)->where('type', 'loan')->delete();
    }

    return redirect()->route('admin.loan.index')
        ->with('success','Loan updated successfully');
}
    public function destroy($id)
    {
        Loan::findOrFail($id)->delete();

        return redirect()->route('admin.loan.index')
            ->with('success', 'Loan Deleted Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO DEDUCTION WHEN SALARY POSTED
    |--------------------------------------------------------------------------
    */

    public function deductLoan($loanId, $month, $year)
    {
        $loan = Loan::findOrFail($loanId);

        if ($loan->status !== 'approved' || $loan->remaining_balance <= 0) {
            return;
        }

        $deduction = min(
            $loan->monthly_deduction,
            $loan->remaining_balance
        );

        $newBalance = $loan->remaining_balance - $deduction;

        LoanPayment::create([
            'loan_id' => $loan->id,
            'amount_paid' => $deduction,
            'remaining_balance' => $newBalance,
            'month' => $month,
            'year' => $year
        ]);

        $loan->update([
            'remaining_balance' => $newBalance,
            'status' => $newBalance <= 0 ? 'completed' : 'approved'
        ]);
    }

    public function storeLedgerEntry(Request $request, $loanId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:deduction,adjustment,opening,loan',
            'remarks' => 'nullable|string|max:255',
            'created_at' => 'required|date',
        ]);

        $ledger = LoanLedger::create([
            'loan_id' => $loanId,
            'amount' => $request->amount,
            'type' => $request->type,
            'remarks' => $request->remarks,
        ]);

        $ledger->timestamps = false;
        $ledger->created_at = $request->created_at . ' 00:00:00';
        $ledger->updated_at = $request->created_at . ' 00:00:00';
        $ledger->save();

        $this->recalculateLoanBalance($loanId);

        return back()->with('success', 'Ledger entry added successfully');
    }

    public function editLedgerEntry($id)
    {
        $entry = LoanLedger::findOrFail($id);
        return view('loan.edit-ledger-entry', compact('entry'));
    }

    public function updateLedgerEntry(Request $request, $id)
    {
        $entry = LoanLedger::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:deduction,adjustment,opening,loan',
            'remarks' => 'nullable|string|max:255',
            'created_at' => 'required|date',
        ]);

        $entry->update([
            'amount' => $request->amount,
            'type' => $request->type,
            'remarks' => $request->remarks,
        ]);

        $entry->timestamps = false;
        $entry->created_at = $request->created_at . ' 00:00:00';
        $entry->updated_at = $request->created_at . ' 00:00:00';
        $entry->save();

        $this->recalculateLoanBalance($entry->loan_id);

        return redirect()->route('admin.loan.ledger', $entry->loan_id)
            ->with('success', 'Ledger entry updated successfully');
    }

    public function destroyLedgerEntry($id)
    {
        $entry = LoanLedger::findOrFail($id);
        $loanId = $entry->loan_id;

        $entry->delete();

        $this->recalculateLoanBalance($loanId);

        return back()->with('success', 'Ledger entry deleted successfully');
    }

    private function recalculateLoanBalance($loanId)
    {
        $loan = Loan::findOrFail($loanId);

        $totalIssued = LoanLedger::where('loan_id', $loan->id)
            ->whereIn('type', ['opening', 'loan'])
            ->sum('amount');

        $totalPaid = LoanLedger::where('loan_id', $loan->id)
            ->whereIn('type', ['deduction', 'adjustment'])
            ->sum('amount');

        $remaining = max(0, $totalIssued - $totalPaid);

        $status = $loan->status;
        if ($remaining <= 0) {
            $status = 'closed';
        } else {
            $status = 'approved';
        }

        $openingSum = LoanLedger::where('loan_id', $loan->id)
            ->where('type', 'opening')
            ->sum('amount');

        $loanSum = LoanLedger::where('loan_id', $loan->id)
            ->where('type', 'loan')
            ->sum('amount');

        $loan->update([
            'opening_balance' => $openingSum,
            'amount' => $loanSum,
            'remaining_balance' => $remaining,
            'status' => $status
        ]);
    }
}
