<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use App\Models\LeaveTransaction;
use App\Models\LeaveBalance;
use App\Models\LeaveApprovalEmail;
use App\Mail\LeaveApplicationSubmitted;
use App\Services\LeaveWhatsAppNotifier;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeaveTransactionsExport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class LeaveController extends Controller
{

/*
|--------------------------------------------------------------------------
| EMPLOYEE SECTION
|--------------------------------------------------------------------------
*/

    public function index()
    {
        $leaves = Leave::where('user_id', auth()->id())
            ->latest()
            ->get();

        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => auth()->id()],
            [
                'opening_balance' => 0,
                'used_leaves' => 0,
                'remaining_leaves' => 0
            ]
        );

        return view('leave.index', compact('leaves','balance'));
    }

    public function create()
    {
        if(auth()->user()->role === 'admin'){
            $employees = User::where('role', 'employee')
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $employees = collect([auth()->user()]);
        }

        return view('leave.create', compact('employees'));
    }

    public function history()
    {
        $leaves = Leave::where('user_id', auth()->id())
            ->latest()
            ->get();

        $transactions = LeaveTransaction::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('leave.history', compact('leaves','transactions'));
    }

/*
|--------------------------------------------------------------------------
| STORE LEAVE (ADMIN + EMPLOYEE)
|--------------------------------------------------------------------------
*/

