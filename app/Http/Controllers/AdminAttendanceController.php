<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Leave;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{

public function dashboard()
{
    $today = Carbon::today('Asia/Karachi');

// Present
$present = Attendance::whereDate('created_at',$today)
    ->where('status','present')
    ->distinct('user_id')
    ->count('user_id');

// Late
$late = Attendance::whereDate('created_at',$today)
    ->where('status','late')
    ->distinct('user_id')
    ->count('user_id');

// Half Day
$halfday = Attendance::whereDate('created_at',$today)
    ->where('status','half_day')
    ->distinct('user_id')
    ->count('user_id');

// Leave
$leave = \App\Models\Leave::where('status','approved')
    ->whereDate('start_date','<=',$today)
    ->whereDate('end_date','>=',$today)
    ->count();

// Working
$working = Attendance::whereDate('created_at',$today)
    ->whereNotNull('clock_in')
    ->whereNull('clock_out')
    ->with('user')
    ->get();

// Attendance users
$attendanceUsers = Attendance::whereDate('created_at',$today)
    ->pluck('user_id');

// Leave users
$leaveUsers = \App\Models\Leave::where('status','approved')
    ->whereDate('start_date','<=',$today)
    ->whereDate('end_date','>=',$today)
    ->pluck('user_id');

// Absent employees
// employees who marked attendance today
$attendanceUsers = Attendance::whereDate('created_at',$today)
    ->pluck('user_id');

// employees on leave today
$leaveUsers = Leave::where('status','approved')
    ->whereDate('start_date','<=',$today)
    ->whereDate('end_date','>=',$today)
    ->pluck('user_id');

// absent employees
$absentEmployees = User::where('role','employee')
    ->whereNotIn('id',$attendanceUsers)
    ->whereNotIn('id',$leaveUsers)
    ->get();

$absent = $absentEmployees->count();

return view('admin.attendance-dashboard', compact(
    'present',
    'late',
    'halfday',
    'leave',
    'absent',
    'working'
));
}

public function manualMarkAttendance(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'date' => 'required|date',
        'clock_in' => 'required',
        'clock_out' => 'nullable'
    ]);

    $date = $request->date;

    $clockIn = Carbon::parse($date.' '.$request->clock_in);
    $clockOut = $request->clock_out
        ? Carbon::parse($date.' '.$request->clock_out)
        : null;

    $status = 'present';

    // Late after 9:45
    if ($clockIn->gt(Carbon::parse($date.' 09:45:00'))) {
        $status = 'late';
    }

    $totalHours = null;

    if ($clockOut) {

        $minutes = $clockIn->diffInMinutes($clockOut);
        $hours = $minutes / 60;

        $totalHours = round($hours,2);

        if ($hours < 4) {
            $status = 'half_day';
        }
    }

    Attendance::updateOrCreate(

        [
            'user_id' => $request->user_id,
            'date' => $date
        ],

        [
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => $status,
            'total_hours' => $totalHours
        ]

    );

    return back()->with('success','Attendance marked successfully.');
}
    
public function liveAttendance()
{
    $today = \Carbon\Carbon::today('Asia/Karachi');

    $working = \App\Models\Attendance::whereDate('date',$today)
        ->whereNotNull('clock_in')
        ->whereNull('clock_out')
        ->with('user')
        ->get();

    return view('admin.partials.live-attendance-table', compact('working'));
}

public function attendanceList($type)
{
    $today = Carbon::today('Asia/Karachi')->toDateString();

    /*
    |--------------------------------------------------------------------------
    | Leave Records
    |--------------------------------------------------------------------------
    */

    if ($type === 'leave') {

        $records = Leave::where('status','approved')
            ->whereDate('start_date','<=',$today)
            ->whereDate('end_date','>=',$today)
            ->with('user')
            ->get();

        return view('admin.attendance-list', compact('records','type'));
    }

    /*
    |--------------------------------------------------------------------------
    | Absent Employees
    |--------------------------------------------------------------------------
    */

    if ($type === 'absent') {

        $presentUsers = Attendance::whereDate('date',$today)
            ->pluck('user_id');

        $leaveUsers = Leave::where('status','approved')
            ->whereDate('start_date','<=',$today)
            ->whereDate('end_date','>=',$today)
            ->pluck('user_id');

        $records = User::where('role','employee')
            ->whereNotIn('id',$presentUsers)
            ->whereNotIn('id',$leaveUsers)
            ->get();

        return view('admin.attendance-list', compact('records','type'));
    }

    /*
    |--------------------------------------------------------------------------
    | Working Employees
    |--------------------------------------------------------------------------
    */

    if ($type === 'working') {

        $records = Attendance::whereDate('date',$today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->with('user')
            ->get();

        return view('admin.attendance-list', compact('records','type'));
    }

    /*
    |--------------------------------------------------------------------------
    | Present / Late / Half Day
    |--------------------------------------------------------------------------
    */

    $statusMap = [
        'present' => 'present',
        'late' => 'late',
        'halfday' => 'half_day'
    ];

    if (isset($statusMap[$type])) {

        $records = Attendance::whereDate('date',$today)
            ->where('status',$statusMap[$type])
            ->with('user')
            ->get();

        return view('admin.attendance-list', compact('records','type'));
    }

    abort(404);
}

}
