<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Leave;
use App\Exports\StaffMonthlyReportExport;
use Maatwebsite\Excel\Facades\Excel;
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
    ->get()
    ->unique('user_id');


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
    ->tracked()
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

$clockIn = Carbon::parse($date . ' ' . $request->clock_in)
    ->setTimezone('Asia/Karachi');

$clockOut = $request->clock_out
    ? Carbon::parse($date . ' ' . $request->clock_out)
        ->setTimezone('Asia/Karachi')
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
        ->get()
        ->unique('user_id');

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
->get()
->unique('user_id');

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

/*
|--------------------------------------------------------------------------
| Holiday Users
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Weekend Check
|--------------------------------------------------------------------------
*/

$isWeekend = \Carbon\Carbon::parse($today)->isWeekend();

/*
|--------------------------------------------------------------------------
| Absent Records
|--------------------------------------------------------------------------
*/

if(!$isWeekend){

$records = User::where('role','employee')
->tracked()
->whereNotIn('id',$attendanceUsers)
->whereNotIn('id',$leaveUsers)
->whereNotIn('id',$wfhUsers)
->whereNotIn('id',$holidayUsers)
->get();

}else{

$records = collect();

}

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

$attendance = \App\Models\Attendance::with('user')
    ->whereDate('date', now()->toDateString())
    ->orderBy('clock_in', 'asc')
    ->get()
    ->unique('user_id');

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

    // Payroll-only staff never mark attendance, so a calendar of theirs would
    // be a wall of crosses. They come back with ?include_payroll_only=1.
    $includePayrollOnly = $request->boolean('include_payroll_only');

    $users = \App\Models\User::where('role','employee')
        ->when(!$includePayrollOnly, fn ($q) => $q->tracked())
        ->orderBy('name','asc')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | 🔥 NEW: All Employees (for toggle filter)
    |--------------------------------------------------------------------------
    */

    $allEmployees = \App\Models\User::where('role','employee')
        ->when(!$includePayrollOnly, fn ($q) => $q->tracked())
        ->orderBy('name','asc')
        ->get();

    $payrollOnlyCount = \App\Models\User::where('role','employee')->payrollOnly()->count();

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
    | FINAL RETURN (ONLY ONE)
    |--------------------------------------------------------------------------
    */

    return view('admin.attendance-calendar', compact(
        'users',
        'allEmployees', // 🔥 NEW
        'attendances',
        'leaves',
        'holidays',
        'wfhData',
        'start',
        'end',
        'month',
        'includePayrollOnly',
        'payrollOnlyCount'
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

$includePayrollOnly = $request->boolean('include_payroll_only');

$users = \App\Models\User::where('role','employee')
->when(!$includePayrollOnly, fn ($q) => $q->tracked())
->orderBy('name','asc')
->get();

$payrollOnlyCount = \App\Models\User::where('role','employee')->payrollOnly()->count();

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
'month',
'includePayrollOnly',
'payrollOnlyCount'
));

}

/*
|--------------------------------------------------------------------------
| Staff-wise Late Arrivals (Month)
|--------------------------------------------------------------------------
*/
public function staffLateReport(Request $request)
{
    $report = $this->buildStaffLateReport(
        $request->month ?? now()->format('Y-m'),
        $request->boolean('include_payroll_only')
    );

    return view('admin.reports.staff-late', $report);
}

public function exportStaffLateReport(Request $request)
{
    $report = $this->buildStaffLateReport(
        $request->month ?? now()->format('Y-m'),
        $request->boolean('include_payroll_only')
    );

    $rows = [];
    foreach ($report['data'] as $index => $row) {
        $details = $row['records']->map(function ($record) {
            $date = Carbon::parse($record->date)->format('d M Y');
            $time = $record->clock_in
                ? Carbon::parse($record->clock_in)->format('h:i A')
                : '-';

            return $date.' '.$time;
        })->implode('; ');

        $rows[] = [
            $index + 1,
            $row['user']->name,
            $row['user']->staff->employee_id ?? '-',
            $row['user']->staff->department ?? '-',
            $row['count'],
            $details ?: 'No late arrivals',
        ];
    }

    $filename = 'staff_late_'.$report['month'].'.xlsx';

    return Excel::download(
        new StaffMonthlyReportExport(
            ['#', 'Employee', 'Employee ID', 'Department', 'Late Count', 'Late Dates / Clock In'],
            $rows
        ),
        $filename
    );
}

