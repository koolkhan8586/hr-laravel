<?php

namespace App\Http\Controllers;


use App\Exports\LeaveTransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Leave;
use App\Models\User;
use App\Models\LeaveTransaction;
use App\Notifications\LeaveApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
            ->latest()
            ->get();

        $balance = auth()->user()->annual_leave_balance;

        return view('leave.index', compact('leaves', 'balance'));
    }

    /*
    |--------------------------------------------------------------------------
    | Show Apply Form
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $balance = auth()->user()->annual_leave_balance;

        return view('leave.create', compact('balance'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store Leave Request
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'type'          => 'required',
            'start_date'    => 'required|date',
            'duration_type' => 'required',
            'reason'        => 'required'
        ]);

        $start = Carbon::parse($request->start_date);

        // Calculate Days
        if ($request->duration_type === 'half_day') {
            $end = $start;
            $calculatedDays = 0.5;
        } else {
            $request->validate([
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $end = Carbon::parse($request->end_date);
            $calculatedDays = $start->diffInDays($end) + 1;
        }

        // Prevent overlapping leave
        $overlap = Leave::where('user_id', auth()->id())
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                      });
            })
            ->exists();

        if ($overlap) {
            return back()->with('error', 'You already have leave during this period.');
        }

        // Check annual leave balance
        if ($request->type === 'annual') {
            $user = auth()->user();

            if ((float) $user->annual_leave_balance < (float) $calculatedDays) {
                return back()->with('error', 'Insufficient Leave Balance.');
            }
        }

        // Create Leave
        Leave::create([
            'user_id'         => auth()->id(),
            'type'            => $request->type,
            'start_date'      => $start,
            'end_date'        => $end,
            'days'            => $calculatedDays,
            'duration_type'   => $request->duration_type,
            'half_day_type'   => $request->half_day_type,
            'calculated_days' => $calculatedDays,
            'reason'          => $request->reason,
            'status'          => 'pending',
        ]);

        return redirect()->route('leave.index')
            ->with('success', 'Leave Applied Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin - View All Leaves
    |--------------------------------------------------------------------------
    */
    public function adminIndex(Request $request)
{
    $query = Leave::with('user')->orderBy('created_at', 'desc');

    // Optional filter by month
    if ($request->month) {
        $query->whereMonth('start_date', date('m', strtotime($request->month)))
              ->whereYear('start_date', date('Y', strtotime($request->month)));
    }

    $leaves = $query->get();   // ✅ THIS LINE IS IMPORTANT

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

        // Prevent double approval
        if ($leave->status === 'approved') {
            return back()->with('info', 'Leave already approved.');
        }

        $user = User::findOrFail($leave->user_id);

        $balanceBefore = (float) $user->annual_leave_balance;
        $balanceAfter  = $balanceBefore;

        if ($leave->type === 'annual') {

            if ($balanceBefore < (float) $leave->calculated_days) {
                return back()->with('error', 'Insufficient Leave Balance.');
            }

            $balanceAfter = $balanceBefore - (float) $leave->calculated_days;

            $user->update([
                'annual_leave_balance' => $balanceAfter
            ]);

            LeaveTransaction::create([
                'user_id'        => $user->id,
                'leave_id'       => $leave->id,
                'days'           => $leave->calculated_days,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'action'         => 'approved',
                'processed_by'   => auth()->id(),
            ]);
        }

        $leave->update([
            'status' => 'approved'
        ]);

        // ✅ Send Email Notification
        $user->notify(new LeaveApproved($leave));

        return back()->with('success', 'Leave Approved Successfully');
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
            return back()->with('error', 'Cannot reject an approved leave.');
        }

        $leave->update([
            'status' => 'rejected'
        ]);

        return back()->with('success', 'Leave Rejected Successfully');
    }

    /*
|--------------------------------------------------------------------------
| Export Leave Transactions
|--------------------------------------------------------------------------
*/
public function exportTransactions(Request $request)
{
    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\LeaveTransactionsExport($request->user_id),
        'leave_transactions.xlsx'
    );
}

    /*
    |--------------------------------------------------------------------------
    | Leave Transaction History
    |--------------------------------------------------------------------------
    */
    public function history()
    {
    $transactions = \App\Models\LeaveTransaction::where('user_id', auth()->id())
        ->orderBy('created_at', 'desc')
        ->get();

    return view('leave.history', compact('transactions'));
    }

    public function adminTransactions(Request $request)
{
    $query = \App\Models\LeaveTransaction::with(['user'])
        ->orderBy('created_at', 'desc');

    // Month filter
    if ($request->month) {
        $query->whereMonth('created_at', $request->month);
    }

    // Year filter
    if ($request->year) {
        $query->whereYear('created_at', $request->year);
    }

    $transactions = $query->get();

    return view('leave.admin_transactions', compact('transactions'));
}


}
    public function payrollSummary(Request $request)
{
    $year = $request->year ?? now()->year;

    // Annual Used
    $annualUsed = Leave::where('type', 'annual')
        ->where('status', 'approved')
        ->whereYear('start_date', $year)
        ->sum('calculated_days');

    // Without Pay Used
    $withoutPay = Leave::where('type', 'without_pay')
        ->where('status', 'approved')
        ->whereYear('start_date', $year)
        ->sum('calculated_days');

    // Sick Leave Used
    $sickUsed = Leave::where('type', 'sick')
        ->where('status', 'approved')
        ->whereYear('start_date', $year)
        ->sum('calculated_days');

    // Monthly Breakdown
    $monthly = Leave::select(
            DB::raw('MONTH(start_date) as month'),
            DB::raw('SUM(calculated_days) as total')
        )
        ->where('status', 'approved')
        ->whereYear('start_date', $year)
        ->groupBy(DB::raw('MONTH(start_date)'))
        ->orderBy('month')
        ->get();

    // Per Employee Summary
    $employees = Leave::select(
            'user_id',
            DB::raw('SUM(calculated_days) as total_used')
        )
        ->where('status', 'approved')
        ->whereYear('start_date', $year)
        ->groupBy('user_id')
        ->with('user')
        ->get();

    return view('leave.payroll-summary', compact(
        'year',
        'annualUsed',
        'withoutPay',
        'sickUsed',
        'monthly',
        'employees'
    ));
}