public function store(Request $request)
{
    $request->validate([
        'employee_id' => auth()->user()->role === 'admin'
            ? 'required|exists:users,id'
            : 'nullable',
        'type'          => 'required|in:annual,without_pay',
        'start_date'    => 'required|date',
        'end_date'      => 'required|date|after_or_equal:start_date',
        'duration_type' => 'required|in:full_day,half_day',
        'half_day_type' => 'nullable|required_if:duration_type,half_day|in:morning,afternoon',
        'reason'        => 'nullable|string'
    ], [
        'half_day_type.required_if' => 'Please select whether the half day is morning or afternoon.',
    ]);

    $userId = auth()->user()->role === 'admin'
        ? $request->employee_id
        : auth()->id();

    $start = Carbon::parse($request->start_date);
    $end   = Carbon::parse($request->end_date);

    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE / OVERLAPPING LEAVES  ✅ NEW
    |--------------------------------------------------------------------------
    */

    $exists = Leave::where('user_id', $userId)
        ->where(function($q) use ($request) {

            $q->whereBetween('start_date', [$request->start_date, $request->end_date])
              ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
              ->orWhere(function($q2) use ($request){
                  $q2->where('start_date','<=',$request->start_date)
                     ->where('end_date','>=',$request->end_date);
              });

        })
        ->whereIn('status', ['pending','approved']) // only active leaves
        ->exists();

    if($exists){
        return back()->with('error','Leave already applied for selected dates');
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE DAYS (UNCHANGED)
    |--------------------------------------------------------------------------
    */

    $days = $request->duration_type === 'half_day'
        ? 0.5
        : $start->diffInDays($end) + 1;

    $leave = Leave::create([
        'user_id'        => $userId,
        'type'           => $request->type,
        'start_date'     => $request->start_date,
        'end_date'       => $request->end_date,
        'duration_type'  => $request->duration_type,
        'half_day_type'  => $request->duration_type === 'half_day'
            ? $request->half_day_type
            : null,
        'days'           => $days,
        'calculated_days'=> $days,
        'reason'         => $request->reason,
        'status'         => auth()->user()->role === 'admin' ? 'approved' : 'pending',
        'decided_via'    => auth()->user()->role === 'admin' ? 'dashboard' : null,
        'decided_by_email' => auth()->user()->role === 'admin' ? auth()->user()->email : null,
        'decided_at'     => auth()->user()->role === 'admin' ? now() : null,
    ]);

    /*
    |--------------------------------------------------------------------------
    | EMAIL LOGIC (UNCHANGED)
    |--------------------------------------------------------------------------
    */

    if(auth()->user()->role === 'employee'){
        $adminEmails = User::where('role','admin')->pluck('email');
        $approvalEmails = LeaveApprovalEmail::pluck('email');

        $recipients = $adminEmails->merge($approvalEmails)
            ->filter()
            ->unique()
            ->values();

        $mailFailed = 0;
        foreach($recipients as $recipientEmail){
            try {
                Mail::to($recipientEmail)->send(new LeaveApplicationSubmitted($leave, $recipientEmail));
            } catch (\Throwable $e) {
                $mailFailed++;
                Log::error('Leave approval email failed', [
                    'leave_id' => $leave->id,
                    'email' => $recipientEmail,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // WhatsApp must run even if email SMTP fails
        $waResult = ['ok' => false, 'message' => 'WhatsApp not attempted'];
        try {
            $waResult = app(LeaveWhatsAppNotifier::class)->notifyApprovers($leave);
        } catch (\Throwable $e) {
            Log::error('Leave approval WhatsApp failed', [
                'leave_id' => $leave->id,
                'message' => $e->getMessage(),
            ]);
            $waResult = ['ok' => false, 'message' => $e->getMessage()];
        }

        $hints = [];
        if ($mailFailed > 0) {
            $hints[] = "Email failed for {$mailFailed} recipient(s)";
        }
        if (!$waResult['ok']) {
            $hints[] = $waResult['message'] ?? 'WhatsApp notification failed';
        }

        if (!empty($hints)) {
            return back()->with('success', 'Leave Created Successfully')
                ->with('error', implode(' | ', $hints).'. You can Resend WhatsApp from Leave Management.');
        }
    }

    if(auth()->user()->role === 'admin'){
        $approved = $this->processApproval($leave);

        if (!$approved) {
            $leave->delete();
            return back()->with('error','Insufficient leave balance.');
        }

        Mail::raw(
            "Leave Created By Admin\n\nFrom: ".$request->start_date.
            "\nTo: ".$request->end_date.
            "\nDays: ".$days,
            function ($message) use ($leave) {
                $message->to($leave->user->email)
                    ->subject('Leave Created By Admin');
            }
        );
    }

    return back()->with('success','Leave Created Successfully');
}

/*
|--------------------------------------------------------------------------
| ADMIN INDEX
|--------------------------------------------------------------------------
*/

    public function adminIndex(Request $request)
    {
        $query = Leave::with('user')->latest();

        if ($request->employee) {
            $query->where('user_id', $request->employee);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->month) {
            $month = Carbon::parse($request->month);
            $query->whereMonth('start_date',$month->month)
                  ->whereYear('start_date',$month->year);
        }

        $leaves = $query->paginate(50)->withQueryString();
        $employees = User::where('role','employee')->orderBy('name')->get(['id', 'name']);

        return view('leave.admin', compact('leaves','employees'));
    }

/*
|--------------------------------------------------------------------------
| APPROVE
|--------------------------------------------------------------------------
*/

    public function approve($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved') {
            return redirect()
                ->route('admin.leave.index')
                ->with('error', 'Already Approved');
        }

        $approved = $this->processApproval($leave);

        if (!$approved) {
            return redirect()
                ->route('admin.leave.index')
                ->with('error', 'Insufficient leave balance.');
        }

        $leave->update([
            'status' => 'approved',
            'decided_via' => 'dashboard',
            'decided_by_email' => auth()->user()->email,
            'decided_at' => now(),
        ]);

        $this->notifyLeaveOwner($leave, 'approved');

        return redirect()
            ->route('admin.leave.index')
            ->with('success', 'Leave Approved');
    }

/*
|--------------------------------------------------------------------------
| APPROVE / REJECT VIA EMAIL / WHATSAPP LINK (CONFIRMATION REQUIRED)
|--------------------------------------------------------------------------
| GET only shows a confirmation page. WhatsApp/email link-preview bots
| fetch GET URLs and must NOT be able to approve/reject leave.
| Actual decision happens on POST (emailDecisionSubmit).
*/

    public function emailDecision(Request $request, $id, $decision)
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 404);

        $leave = Leave::with('user')->findOrFail($id);
        $approverEmail = $request->query('email');
        $via = $request->query('via') === 'whatsapp' ? 'whatsapp' : 'email';

        $confirmUrl = URL::temporarySignedRoute(
            'leave.email.decision.submit',
            now()->addHours(72),
            [
                'id' => $leave->id,
                'decision' => $decision,
                'email' => $approverEmail,
                'via' => $via,
            ]
        );

        return view('leave.email-decision-confirm', [
            'leave' => $leave,
            'decision' => $decision,
            'confirmUrl' => $confirmUrl,
        ]);
    }

    public function emailDecisionSubmit(Request $request, $id, $decision)
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 404);

        $leave = Leave::with('user')->findOrFail($id);
        $approverEmail = $request->query('email');
        $decidedVia = $request->query('via') === 'whatsapp' ? 'whatsapp' : 'email';

        if ($leave->status !== 'pending') {
            return view('leave.email-decision-result', [
                'status' => 'already',
                'leave' => $leave,
            ]);
        }

        if ($decision === 'approve') {
            $approved = $this->processApproval($leave);

            if (!$approved) {
                return view('leave.email-decision-result', [
                    'status' => 'insufficient',
                    'leave' => $leave,
                ]);
            }

            $leave->update([
                'status' => 'approved',
                'decided_via' => $decidedVia,
                'decided_by_email' => $approverEmail,
                'decided_at' => now(),
            ]);

            $this->notifyLeaveOwner($leave, 'approved');

            return view('leave.email-decision-result', [
                'status' => 'approved',
                'leave' => $leave,
            ]);
        }

        $leave->update([
            'status' => 'rejected',
            'decided_via' => $decidedVia,
            'decided_by_email' => $approverEmail,
            'decided_at' => now(),
        ]);

        $this->notifyLeaveOwner($leave, 'rejected');

        return view('leave.email-decision-result', [
            'status' => 'rejected',
            'leave' => $leave,
        ]);
    }

