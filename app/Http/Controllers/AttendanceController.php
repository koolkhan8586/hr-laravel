<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;

class AttendanceController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Employee Attendance View
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        $records = Attendance::where('user_id', auth()->id())
            ->whereMonth('clock_in', Carbon::parse($month)->month)
            ->whereYear('clock_in', Carbon::parse($month)->year)
            ->orderByDesc('clock_in')
            ->get();

        $active = Attendance::where('user_id', auth()->id())
            ->whereNull('clock_out')
            ->latest()
            ->first();

        return view('attendance.index', compact('records','month','active'));
    }

    /*
    |--------------------------------------------------------------------------
    | Clock In
    |--------------------------------------------------------------------------
    */
    public function clockIn(Request $request)
    {
        $today = now()->toDateString();

        $exists = Attendance::where('user_id', auth()->id())
            ->whereDate('clock_in', $today)
            ->exists();

        if ($exists) {
            return response()->json(['message'=>'Already clocked in'], 400);
        }

        $clockIn = now();

        // Late detection (after 9:15 AM)
        $lateTime = Carbon::createFromTime(9,30,0);

        $isLate = $clockIn->gt($lateTime);

        Attendance::create([
            'user_id' => auth()->id(),
            'clock_in' => $clockIn,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_late' => $isLate,
            'status' => $isLate ? 'late' : 'present'
        ]);

        return response()->json(['success'=>true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Clock Out
    |--------------------------------------------------------------------------
    */
    public function clockOut()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereNull('clock_out')
            ->latest()
            ->first();

        if (!$attendance) {
            return response()->json(['message'=>'No active clock in'], 400);
        }

        $attendance->clock_out = now();

        $hours = Carbon::parse($attendance->clock_in)
            ->diffInMinutes(now()) / 60;

        $attendance->total_hours = round($hours,2);
        $attendance->save();

        return response()->json(['success'=>true]);
    }

    public function edit($id)
{
    $attendance = \App\Models\Attendance::with('user')->findOrFail($id);

    return view('attendance.edit', compact('attendance'));
}

    public function update(Request $request, $id)
{
    $attendance = \App\Models\Attendance::findOrFail($id);

    $request->validate([
        'clock_in'  => 'required|date',
        'clock_out' => 'nullable|date|after_or_equal:clock_in',
    ]);

    $clockIn  = \Carbon\Carbon::parse($request->clock_in);
    $clockOut = $request->clock_out
        ? \Carbon\Carbon::parse($request->clock_out)
        : null;

    $totalHours = null;

    if ($clockOut) {
        $totalHours = $clockIn->diffInMinutes($clockOut) / 60;
    }

    // Late detection (after 9:00 AM)
    $status = $clockIn->format('H:i:s') > '09:00:00' ? 'late' : 'present';

    $attendance->update([
        'clock_in'    => $clockIn,
        'clock_out'   => $clockOut,
        'total_hours' => $totalHours,
        'status'      => $status,
    ]);

    return redirect()->route('admin.attendance.index')
        ->with('success', 'Attendance updated successfully');
}


    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */
    public function adminIndex(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        $records = Attendance::with('user')
            ->whereMonth('clock_in', Carbon::parse($month)->month)
            ->whereYear('clock_in', Carbon::parse($month)->year)
            ->orderByDesc('clock_in')
            ->get();

        return view('attendance.admin-index', compact('records','month'));
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Attendance
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        Attendance::findOrFail($id)->delete();
        return back()->with('success','Attendance deleted');
    }

    /*
    |--------------------------------------------------------------------------
    | Attendance Percentage
    |--------------------------------------------------------------------------
    */
    public function percentage($userId, $month)
    {
        $monthCarbon = Carbon::parse($month);

        $totalDays = $monthCarbon->daysInMonth;

        $present = Attendance::where('user_id',$userId)
            ->whereMonth('clock_in',$monthCarbon->month)
            ->whereYear('clock_in',$monthCarbon->year)
            ->count();

        return round(($present / $totalDays) * 100,2);
    }

    /*
    |--------------------------------------------------------------------------
    | Export Excel
    |--------------------------------------------------------------------------
    */
    public function export(Request $request)
    {
        return Excel::download(
            new AttendanceExport($request->month),
            'attendance.xlsx'
        );
    }
}