/*
|--------------------------------------------------------------------------
| Staff-wise Absences (Month)
|--------------------------------------------------------------------------
*/
public function staffAbsentReport(Request $request)
{
    $report = $this->buildStaffAbsentReport(
        $request->month ?? now()->format('Y-m'),
        $request->boolean('include_payroll_only')
    );

    return view('admin.reports.staff-absent', $report);
}

public function exportStaffAbsentReport(Request $request)
{
    $report = $this->buildStaffAbsentReport(
        $request->month ?? now()->format('Y-m'),
        $request->boolean('include_payroll_only')
    );

    $rows = [];
    foreach ($report['data'] as $index => $row) {
        $details = collect($row['dates'])
            ->map(fn ($d) => Carbon::parse($d)->format('d M Y'))
            ->implode('; ');

        $rows[] = [
            $index + 1,
            $row['user']->name,
            $row['user']->staff->employee_id ?? '-',
            $row['user']->staff->department ?? '-',
            $row['count'],
            $details ?: 'No absences',
        ];
    }

    $filename = 'staff_absence_'.$report['month'].'.xlsx';

    return Excel::download(
        new StaffMonthlyReportExport(
            ['#', 'Employee', 'Employee ID', 'Department', 'Absent Days', 'Absent Dates'],
            $rows
        ),
        $filename
    );
}

/*
|--------------------------------------------------------------------------
| Staff-wise Leave (Month)
|--------------------------------------------------------------------------
*/
public function staffLeaveReport(Request $request)
{
    $report = $this->buildStaffLeaveReport(
        $request->month ?? now()->format('Y-m'),
        $request->boolean('include_payroll_only')
    );

    return view('admin.reports.staff-leave', $report);
}

public function exportStaffLeaveReport(Request $request)
{
    $report = $this->buildStaffLeaveReport(
        $request->month ?? now()->format('Y-m'),
        $request->boolean('include_payroll_only')
    );

    $rows = [];
    foreach ($report['data'] as $index => $row) {
        $details = collect($row['leaves'])->map(function ($item) {
            $leave = $item['leave'];

            return ucfirst(str_replace('_', ' ', $leave->type)).' '.
                Carbon::parse($leave->start_date)->format('d M Y').' to '.
                Carbon::parse($leave->end_date)->format('d M Y').
                ' ('.$item['days_in_month'].' days)';
        })->implode('; ');

        $rows[] = [
            $index + 1,
            $row['user']->name,
            $row['user']->staff->employee_id ?? '-',
            $row['user']->staff->department ?? '-',
            $row['count'],
            $row['days'],
            $details ?: 'No leave',
        ];
    }

    $filename = 'staff_leave_'.$report['month'].'.xlsx';

    return Excel::download(
        new StaffMonthlyReportExport(
            ['#', 'Employee', 'Employee ID', 'Department', 'Applications', 'Days in Month', 'Leave Details'],
            $rows
        ),
        $filename
    );
}

/**
 * Employees an attendance report should cover.
 *
 * Payroll-only staff are left out unless they are asked for, so they stop
 * filling the reports with absences they were never expected to avoid.
 */
protected function reportRoster(bool $includePayrollOnly = false)
{
    $query = User::where('role', 'employee')->with('staff');

    if (!$includePayrollOnly) {
        $query->tracked();
    }

    return $query->orderBy('name')->get();
}

/** How many employees the payroll-only filter is currently hiding. */
protected function payrollOnlyCount(): int
{
    return User::where('role', 'employee')->payrollOnly()->count();
}

