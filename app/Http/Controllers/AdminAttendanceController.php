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


/* Work From Home Today */

$today = now()->toDateString();

$wfhToday = \App\Models\WorkFromHome::with('user')
->whereDate('start_date','<=',$today)
->whereDate('end_date','>=',$today)
->get();

$wfhCount = $wfhToday->count();
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

$today = $date;

/* WFH users */

$wfhUsers = \App\Models\WorkFromHome::whereDate('start_date','<=',$today)
    ->whereDate('end_date','>=',$today)
    ->pluck('user_id')
    ->toArray();

/* Holiday users */

$holidayUsers = [];

$holidays = \App\Models\Holiday::with('users')
    ->whereDate('start_date','<=',$today)
    ->whereDate('end_date','>=',$today)
    ->get();

foreach($holidays as $h){

    if($h->for_all == 1){

        $holidayUsers = User::where('role','employee')
            ->pluck('id')
            ->toArray();

    }else{

        foreach($h->users as $u){
            $holidayUsers[] = $u->id;
        }

    }

}

/* Weekend check */

$isWeekend = \Carbon\Carbon::parse($today)->isWeekend();

/* Absent employees */

$absentEmployees = collect();

if(!$isWeekend){

$absentEmployees = User::where('role','employee')
    ->whereNotIn('id',$attendanceUsers)
    ->whereNotIn('id',$leaveUsers)
    ->whereNotIn('id',$wfhUsers)
    ->whereNotIn('id',$holidayUsers)
    ->get();

}

$absent = $absentEmployees->count();

/* Work From Home Today */

$today = now()->toDateString();

$wfhToday = \App\Models\WorkFromHome::with('user')
    ->whereDate('start_date','<=',$today)
    ->whereDate('end_date','>=',$today)
    ->get();

$wfhCount = $wfhToday->count();

return view('admin.attendance-dashboard', compact(
'present',
'late',
'halfday',
'leave',
'absent',
'working',
'wfhToday',
'wfhCount',
'date'
));


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
| Work From Home
|--------------------------------------------------------------------------
*/

