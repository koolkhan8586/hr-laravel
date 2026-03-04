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
        ->count();

    // Late
    $late = Attendance::whereDate('created_at',$today)
        ->where('status','late')
        ->count();

    // Half Day
    $halfday = Attendance::whereDate('created_at',$today)
        ->where('status','halfday')
        ->count();

    // Employees currently working
    $working = Attendance::whereDate('created_at',$today)
        ->whereNotNull('clock_in')
        ->whereNull('clock_out')
        ->with('user')
        ->get();

    // Employees on leave
    $leave = Leave::where('status','approved')
        ->whereDate('start_date','<=',$today)
        ->whereDate('end_date','>=',$today)
        ->count();

    // Employees who marked attendance today
    $presentUserIds = Attendance::whereDate('created_at',$today)
        ->pluck('user_id');

    // Employees on leave today
    $leaveUserIds = Leave::where('status','approved')
        ->whereDate('start_date','<=',$today)
        ->whereDate('end_date','>=',$today)
        ->pluck('user_id');

    // Absent employees
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

        $presentUsers = Attendance::whereDate('created_at',$today)
            ->pluck('user_id');

        $leaveUsers = Leave::where('status','approved')
            ->whereDate('start_date','<=',$today)
            ->whereDate('end_date','>=',$today)
            ->pluck('user_id');

        $records = User::where('role','employee')
            ->whereNotIn('id',$presentUsers)
            ->whereNotIn('id',$leaveUsers)
            ->get();
    }

    return view('admin.attendance-list',compact('records','type'));
}

}
