<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use App\Models\LeaveTransaction;
use App\Notifications\LeaveApproved;
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
    public function adminIndex()
    {
        $leaves = Leave::with('user')
            ->latest()
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

}
