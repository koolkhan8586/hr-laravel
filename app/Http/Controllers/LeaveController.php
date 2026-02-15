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
    | Employee Leave List
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $leaves = Leave::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('leave.index', compact('leaves'));
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Apply Form
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('leave.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Store Leave
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $request->validate([
            'type'          => 'required|in:annual,without_pay',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'duration_type' => 'required|in:full_day,half_day',
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
            'status'         => 'pending',
        ]);

        return redirect()->route('leave.index')
            ->with('success','Leave Applied Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Leave History
    |--------------------------------------------------------------------------
    */
    public function history()
    {
        $leaves = auth()->user()->leaves()->latest()->get();

        $transactions = LeaveTransaction::where('user_id', auth()->id())
            ->with('leave')
            ->latest()
            ->get();

        return view('leave.history', compact('leaves', 'transactions'));
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

    /*
    |--------------------------------------------------------------------------
    | ADMIN STORE
    |--------------------------------------------------------------------------
    */
    public function adminStore(Request $request)
    {
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
        ]);

        // Send email notification
        if ($leave->user && $leave->user->email) {
            Mail::raw(
                "Your leave from {$leave->start_date} to {$leave->end_date} has been created with status: {$leave->status}",
                function ($message) use ($leave) {
                    $message->to($leave->user->email)
                            ->subject('Leave Notification');
                }
            );
        }

        return redirect()->route('admin.leave.index')
            ->with('success', 'Leave added successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE
    |--------------------------------------------------------------------------
    */
    public function approve($id)
    {
        $leave = Leave::findOrFail($id);
        $user  = $leave->user;

        if ($leave->status === 'approved') {
            return back()->with('error','Already approved');
        }

        if ($leave->type === 'annual') {

            if ($user->annual_leave_balance < $leave->calculated_days) {
                return back()->with('error','Insufficient Balance');
            }

            $balanceBefore = $user->annual_leave_balance;
            $balanceAfter  = $balanceBefore - $leave->calculated_days;

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

        $leave->update(['status'=>'approved']);

        return back()->with('success','Leave Approved');
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */
    public function reject($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved') {
            return back()->with('error','Cannot reject approved leave.');
        }

        $leave->update(['status'=>'rejected']);

        return back()->with('success','Leave Rejected');
    }

    /*
    |--------------------------------------------------------------------------
    | REVERT
    |--------------------------------------------------------------------------
    */
    public function revert($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved') {
            $leave->user->increment(
                'annual_leave_balance',
                $leave->calculated_days
            );
        }

        $leave->update(['status'=>'pending']);

        return back()->with('success','Leave reverted.');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);

        if ($leave->status === 'approved') {
            $leave->user->increment(
                'annual_leave_balance',
                $leave->calculated_days
            );
        }

        $leave->delete();

        return back()->with('success','Leave deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN UPDATE
    |--------------------------------------------------------------------------
    */
    public function adminUpdate(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $request->validate([
            'type'   => 'required|in:annual,without_pay',
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $leave->update([
            'type'   => $request->type,
            'status' => $request->status
        ]);

        return redirect()->route('admin.leave.index')
            ->with('success','Leave Updated Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN TRANSACTIONS
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

}
