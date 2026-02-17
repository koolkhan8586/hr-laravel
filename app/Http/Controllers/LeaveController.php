<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use App\Models\LeaveTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeaveTransactionsExport;

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

        return view('leave.index', compact('leaves'));
    }

    public function create()
{
    // If admin → show all employees
    if(auth()->user()->role === 'admin'){
        $employees = \App\Models\User::where('role','employee')->get();
        return view('leave.create', compact('employees'));
    }

    // If employee → only himself
    $employees = collect([auth()->user()]);
    return view('leave.create', compact('employees'));
}


    public function store(Request $request)
    {
        $request->validate([
            'type'          => 'required|in:annual,without_pay',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'duration_type' => 'required|in:full_day,half_day',
            'reason'        => 'nullable|string'
        ]);

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        $totalDays = $start->diffInDays($end) + 1;

        $calculatedDays = $request->duration_type === 'half_day'
            ? 0.5
            : $totalDays;

        Leave::create([
            'user_id'        => auth()->id(),
            'type'           => $request->type,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'duration'       => $request->duration_type === 'half_day' ? 'half' : 'full',
            'duration_type'  => $request->duration_type,
            'half_day_type'  => $request->half_day_type ?? null,
            'days'           => $calculatedDays,
            'calculated_days'=> $calculatedDays,
            'reason'         => $request->reason,
            'status'         => 'pending',
        ]);

        return back()->with('success','Leave Request Submitted Successfully');
    }


/*
|--------------------------------------------------------------------------
| ADMIN SECTION
|--------------------------------------------------------------------------
*/

    public function adminIndex()
    {
        $leaves = Leave::with('user')->latest()->get();
        return view('leave.admin', compact('leaves'));
    }

    public function adminCreate()
    {
        $employees = User::where('role','employee')->get();
        return view('leave.admin-create', compact('employees'));
    }

    public function adminStore(Request $request)
    {
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'type'          => 'required|in:annual,without_pay',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'duration_type' => 'required|in:full_day,half_day',
            'status'        => 'required|in:pending,approved,rejected',
            'reason'        => 'nullable|string'
        ]);

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        $totalDays = $start->diffInDays($end) + 1;

        $calculatedDays = $request->duration_type === 'half_day'
            ? 0.5
            : $totalDays;

        $leave = Leave::create([
            'user_id'        => $request->user_id,
            'type'           => $request->type,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'duration'       => $request->duration_type === 'half_day' ? 'half' : 'full',
            'duration_type'  => $request->duration_type,
            'half_day_type'  => $request->half_day_type ?? null,
            'days'           => $calculatedDays,
            'calculated_days'=> $calculatedDays,
            'status'         => $request->status,
            'reason'         => $request->reason
        ]);

        if ($request->status === 'approved') {
            $this->processApproval($leave);
        }

        return redirect()->route('admin.leave.index')
            ->with('success','Leave Added Successfully');
    }

    public function adminEdit($id)
    {
        $leave = Leave::findOrFail($id);
        $employees = User::where('role','employee')->get();

        return view('leave.admin-edit', compact('leave','employees'));
    }

    public function adminUpdate(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'type'          => 'required|in:annual,without_pay',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'duration_type' => 'required|in:full_day,half_day',
            'status'        => 'required|in:pending,approved,rejected'
        ]);

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        $totalDays = $start->diffInDays($end) + 1;

        $calculatedDays = $request->duration_type === 'half_day'
            ? 0.5
            : $totalDays;

        $leave->update([
            'user_id'        => $request->user_id,
            'type'           => $request->type,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'duration'       => $request->duration_type === 'half_day' ? 'half' : 'full',
            'duration_type'  => $request->duration_type,
            'half_day_type'  => $request->half_day_type ?? null,
            'days'           => $calculatedDays,
            'calculated_days'=> $calculatedDays,
            'status'         => $request->status
        ]);

        return redirect()->route('admin.leave.index')
            ->with('success','Leave Updated Successfully');
    }


/*
|--------------------------------------------------------------------------
| APPROVE / REJECT / REVERT
|--------------------------------------------------------------------------
*/

    public function approve($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved') {
            return back()->with('error','Already approved');
        }

        $this->processApproval($leave);

        $leave->update(['status'=>'approved']);

        return back()->with('success','Leave Approved');
    }

    private function processApproval($leave)
    {
        if ($leave->type === 'annual') {

            $user = $leave->user;
            $before = $user->annual_leave_balance;

            if ($before < $leave->calculated_days) {
                abort(403,'Insufficient Leave Balance');
            }

            $after = $before - $leave->calculated_days;

            $user->update(['annual_leave_balance'=>$after]);

            LeaveTransaction::create([
                'user_id'        => $user->id,
                'leave_id'       => $leave->id,
                'days'           => $leave->calculated_days,
                'balance_before' => $before,
                'balance_after'  => $after,
                'action'         => 'approved',
                'processed_by'   => auth()->id(),
            ]);
        }
    }

    public function reject($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->update(['status'=>'rejected']);

        return back()->with('success','Leave Rejected');
    }

    public function revert($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved' && $leave->type === 'annual') {
            $leave->user->increment(
                'annual_leave_balance',
                $leave->calculated_days
            );
        }

        $leave->update(['status'=>'pending']);

        return back()->with('success','Leave Reverted');
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved' && $leave->type === 'annual') {
            $leave->user->increment(
                'annual_leave_balance',
                $leave->calculated_days
            );
        }

        $leave->delete();

        return back()->with('success','Leave Deleted Successfully');
    }


/*
|--------------------------------------------------------------------------
| ADMIN TRANSACTIONS + EXPORT
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
| PAYROLL SUMMARY
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
}
