<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use App\Models\LeaveTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeaveTransactionsExport;

class LeaveController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Employee Leave List
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $leaves = Leave::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('leave.index', compact('leaves'));
    }

    /*
    |--------------------------------------------------------------------------
    | Leave Apply Form
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('leave.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Store Leave
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
{
    $request->validate([
        'user_id'   => 'required',
        'type'      => 'required',
        'from_date' => 'required|date',
        'to_date'   => 'required|date|after_or_equal:from_date',
        'day_type'  => 'required',
        'status'    => 'required'
    ]);

    // Calculate days difference
    $from = \Carbon\Carbon::parse($request->from_date);
    $to   = \Carbon\Carbon::parse($request->to_date);

    $days = $from->diffInDays($to) + 1;

    if ($request->day_type == 'half') {
        $days = 0.5;
    }

    Leave::create([
        'user_id'   => $request->user_id,
        'type'      => $request->type,
        'from_date' => $request->from_date,
        'to_date'   => $request->to_date,
        'days'      => $days,
        'day_type'  => $request->day_type,
        'status'    => $request->status
    ]);

    return redirect()->route('admin.leave.index')
        ->with('success','Leave Added Successfully');
}


    /*
|--------------------------------------------------------------------------
| Leave History (Employee)
|--------------------------------------------------------------------------
*/
public function history()
{
    $leaves = auth()->user()
                    ->leaves()
                    ->latest()
                    ->get();

    $transactions = LeaveTransaction::where('user_id', auth()->id())
                        ->with('leave')
                        ->latest()
                        ->get();

    return view('leave.history', compact('leaves', 'transactions'));
}


    /*
    |--------------------------------------------------------------------------
    | Admin Leave List
    |--------------------------------------------------------------------------
    */
    public function adminIndex()
    {
        $leaves = Leave::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('leave.admin', compact('leaves'));
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Leave
    |--------------------------------------------------------------------------
    */
    public function approve($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved') {
            return back()->with('error', 'Already approved');
        }

        $user = User::findOrFail($leave->user_id);

        if ($leave->type === 'annual') {

            $balanceBefore = $user->annual_leave_balance;

            if ($balanceBefore < $leave->calculated_days) {
                return back()->with('error', 'Insufficient Balance');
            }

            $balanceAfter = $balanceBefore - $leave->calculated_days;

            $user->update([
                'annual_leave_balance' => $balanceAfter
            ]);

            LeaveTransaction::create([
                'user_id' => $user->id,
                'leave_id' => $leave->id,
                'days' => $leave->calculated_days,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'action' => 'approved',
                'processed_by' => auth()->id(),
            ]);
        }

        $leave->update([
            'status' => 'approved'
        ]);

        return back()->with('success', 'Leave Approved');
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Leave
    |--------------------------------------------------------------------------
    */
    public function reject($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved') {
            return back()->with('error', 'Cannot reject approved leave.');
        }

        $leave->update([
            'status' => 'rejected'
        ]);

        return back()->with('success', 'Leave Rejected');
    }

    /*
|--------------------------------------------------------------------------
| Revert Leave (Approved/Rejected → Pending)
|--------------------------------------------------------------------------
*/
public function revert($id)
{
    $leave = Leave::findOrFail($id);

    // If approved, return leave balance back
    if ($leave->status == 'approved') {
    $leave->user->increment('annual_leave_balance', $leave->calculated_days ?? $leave->days);
}

    $leave->update([
        'status' => 'pending'
    ]);

    return back()->with('success', 'Leave reverted to pending.');
}


/*
|--------------------------------------------------------------------------
| Delete Leave
|--------------------------------------------------------------------------
*/
public function destroy($id)
{
    $leave = Leave::findOrFail($id);

    // If approved, return balance before deleting
    if ($leave->status == 'approved') {
        $leave->user->increment('annual_leave_balance', $leave->days);

    }

    $leave->delete();

    return back()->with('success', 'Leave deleted successfully.');
}

    public function adminCreate()
{
    $employees = \App\Models\User::where('role','employee')->get();
    return view('leave.admin-create', compact('employees'));
}
public function adminStore(Request $request)
{
    $request->validate([
        'user_id' => 'required',
        'type' => 'required',
        'days' => 'required|numeric|min:0.5',
        'status' => 'required'
    ]);

    Leave::create([
        'user_id' => $request->user_id,
        'type' => $request->type,
        'days' => $request->days,
        'status' => $request->status
    ]);

    return redirect()->route('admin.leave.index')
        ->with('success','Leave Added Successfully');
}
public function adminEdit($id)
{
    $leave = Leave::findOrFail($id);
    $employees = \App\Models\User::where('role','employee')->get();

    return view('leave.admin-edit', compact('leave','employees'));
}
public function adminUpdate(Request $request, $id)
{
    $leave = Leave::findOrFail($id);

    $request->validate([
        'type' => 'required',
        'days' => 'required|numeric|min:0.5',
        'status' => 'required'
    ]);

    $leave->update([
        'user_id' => $request->user_id,
        'type' => $request->type,
        'days' => $request->days,
        'status' => $request->status
    ]);

    return redirect()->route('admin.leave.index')
        ->with('success','Leave Updated Successfully');
}

    
    /*
    |--------------------------------------------------------------------------
    | Admin Transactions
    |--------------------------------------------------------------------------
    */
    public function adminTransactions()
    {
        $transactions = LeaveTransaction::with('user', 'leave')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('leave.admin_transactions', compact('transactions'));
    }

    /*
    |--------------------------------------------------------------------------
    | Export Transactions
    |--------------------------------------------------------------------------
    */
    public function exportTransactions()
    {
        return Excel::download(
            new LeaveTransactionsExport,
            'leave_transactions.xlsx'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Payroll Summary
    |--------------------------------------------------------------------------
    */
    public function payrollSummary(Request $request)
    {
        $year = $request->year ?? now()->year;

        $annualUsed = Leave::where('type', 'annual')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('calculated_days');

        $withoutPay = Leave::where('type', 'without_pay')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('calculated_days');

        $sickUsed = Leave::where('type', 'sick')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('calculated_days');

        $monthly = Leave::selectRaw('MONTH(start_date) as month, SUM(calculated_days) as total')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $employees = Leave::selectRaw('user_id, SUM(calculated_days) as total_used')
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->groupBy('user_id')
            ->with('user')
            ->get();

        return view('leave.payroll-summary', compact(
            'annualUsed',
            'withoutPay',
            'sickUsed',
            'monthly',
            'employees',
            'year'
        ));
    }

}
