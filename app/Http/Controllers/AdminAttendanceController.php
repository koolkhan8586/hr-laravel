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
        $today = Carbon::today('Asia/Karachi');

        $present = Attendance::whereDate('date', $today)
            ->whereIn('status', ['present','late'])
            ->count();

        $late = Attendance::whereDate('date', $today)
            ->where('status', 'late')
            ->count();

        $absent = User::whereDoesntHave('attendances', function ($q) use ($today) {
            $q->whereDate('date', $today);
        })->count();

        $working = Attendance::whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->with('user')
            ->get();

        return view('admin.attendance-dashboard', compact(
            'present','late','absent','working'
        ));
    }
}