protected function buildStaffLateReport(string $month, bool $includePayrollOnly = false): array
{
    $start = Carbon::parse($month.'-01')->startOfMonth();
    $end   = Carbon::parse($month.'-01')->endOfMonth();

    $users = $this->reportRoster($includePayrollOnly);

    $lateRecords = Attendance::with('user')
        ->whereBetween('date', [$start, $end])
        ->where('status', 'late')
        ->orderBy('date')
        ->get()
        ->groupBy('user_id');

    $data = [];
    $totalLate = 0;

    foreach ($users as $user) {
        $records = $lateRecords->get($user->id, collect());
        $count = $records->count();
        $totalLate += $count;

        $data[] = [
            'user' => $user,
            'count' => $count,
            'records' => $records,
        ];
    }

    usort($data, fn ($a, $b) => $b['count'] <=> $a['count']);

    return [
        'data' => $data,
        'month' => $month,
        'totalLate' => $totalLate,
        'staffWithLate' => collect($data)->where('count', '>', 0)->count(),
        'includePayrollOnly' => $includePayrollOnly,
        'payrollOnlyCount' => $this->payrollOnlyCount(),
    ];
}

protected function buildStaffAbsentReport(string $month, bool $includePayrollOnly = false): array
{
    $start = Carbon::parse($month.'-01')->startOfMonth();
    $end   = Carbon::parse($month.'-01')->endOfMonth();

    // Do not count future weekdays in the current month
    $rangeEnd = $end->copy();
    if ($end->isFuture()) {
        $rangeEnd = now()->copy()->startOfDay();
    }

    $users = $this->reportRoster($includePayrollOnly);

    $employeeIds = $users->pluck('id');

    // Compare plain dates. Handing a datetime to a date column makes the first
    // of the month fall outside the range on some drivers, which then reads as
    // an absence for everybody.
    $attendanceByUserDate = Attendance::whereBetween('date', [
            $start->toDateString(),
            $rangeEnd->toDateString(),
        ])
        ->whereIn('user_id', $employeeIds)
        ->get()
        ->groupBy(fn ($row) => $row->user_id.'_'.Carbon::parse($row->date)->toDateString());

    $leaves = Leave::where('status', 'approved')
        ->whereDate('start_date', '<=', $rangeEnd)
        ->whereDate('end_date', '>=', $start)
        ->whereIn('user_id', $employeeIds)
        ->get();

    $wfhList = \App\Models\WorkFromHome::whereDate('start_date', '<=', $rangeEnd)
        ->whereDate('end_date', '>=', $start)
        ->whereIn('user_id', $employeeIds)
        ->get();

    $holidays = \App\Models\Holiday::with('users')
        ->whereDate('start_date', '<=', $rangeEnd)
        ->whereDate('end_date', '>=', $start)
        ->get();

    $absencesByUser = [];
    foreach ($users as $user) {
        $absencesByUser[$user->id] = [];
    }

    $day = $start->copy();
    while ($day->lte($rangeEnd)) {
        if ($day->isWeekend()) {
            $day->addDay();
            continue;
        }

        $dateStr = $day->toDateString();

        $holidayUsers = [];
        foreach ($holidays as $holiday) {
            $hStart = Carbon::parse($holiday->start_date)->startOfDay();
            $hEnd = Carbon::parse($holiday->end_date)->startOfDay();
            if ($day->lt($hStart) || $day->gt($hEnd)) {
                continue;
            }

            if ((int) $holiday->for_all === 1) {
                $holidayUsers = $employeeIds->all();
                break;
            }

            foreach ($holiday->users as $u) {
                $holidayUsers[] = $u->id;
            }
        }
        $holidayUsers = array_unique($holidayUsers);

        $leaveUsers = $leaves
            ->filter(fn ($leave) =>
                Carbon::parse($leave->start_date)->lte($day) &&
                Carbon::parse($leave->end_date)->gte($day)
            )
            ->pluck('user_id')
            ->all();

        $wfhUsers = $wfhList
            ->filter(fn ($wfh) =>
                Carbon::parse($wfh->start_date)->lte($day) &&
                Carbon::parse($wfh->end_date)->gte($day)
            )
            ->pluck('user_id')
            ->all();

        foreach ($users as $user) {
            if (in_array($user->id, $holidayUsers, true)) {
                continue;
            }
            if (in_array($user->id, $leaveUsers, true)) {
                continue;
            }
            if (in_array($user->id, $wfhUsers, true)) {
                continue;
            }

            $key = $user->id.'_'.$dateStr;
            $attendance = $attendanceByUserDate->get($key);

            if ($attendance && $attendance->isNotEmpty()) {
                $status = $attendance->first()->status;
                // Explicit absent rows count; present/late/half_day do not
                if ($status === 'absent') {
                    $absencesByUser[$user->id][] = $dateStr;
                }
                continue;
            }

            $absencesByUser[$user->id][] = $dateStr;
        }

        $day->addDay();
    }

    $data = [];
    $totalAbsent = 0;

    foreach ($users as $user) {
        $dates = $absencesByUser[$user->id] ?? [];
        $count = count($dates);
        $totalAbsent += $count;

        $data[] = [
            'user' => $user,
            'count' => $count,
            'dates' => $dates,
        ];
    }

    usort($data, fn ($a, $b) => $b['count'] <=> $a['count']);

    return [
        'data' => $data,
        'month' => $month,
        'totalAbsent' => $totalAbsent,
        'staffWithAbsent' => collect($data)->where('count', '>', 0)->count(),
        'includePayrollOnly' => $includePayrollOnly,
        'payrollOnlyCount' => $this->payrollOnlyCount(),
    ];
}

