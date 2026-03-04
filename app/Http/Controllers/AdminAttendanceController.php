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
    $date = request('date', now()->toDateString());

/*
|--------------------------------------------------------------------------
| Present
|--------------------------------------------------------------------------
*/

$present = Attendance::whereDate('date',$date)
    ->where('status','present')
    ->distinct('user_id')
    ->count('user_id');

/*
|--------------------------------------------------------------------------
| Late
|--------------------------------------------------------------------------
*/

$late = Attendance::whereDate('date',$date)
    ->where('status','late')
    ->distinct('user_id')
    ->count('user_id');

/*
|--------------------------------------------------------------------------
| Half Day
|--------------------------------------------------------------------------
*/

$halfday = Attendance::whereDate('date',$date)
    ->where('status','half_day')
    ->distinct('user_id')
    ->count('user_id');

/*
|--------------------------------------------------------------------------
| Leave
|--------------------------------------------------------------------------
*/

$leave = Leave::where('status','approved')
    ->whereDate('start_date','<=',$date)
    ->whereDate('end_date','>=',$date)
    ->count();

/*
|--------------------------------------------------------------------------
| Working Employees
|--------------------------------------------------------------------------
*/

$working = Attendance::whereDate('date',$date)
    ->whereNotNull('clock_in')
    ->whereNull('clock_out')
    ->with('user')
    ->get();

/*
|--------------------------------------------------------------------------
| Attendance Users
|--------------------------------------------------------------------------
*/

$attendanceUsers = Attendance::whereDate('date',$date)
    ->pluck('user_id');

/*
|--------------------------------------------------------------------------
| Leave Users
|--------------------------------------------------------------------------
*/

$leaveUsers = Leave::where('status','approved')
    ->whereDate('start_date','<=',$date)
    ->whereDate('end_date','>=',$date)
    ->pluck('user_id');

/*
|--------------------------------------------------------------------------
| Absent Employees
|--------------------------------------------------------------------------
*/

$absentEmployees = User::where('role','employee')
    ->whereNotIn('id',$attendanceUsers)
    ->whereNotIn('id',$leaveUsers)
    ->get();

$absent = $absentEmployees->count();

/*
|--------------------------------------------------------------------------
| Return View
|--------------------------------------------------------------------------
*/

return view('admin.attendance-dashboard', compact(
    'present',
    'late',
    'halfday',
    'leave',
    'absent',
    'working',
    'date'
));
}


/*
|--------------------------------------------------------------------------
| Manual Attendance Entry
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Live Attendance Table
|--------------------------------------------------------------------------
*/

public function liveAttendance()
{
    $today = Carbon::today('Asia/Karachi');

    $working = Attendance::whereDate('date',$today)
        ->whereNotNull('clock_in')
        ->whereNull('clock_out')
        ->with('user')
        ->get();

    return view('admin.partials.live-attendance-table', compact('working'));
}


/*
|--------------------------------------------------------------------------
| Attendance List
|--------------------------------------------------------------------------
*/

public function attendanceList($type)
{
    $today = request('date', Carbon::today('Asia/Karachi')->toDateString());

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


public function attendanceCalendar(Request $request)
{

$month = $request->month ?? now()->format('Y-m');

$start = \Carbon\Carbon::parse($month.'-01')->startOfMonth();
$end = \Carbon\Carbon::parse($month.'-01')->endOfMonth();

$users = \App\Models\User::where('role','employee')
    ->orderBy('name')
    ->get();

$attendances = \App\Models\Attendance::whereBetween('date',[$start,$end])
    ->get()
    ->groupBy('user_id');

$leaves = Leave::where('status','approved')
    ->where(function($q) use ($start,$end){

        $q->whereBetween('start_date',[$start,$end])
          ->orWhereBetween('end_date',[$start,$end])
          ->orWhere(function($q2) use ($start,$end){
                $q2->where('start_date','<=',$start)
                   ->where('end_date','>=',$end);
          });

    })
    ->get()
    ->groupBy('user_id');
return view('admin.attendance-calendar',compact(
    'users',
    'attendances',
    'leaves',
    'start',
    'end',
    'month'
));

}
    
}
