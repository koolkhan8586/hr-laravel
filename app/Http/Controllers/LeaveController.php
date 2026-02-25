<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use App\Models\LeaveTransaction;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LeaveTransactionsExport;
use Illuminate\Support\Facades\Mail;

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
            $employees = User::where('role','employee')->get();
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

    public function store(Request $request)
    {
        $request->validate([
            'user_id'       => auth()->user()->role === 'admin' ? 'required|exists:users,id' : '',
            'type'          => 'required|in:annual,without_pay',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'duration_type' => 'required|in:full_day,half_day',
            'reason'        => 'nullable|string'
        ]);

        $userId = auth()->user()->role === 'admin'
            ? $request->user_id
            : auth()->id();

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        $days = $request->duration_type === 'half_day'
            ? 0.5
            : $start->diffInDays($end) + 1;

        $leave = Leave::create([
            'user_id'        => $userId,
            'type'           => $request->type,
            'start_date'     => $request->start_date,
            'end_date'       => $request->end_date,
            'duration_type'  => $request->duration_type,
            'half_day_type'  => $request->half_day_type ?? null,
            'days'           => $days,
            'calculated_days'=> $days,
            'reason'         => $request->reason,
            'status'         => auth()->user()->role === 'admin' ? 'approved' : 'pending',
        ]);

        // ================= EMAIL LOGIC =================

        if(auth()->user()->role === 'employee'){
            $admins = User::where('role','admin')->get();

            foreach($admins as $admin){
                Mail::raw(
                    "New Leave Request\n\nEmployee: ".auth()->user()->name.
                    "\nFrom: ".$request->start_date.
                    "\nTo: ".$request->end_date.
                    "\nDays: ".$days,
                    function ($message) use ($admin) {
                        $message->to($admin->email)
                            ->subject('New Leave Application Submitted');
                    }
                );
            }
        }

        if(auth()->user()->role === 'admin'){
            $this->processApproval($leave);

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
| ADMIN SECTION
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

        $leaves = $query->get();
        $employees = User::where('role','employee')->get();

        return view('leave.admin', compact('leaves','employees'));
    }


/*
|--------------------------------------------------------------------------
| APPROVE / REJECT
|--------------------------------------------------------------------------
*/

    public function approve($id)
    {
        $leave = Leave::findOrFail($id);

        if($leave->status === 'approved'){
            return back()->with('error','Already Approved');
        }

        $this->processApproval($leave);

        $leave->update(['status'=>'approved']);

        Mail::raw(
            "Your Leave Has Been Approved\n\nFrom: ".$leave->start_date.
            "\nTo: ".$leave->end_date.
            "\nDays: ".$leave->calculated_days,
            function ($message) use ($leave) {
                $message->to($leave->user->email)
                    ->subject('Leave Approved');
            }
        );

        return back()->with('success','Leave Approved');
    }

    public function reject($id)
    {
        $leave = Leave::findOrFail($id);

        $leave->update(['status'=>'rejected']);

        Mail::raw(
            "Your Leave Has Been Rejected\n\nFrom: ".$leave->start_date.
            "\nTo: ".$leave->end_date,
            function ($message) use ($leave) {
                $message->to($leave->user->email)
                    ->subject('Leave Rejected');
            }
        );

        return back()->with('success','Leave Rejected');
    }


/*
|--------------------------------------------------------------------------
| PROCESS APPROVAL
|--------------------------------------------------------------------------
*/

    private function processApproval($leave)
    {
        if ($leave->type !== 'annual') return;

        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $leave->user_id],
            [
                'opening_balance' => 0,
                'used_leaves' => 0,
                'remaining_leaves' => 0
            ]
        );

        if ($balance->remaining_leaves < $leave->calculated_days) {
            return;
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
                $balance->increment('remaining_leaves',$leave->calculated_days);
                $balance->decrement('used_leaves',$leave->calculated_days);
            }
        }

        $leave->update(['status'=>'pending']);

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

        if($leave->status === 'approved' && $leave->type === 'annual'){
            $balance = LeaveBalance::where('user_id',$leave->user_id)->first();

            if($balance){
                $balance->increment('remaining_leaves',$leave->calculated_days);
                $balance->decrement('used_leaves',$leave->calculated_days);
            }
        }

        $leave->delete();

        return back()->with('success','Leave Deleted');
    }


/*
|--------------------------------------------------------------------------
| LEAVE ALLOCATION (OPENING BALANCE)
|--------------------------------------------------------------------------
*/

    public function allocationIndex()
    {
        $employees = User::where('role','employee')->get();
        return view('leave.balance-index', compact('employees'));
    }

    public function updateAllocation(Request $request, $id)
    {
        $request->validate([
            'annual_leave_balance' => 'required|numeric|min:0'
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'annual_leave_balance' => $request->annual_leave_balance
        ]);

        return back()->with('success','Leave Allocation Updated Successfully');
    }


/*
|--------------------------------------------------------------------------
| CALENDAR
|--------------------------------------------------------------------------
*/

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
| EXPORT + PAYROLL
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