if ($type === 'wfh') {

$records = \App\Models\WorkFromHome::whereDate('start_date','<=',$today)
->whereDate('end_date','>=',$today)
->with('user')
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
| Absent Employees
|--------------------------------------------------------------------------
*/

if ($type === 'absent') {

$attendanceUsers = Attendance::whereDate('date',$today)->pluck('user_id');

$leaveUsers = Leave::where('status','approved')
->whereDate('start_date','<=',$today)
->whereDate('end_date','>=',$today)
->pluck('user_id');

$wfhUsers = \App\Models\WorkFromHome::whereDate('start_date','<=',$today)
->whereDate('end_date','>=',$today)
->pluck('user_id');

$records = User::where('role','employee')
->whereNotIn('id',$attendanceUsers)
->whereNotIn('id',$leaveUsers)
->whereNotIn('id',$wfhUsers)
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

public function allowOvertime(Request $request)
{

$attendance = \App\Models\Attendance::findOrFail($request->attendance_id);

$attendance->overtime_allowed_until =
$attendance->date.' '.$request->overtime_until;

$attendance->save();

return back()->with('success','Overtime allowed.');

}

public function calendarEvents()
{

$events = [];

/* Holidays */


$holidays = \App\Models\Holiday::all();

foreach($holidays as $holiday){

    /* If holiday is for all employees */
    if($holiday->employee_id == null){

        $events[] = [
            'title' => 'Holiday: '.$holiday->title,
            'start' => $holiday->start_date,
            'end'   => $holiday->end_date,
            'color' => 'red'
        ];

    }

    /* If holiday is assigned to a specific employee */
    else{

        $employee = \App\Models\User::find($holiday->employee_id);

        $events[] = [
            'title' => $holiday->title.' ('.$employee->name.')',
            'start' => $holiday->start_date,
            'end'   => $holiday->end_date,
            'color' => 'orange'
        ];

    }

}


/* Leave */

$leaves = \App\Models\Leave::where('status','approved')->get();

foreach($leaves as $leave){

$events[] = [
'title' => $leave->user->name.' (Leave)',
'start' => $leave->start_date,
'end' => $leave->end_date,
'color' => 'orange'
];

}

/* Work From Home */

$wfh = \App\Models\WorkFromHome::with('user')->get();

foreach($wfh as $item){

$events[] = [
'title' => $item->user->name.' (WFH)',
'start' => $item->start_date,
'end' => $item->end_date,
'color' => 'blue'
];

}

/* Attendance */

$attendance = \App\Models\Attendance::with('user')->get();

foreach($attendance as $att){

$events[] = [
'title' => $att->user->name.' Present',
'start' => $att->clock_in,
'color' => 'green'
];

}

return response()->json($events);

}

public function attendanceCalendar(Request $request)
{

$month = $request->month ?? now()->format('Y-m');

$start = \Carbon\Carbon::parse($month.'-01')->startOfMonth();
$end   = \Carbon\Carbon::parse($month.'-01')->endOfMonth();

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/

$users = \App\Models\User::where('role','employee')
->orderBy('name','asc')
->get();

/*
|--------------------------------------------------------------------------
| Attendance (Optimized)
|--------------------------------------------------------------------------
*/

$attendances = \App\Models\Attendance::whereBetween('date',[$start,$end])
->get()
->groupBy(function($item){
    return $item->user_id.'_'.$item->date;
});

/*
|--------------------------------------------------------------------------
| Leaves
|--------------------------------------------------------------------------
*/

$leaves = \App\Models\Leave::where('status','approved')
->where(function($q) use ($start,$end){

$q->whereBetween('start_date',[$start,$end])
  ->orWhereBetween('end_date',[$start,$end]);

})
->get()
->groupBy('user_id');

/*
|--------------------------------------------------------------------------
| Holidays
|--------------------------------------------------------------------------
*/

$holidays = \App\Models\Holiday::with('users')->get();

/*
|--------------------------------------------------------------------------
| Work From Home
|--------------------------------------------------------------------------
*/

$wfhData = \App\Models\WorkFromHome::all()
->groupBy('user_id');

/*
|--------------------------------------------------------------------------
| Return View
|--------------------------------------------------------------------------
*/

return view('admin.attendance-calendar',compact(
'users',
'attendances',
'leaves',
'holidays',
'wfhData',
'start',
'end',
'month'
));

}


public function attendanceDetails($user,$date)
{

$record = \App\Models\Attendance::where('user_id',$user)
    ->whereDate('date',$date)
    ->first();

return response()->json($record);

}

public function monthlySummary(Request $request)
{

$month = $request->month ?? now()->format('Y-m');

$start = \Carbon\Carbon::parse($month.'-01')->startOfMonth();
$end   = \Carbon\Carbon::parse($month.'-01')->endOfMonth();

/* Employees */

$users = \App\Models\User::where('role','employee')
->orderBy('name','asc')
->get();

$data = [];

foreach($users as $user){

$present = \App\Models\Attendance::where('user_id',$user->id)
->whereBetween('date',[$start,$end])
->where('status','present')
->count();

$late = \App\Models\Attendance::where('user_id',$user->id)
->whereBetween('date',[$start,$end])
->where('status','late')
->count();

$halfday = \App\Models\Attendance::where('user_id',$user->id)
->whereBetween('date',[$start,$end])
->where('status','half_day')
->count();

$leave = \App\Models\Leave::where('user_id',$user->id)
->where('status','approved')
->whereBetween('start_date',[$start,$end])
->count();

$workingDays = $present + $late + $halfday + $leave;

$monthDays = $start->diffInWeekdays($end) + 1;

$absent = max($monthDays - $workingDays,0);

$attendancePercent = $monthDays > 0
? round(($workingDays / $monthDays) * 100)
: 0;

$data[] = [

'user' => $user,
'present' => $present,
'late' => $late,
'halfday' => $halfday,
'leave' => $leave,
'absent' => $absent,
'percent' => $attendancePercent

];

}

return view('admin.monthly-summary',compact(
'data',
'month'
));

}

    
}