/*
|--------------------------------------------------------------------------
| NOTIFY LEAVE OWNER OF DECISION
|--------------------------------------------------------------------------
*/

    private function notifyLeaveOwner($leave, string $status)
    {
        $subject = $status === 'approved' ? 'Leave Approved' : 'Leave Rejected';

        $body = $status === 'approved'
            ? "Your Leave Has Been Approved\n\nFrom: ".$leave->start_date.
              "\nTo: ".$leave->end_date.
              "\nDays: ".$leave->calculated_days
            : "Your Leave Has Been Rejected\n\nFrom: ".$leave->start_date.
              "\nTo: ".$leave->end_date;

        Mail::raw($body, function ($message) use ($leave, $subject) {
            $message->to($leave->user->email)
                ->subject($subject);
        });
    }

/*
|--------------------------------------------------------------------------
| PROCESS APPROVAL (FIXED SAFE VERSION)
|--------------------------------------------------------------------------
*/

    private function processApproval($leave)
    {
        if ($leave->type !== 'annual') return true;

        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $leave->user_id],
            [
                'opening_balance' => 0,
                'used_leaves' => 0,
                'remaining_leaves' => 0
            ]
        );

        if ($balance->remaining_leaves < $leave->calculated_days) {
            return false;
        }

        $before = $balance->remaining_leaves;
        $after  = $before - $leave->calculated_days;

        $balance->update([
            'used_leaves' => $balance->used_leaves + $leave->calculated_days,
            'remaining_leaves' => $after
        ]);

        LeaveTransaction::create([
            'user_id'        => $leave->user_id,
            'leave_id'       => $leave->id,
            'days'           => $leave->calculated_days,
            'balance_before' => $before,
            'balance_after'  => $after,
            'action'         => 'approved',
            'processed_by'   => auth()->id(),
        ]);

        return true;
    }

