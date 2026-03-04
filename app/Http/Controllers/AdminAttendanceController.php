<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function dashboard()
{
    $today = \Carbon\Carbon::today('Asia/Karachi');

    // Present
    $present = \App\Models\Attendance::whereDate('created_at', $today)
        ->where('status', 'present')
        ->count();

    // Late
    $late = \App\Models\Attendance::whereDate('created_at', $today)
        ->where('status', 'late')
        ->count();

    // Currently working
    $working = \App\Models\Attendance::whereDate('created_at', $today)
        ->whereNotNull('clock_in')
        ->whereNull('clock_out')
        ->with('user')
        ->get();

    // Total employees
    $totalEmployees = \App\Models\User::where('role','employee')->count();

    // Absent
    $absent = $totalEmployees - ($present + $late);

    return view('admin.attendance-dashboard', compact(
        'present',
        'late',
        'absent',
        'working'
    ));
}
}
