<?php

namespace App\Http\Controllers;
use App\Notifications\LeaveApproved;
use App\Models\Leave;
use App\Models\User;
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
            ->orderBy('created_at', 'desc')
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

        /*
        |--------------------------------------------------------------------------
        | Calculate Days
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Prevent Overlapping Leave
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Check Annual Leave Balance
        |--------------------------------------------------------------------------
        */
        if ($request->type === 'annual') {

            $user = auth()->user();

            if ($user->annual_leave_balance < $calculatedDays) {
                return back()->with('error', 'Insufficient Leave Balance.');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create Leave
        |--------------------------------------------------------------------------
        */
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
    | Admin Panel - View All Leaves
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
        return back()->with('error', 'Leave already approved.');
    }

    if ($leave->type === 'annual') {

        $user = User::findOrFail($leave->user_id);

        $balanceBefore = $user->annual_leave_balance;

        if ($balanceBefore < $leave->calculated_days) {
            return back()->with('error', 'Insufficient Leave Balance.');
        }

        $balanceAfter = $balanceBefore - $leave->calculated_days;

        // Update user balance
        $user->update([
            'annual_leave_balance' => $balanceAfter
        ]);

        // Create transaction record
        \App\Models\LeaveTransaction::create([
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

    return back()->with('success', 'Leave Approved Successfully');
}
     
/* public function approve($id)
   {
    $leave = Leave::findOrFail($id);

    if ($leave->status === 'approved') {
        return back()->with('error', 'Already approved');
    }

    if ($leave->type === 'annual') {

        $user = \App\Models\User::findOrFail($leave->user_id);

        $newBalance = (float) $user->annual_leave_balance - (float) $leave->calculated_days;

        // Force assign new value
        \DB::table('users')
            ->where('id', $user->id)
            ->update([
                'annual_leave_balance' => $newBalance
            ]);
    }

    $leave->status = 'approved';
    $leave->save();

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

        $leave->status = 'rejected';
        $leave->save();

        return back()->with('success', 'Leave Rejected Successfully');
    }
}