/*
|--------------------------------------------------------------------------
| RESEND WHATSAPP (when WAHA was disconnected at apply time)
|--------------------------------------------------------------------------
*/

    public function resendWhatsApp($id)
    {
        $leave = Leave::with('user')->findOrFail($id);

        if ($leave->status !== 'pending') {
            return back()->with('error', 'WhatsApp can only be resent for pending leave applications.');
        }

        $result = app(LeaveWhatsAppNotifier::class)->notifyApprovers($leave);

        if ($result['ok']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

/*
|--------------------------------------------------------------------------
| REJECT
|--------------------------------------------------------------------------
*/

    public function reject($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'rejected') {
            return redirect()
                ->route('admin.leave.index')
                ->with('error', 'Already Rejected');
        }

        if ($leave->status === 'approved') {
            return redirect()
                ->route('admin.leave.index')
                ->with('error', 'Approved leave cannot be rejected. Revert it first.');
        }

        $leave->update([
            'status' => 'rejected',
            'decided_via' => 'dashboard',
            'decided_by_email' => auth()->user()->email,
            'decided_at' => now(),
        ]);

        $this->notifyLeaveOwner($leave, 'rejected');

        return redirect()
            ->route('admin.leave.index')
            ->with('success', 'Leave Rejected');
    }

/*
|--------------------------------------------------------------------------
| REVERT
|--------------------------------------------------------------------------
*/

    public function revert($id)
    {
        $leave = Leave::findOrFail($id);

        if($leave->status === 'approved' && $leave->type === 'annual'){

            $balance = LeaveBalance::where('user_id',$leave->user_id)->first();

            if($balance){
                $balance->update([
                    'used_leaves' => $balance->used_leaves - $leave->calculated_days,
                    'remaining_leaves' => $balance->remaining_leaves + $leave->calculated_days
                ]);
            }
        }

        $leave->update([
            'status' => 'pending',
            'decided_via' => null,
            'decided_by_email' => null,
            'decided_at' => null,
        ]);

        return back()->with('success','Leave Reverted');
    }

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
public function destroy($id)
{
    $leave = Leave::findOrFail($id);

    if ($leave->status === 'approved' && $leave->type === 'annual') {

        $balance = LeaveBalance::where('user_id', $leave->user_id)->first();

        if ($balance) {

            // 🔥 Always recalculate from source of truth
            $approved = Leave::where('user_id', $leave->user_id)
                ->where('type', 'annual')
                ->where('status', 'approved')
                ->sum('calculated_days');

            $balance->used_leaves = $approved;
            $balance->remaining_leaves = $balance->opening_balance - $approved;
            $balance->save();
        }

        LeaveTransaction::where('leave_id', $leave->id)->delete();
    }

    $leave->delete();

    return back()->with('success', 'Leave deleted and balance restored successfully.');
}
/*
|--------------------------------------------------------------------------
| LEAVE ALLOCATION
|--------------------------------------------------------------------------
*/

    public function allocationIndex()
    {
        $employees = User::where('role','employee')
                ->orderBy('name', 'asc')
                ->get();
        return view('leave.balance-index', compact('employees'));
    }


    public function updateAllocation(Request $request, $id)
    {
        $request->validate([
            'annual_leave_balance' => 'required|numeric|min:0'
        ]);

        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $id],
            [
                'opening_balance' => 0,
                'used_leaves' => 0,
                'remaining_leaves' => 0
            ]
        );

        $newOpening = $request->annual_leave_balance;

        $balance->update([
            'opening_balance' => $newOpening,
            'remaining_leaves' => $newOpening - $balance->used_leaves
        ]);

        return back()->with('success','Leave allocation updated successfully.');
    }


    public function calendar()
    {
        $leaves = Leave::with('user')
            ->where('status','approved')
            ->get();

        $events = [];

        foreach ($leaves as $leave) {
            $events[] = [
                'title' => $leave->user->name.' ('.$leave->calculated_days.' day)',
                'start' => $leave->start_date,
                'end'   => Carbon::parse($leave->end_date)->addDay()->format('Y-m-d'),
                'color' => '#16a34a'
            ];
        }

        return view('leave.calendar', compact('events'));
    }

/*
|--------------------------------------------------------------------------
| RECALCULATE + RESET
|--------------------------------------------------------------------------
*/

    public function recalculateBalances()
    {
        $employees = User::where('role','employee')->get();

        foreach ($employees as $employee) {
            $this->recalculateUserLeaveBalance($employee->id);
        }

        return back()->with('success','All Leave Balances Recalculated Successfully');
    }

    public function bulkAllocate(Request $request)
{
    $request->validate([
        'bulk_balance' => 'required|numeric|min:0'
    ]);

    $employees = User::where('role','employee')->get();

    foreach ($employees as $employee) {

        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $employee->id],
            [
                'opening_balance'  => 0,
                'used_leaves'      => 0,
                'remaining_leaves' => 0
            ]
        );

        $balance->update([
            'opening_balance'  => $request->bulk_balance,
            'remaining_leaves' => $request->bulk_balance - $balance->used_leaves
        ]);
    }

    return back()->with('success','Bulk Leave Allocation Applied Successfully');
}
    public function resetYearlyBalance()
    {
        $balances = LeaveBalance::all();

        foreach ($balances as $balance) {
            $balance->update([
                'used_leaves'=>0,
                'remaining_leaves'=>$balance->opening_balance
            ]);
        }

        return back()->with('success','Yearly Leave Reset Successfully');
    }

