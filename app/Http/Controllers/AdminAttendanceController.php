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
    $today = Carbon::today('Asia/Karachi')->toDateString();

    // Present
    $present = Attendance::where('date',$today)
        ->where('status','present')
        ->count();

    // Late
    $late = Attendance::where('date',$today)
        ->where('status','late')
        ->count();

    // Half Day
    $halfday = Attendance::where('date',$today)
        ->where('status','halfday')
        ->count();

    // Employees on Leave
    $leave = Leave::where('status','approved')
        ->whereDate('start_date','<=',$today)
        ->whereDate('end_date','>=',$today)
        ->count();

    // Currently Working
    $working = Attendance::where('date',$today)
        ->whereNotNull('clock_in')
        ->whereNull('clock_out')
        ->with('user')
        ->get();

    // Total employees
    $totalEmployees = User::where('role','employee')->count();

    // Employees who marked attendance
    $presentUserIds = Attendance::where('date',$today)
        ->pluck('user_id');

    // Employees on leave today
    $leaveUserIds = Leave::where('status','approved')
        ->whereDate('start_date','<=',$today)
        ->whereDate('end_date','>=',$today)
        ->pluck('user_id');

    // Absent = employees not present and not on leave
    $absent = User::where('role','employee')
        ->whereNotIn('id',$presentUserIds)
        ->whereNotIn('id',$leaveUserIds)
        ->count();

    return view('admin.attendance-dashboard', compact(
        'present',
        'late',
        'halfday',
        'leave',
        'absent',
        'working'
    ));
}



public function attendanceList($type)
{
    $today = Carbon::today('Asia/Karachi')->toDateString();

    if ($type == 'present') {

        $records = Attendance::where('date',$today)
            ->where('status','present')
            ->with('user')
            ->get();
    }

    elseif ($type == 'late') {

        $records = Attendance::where('date',$today)
            ->where('status','late')
            ->with('user')
            ->get();
    }

    elseif ($type == 'halfday') {

        $records = Attendance::where('date',$today)
            ->where('status','halfday')
            ->with('user')
            ->get();
    }

    elseif ($type == 'working') {

        $records = Attendance::where('date',$today)
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

        // Users who marked attendance today
        $presentUsers = Attendance::where('date',$today)
            ->pluck('user_id');

        // Users on leave today
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
