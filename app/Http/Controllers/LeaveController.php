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
            'type' => 'required',
            'start_date' => 'required|date',
            'duration_type' => 'required',
            'reason' => 'required'
        ]);

        $start = Carbon::parse($request->start_date);

        if ($request->duration_type === 'half_day') {
            $end = $start;
            $days = 0.5;
        } else {
            $request->validate([
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $end = Carbon::parse($request->end_date);
            $days = $start->diffInDays($end) + 1;
        }

        Leave::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'duration_type' => $request->duration_type,
            'half_day_type' => $request->half_day_type,
            'calculated_days' => $days,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('leave.index')
            ->with('success', 'Leave Applied Successfully');
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
