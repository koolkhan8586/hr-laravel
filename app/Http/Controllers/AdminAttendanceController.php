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
    $today = Carbon::today('Asia/Karachi');

    if ($type == 'present') {

        $records = Attendance::whereDate('created_at',$today)
            ->where('status','present')
            ->with('user')
            ->get();
    }

    elseif ($type == 'late') {

        $records = Attendance::whereDate('created_at',$today)
            ->where('status','late')
            ->with('user')
            ->get();
    }

    elseif ($type == 'halfday') {

        $records = Attendance::whereDate('created_at',$today)
            ->where('status','halfday')
            ->with('user')
            ->get();
    }

    elseif ($type == 'working') {

        $records = Attendance::whereDate('created_at',$today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->with('user')
            ->get();
    }

    elseif ($type == 'leave') {

        $records = Leave::where('status','approved')
            ->whereDate('start_date','<=',$today)
            ->whereDate('end_date','>=',$today)
            ->with('user')
            ->get();
    }

    elseif ($type == 'absent') {

    $today = Carbon::today('Asia/Karachi');

    // Employees who marked attendance today
    $presentUsers = Attendance::whereDate('created_at',$today)
        ->pluck('user_id');

    // Employees on leave today
    $leaveUsers = Leave::where('status','approved')
        ->whereDate('start_date','<=',$today)
        ->whereDate('end_date','>=',$today)
        ->pluck('user_id');

    // Absent employees
    $records = User::where('role','employee')
        ->whereNotIn('id',$presentUsers)
        ->whereNotIn('id',$leaveUsers)
        ->get();
}

    return view('admin.attendance-list',compact('records','type'));
}

}
