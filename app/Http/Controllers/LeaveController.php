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
        return view('leave.create', compact('employees'));
    }

    $employees = collect([auth()->user()]);
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

    return view('leave.history',
        compact('leaves','transactions')
    );
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

        Mail::raw(
    "Leave Request Submitted\n\nEmployee: " . auth()->user()->name .
    "\nFrom: " . $request->start_date .
    "\nTo: " . $request->end_date .
    "\nDays: " . $calculatedDays,
    function ($message) {
        $message->to(auth()->user()->email)
                ->subject('Leave Request Submitted');
    }
);

        return back()->with('success','Leave Request Submitted Successfully');
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
        $query->whereMonth('start_date',
            \Carbon\Carbon::parse($request->month)->month
        )->whereYear('start_date',
            \Carbon\Carbon::parse($request->month)->year
        );
    }

    $leaves = $query->get();

    $employees = \App\Models\User::where('role','employee')->get();

    return view('leave.admin', compact('leaves','employees'));
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


    public function assignLeave(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'opening_balance' => 'required|integer|min:0'
    ]);

    $balance = LeaveBalance::updateOrCreate(
        ['user_id' => $request->user_id],
        [
            'opening_balance' => $request->opening_balance,
            'used_leaves' => 0,
            'remaining_leaves' => $request->opening_balance
        ]
    );

    return back()->with('success','Leave Assigned Successfully');
}

public function balanceIndex()
{
    $balances = LeaveBalance::with('user')->get();
    $employees = User::where('role','employee')->get();

    return view('leave.balance', compact('balances','employees'));
}

    /*
|--------------------------------------------------------------------------
| LEAVE BALANCE UPDATE (ADMIN)
|--------------------------------------------------------------------------
*/

public function updateBalance(Request $request, $id)
{
    // ✅ Check user exists
    $user = \App\Models\User::find($id);

    if (!$user) {
        return back()->with('error', 'User not found');
    }

    $request->validate([
        'opening_balance' => 'required|numeric|min:0'
    ]);

    $balance = \App\Models\LeaveBalance::firstOrCreate(
        ['user_id' => $user->id],
        [
            'opening_balance' => 0,
            'used_leaves' => 0,
            'remaining_leaves' => 0,
        ]
    );

    $newOpening = $request->opening_balance;

    $balance->update([
        'opening_balance' => $newOpening,
        'remaining_leaves' => $newOpening - $balance->used_leaves
    ]);

    return back()->with('success','Leave balance updated successfully');
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
    if ($leave->type !== 'annual') {
        return;
    }

    $balance = \App\Models\LeaveBalance::firstOrCreate(
        ['user_id' => $leave->user_id],
        [
            'opening_balance' => 0,
            'used_leaves' => 0,
            'remaining_leaves' => 0
        ]
    );

    if ($balance->remaining_leaves < $leave->calculated_days) {
        abort(403,'Insufficient Leave Balance');
    }

    $before = $balance->remaining_leaves;
    $after  = $before - $leave->calculated_days;

    $balance->update([
        'used_leaves' => $balance->used_leaves + $leave->calculated_days,
        'remaining_leaves' => $after
    ]);

    \App\Models\LeaveTransaction::create([
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


    public function calendar()
{
    $leaves = \App\Models\Leave::with('user')
        ->where('status','approved')
        ->get();

    $events = [];

    foreach ($leaves as $leave) {
        $events[] = [
            'title' => $leave->user->name . ' (' . $leave->calculated_days . ' day)',
            'start' => $leave->start_date,
            'end'   => \Carbon\Carbon::parse($leave->end_date)->addDay()->format('Y-m-d'),
            'color' => '#16a34a'
        ];
    }

    return view('leave.calendar', compact('events'));
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