protected function buildStaffLeaveReport(string $month, bool $includePayrollOnly = false): array
{
    $start = Carbon::parse($month.'-01')->startOfMonth();
    $end   = Carbon::parse($month.'-01')->endOfMonth();

    $users = $this->reportRoster($includePayrollOnly);

    $leaves = Leave::with('user')
        ->where('status', 'approved')
        ->whereDate('start_date', '<=', $end)
        ->whereDate('end_date', '>=', $start)
        ->orderBy('start_date')
        ->get()
        ->groupBy('user_id');

    $data = [];
    $totalDays = 0;
    $totalApplications = 0;

    foreach ($users as $user) {
        $userLeaves = $leaves->get($user->id, collect());
        $leaveRows = [];
        $daysInMonth = 0;

        foreach ($userLeaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->startOfDay();
            $leaveEnd = Carbon::parse($leave->end_date)->startOfDay();

            $overlapStart = $leaveStart->greaterThan($start) ? $leaveStart->copy() : $start->copy();
            $overlapEnd = $leaveEnd->lessThan($end) ? $leaveEnd->copy() : $end->copy();

            if ($leave->duration_type === 'half_day') {
                $days = 0.5;
            } else {
                $days = $overlapStart->diffInDays($overlapEnd) + 1;
            }

            $daysInMonth += $days;
            $leaveRows[] = [
                'leave' => $leave,
                'days_in_month' => $days,
            ];
        }

        $totalDays += $daysInMonth;
        $totalApplications += count($leaveRows);

        $data[] = [
            'user' => $user,
            'count' => count($leaveRows),
            'days' => $daysInMonth,
            'leaves' => $leaveRows,
        ];
    }

    usort($data, fn ($a, $b) => $b['days'] <=> $a['days']);

    return [
        'data' => $data,
        'month' => $month,
        'totalDays' => $totalDays,
        'totalApplications' => $totalApplications,
        'staffOnLeave' => collect($data)->where('count', '>', 0)->count(),
        'includePayrollOnly' => $includePayrollOnly,
        'payrollOnlyCount' => $this->payrollOnlyCount(),
    ];
}

    public function liveMap()
{
    $employees = \App\Models\Attendance::with('user')
        ->whereDate('date', now()->toDateString())
        ->whereNotNull('clock_in_latitude')
        ->get();

    $locations = \App\Models\OfficeLocation::all(); // ✅ ADD THIS

    return view('admin.attendance.live-map', compact('employees','locations'));
}
    
}