/*
|--------------------------------------------------------------------------
| TRANSACTIONS
|--------------------------------------------------------------------------
*/

    public function adminTransactions()
    {
        $transactions = LeaveTransaction::with('user','leave')
            ->latest()
            ->get();

        return view('leave.admin_transactions', compact('transactions'));
    }

    public function exportTransactions()
    {
        return Excel::download(
            new LeaveTransactionsExport,
            'leave_transactions.xlsx'
        );
    }

/*
|--------------------------------------------------------------------------
| PAYROLL
|--------------------------------------------------------------------------
*/

    public function payrollSummary(Request $request)
    {
        $year = $request->year ?? now()->year;

        $annualUsed = Leave::where('type','annual')
            ->where('status','approved')
            ->whereYear('start_date',$year)
            ->sum('calculated_days');

        $withoutPay = Leave::where('type','without_pay')
            ->where('status','approved')
            ->whereYear('start_date',$year)
            ->sum('calculated_days');

        return view('leave.payroll-summary', compact(
            'annualUsed',
            'withoutPay',
            'year'
        ));
    }


/*
|--------------------------------------------------------------------------
| ADMIN EDIT + UPDATE (NEW - DO NOT REMOVE ANYTHING)
|--------------------------------------------------------------------------
*/

public function adminEdit($id)
{
    $leave = Leave::with('user')->findOrFail($id);

    $employees = User::where('role', 'employee')
        ->orderBy('name', 'asc')
        ->get();

    return view('leave.admin-edit', compact('leave', 'employees'));
}

public function adminUpdate(Request $request, $id)
{
    $request->validate([
        'type'          => 'required|in:annual,without_pay',
        'start_date'    => 'required|date',
        'end_date'      => 'required|date|after_or_equal:start_date',
        'duration_type' => 'required|in:full_day,half_day',
        'half_day_type' => 'nullable|required_if:duration_type,half_day|in:morning,afternoon',
        'reason'        => 'nullable|string'
    ], [
        'half_day_type.required_if' => 'Please select whether the half day is morning or afternoon.',
    ]);

    $leave = Leave::findOrFail($id);

    $start = Carbon::parse($request->start_date);
    $end   = Carbon::parse($request->end_date);

    $days = $request->duration_type === 'half_day'
        ? 0.5
        : $start->diffInDays($end) + 1;

    $leave->update([
        'type'            => $request->type,
        'start_date'      => $request->start_date,
        'end_date'        => $request->end_date,
        'duration_type'   => $request->duration_type,
        'half_day_type'   => $request->duration_type === 'half_day'
            ? $request->half_day_type
            : null,
        'days'            => $days,
        'calculated_days' => $days,
        'reason'          => $request->reason,
    ]);

    // Recalculate Leave Balance for this specific user
    $this->recalculateUserLeaveBalance($leave->user_id);

    return redirect()->back()->with('success', 'Leave updated successfully');
  }

  /*
  |--------------------------------------------------------------------------
  | Helper to recalculate a user's leave balance
  |--------------------------------------------------------------------------
  */
  private function recalculateUserLeaveBalance($userId)
  {
      $approved = Leave::where('user_id', $userId)
          ->where('type', 'annual')
          ->where('status', 'approved')
          ->sum('calculated_days');

      $balance = LeaveBalance::firstOrCreate(
          ['user_id' => $userId],
          ['opening_balance' => 0, 'used_leaves' => 0, 'remaining_leaves' => 0]
      );

      $balance->update([
          'used_leaves' => $approved,
          'remaining_leaves' => $balance->opening_balance - $approved
      ]);
  }
}
