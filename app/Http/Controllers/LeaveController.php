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
        return view('leave.create', compact('employees'));
    }

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
            'user_id'        => 'required|exists:users,id',
            'type'           => 'required|in:annual,without_pay',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'duration_type'  => 'required|in:full_day,half_day',
            'status'         => 'required|in:pending,approved,rejected',
            'opening_balance'=> 'nullable|numeric|min:0'
        ]);

        // ✅ Set Opening Balance if provided
        if($request->opening_balance !== null){
            LeaveBalance::updateOrCreate(
                ['user_id'=>$request->user_id],
                [
                    'opening_balance'=>$request->opening_balance,
                    'remaining_leaves'=>$request->opening_balance,
                    'used_leaves'=>0
                ]
            );
        }

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

        if($request->status === 'approved'){
            $this->processApproval($leave);
        }

        return redirect()->route('admin.leave.index')
            ->with('success','Leave Added Successfully');
    }


/*
|--------------------------------------------------------------------------
| APPROVAL LOGIC
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

        return back()->with('success','Leave Approved');
    }


    private function processApproval($leave)
    {
        if($leave->type !== 'annual') return;

        $balance = LeaveBalance::firstOrCreate(
            ['user_id'=>$leave->user_id],
            [
                'opening_balance'=>0,
                'remaining_leaves'=>0,
                'used_leaves'=>0
            ]
        );

        if($balance->remaining_leaves < $leave->calculated_days){
            abort(403,'Insufficient Leave Balance');
        }

        $before = $balance->remaining_leaves;
        $after  = $before - $leave->calculated_days;

        $balance->update([
            'remaining_leaves'=>$after,
            'used_leaves'=>$balance->used_leaves + $leave->calculated_days
        ]);

        LeaveTransaction::create([
            'user_id'=>$leave->user_id,
            'leave_id'=>$leave->id,
            'days'=>$leave->calculated_days,
            'balance_before'=>$before,
            'balance_after'=>$after,
            'action'=>'approved',
            'processed_by'=>auth()->id(),
        ]);
    }


/*
|--------------------------------------------------------------------------
| REVERT / DELETE
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
| TRANSACTIONS + EXPORT
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
