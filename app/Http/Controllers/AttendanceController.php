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
        $month = $request->month ?? now('Asia/Karachi')->format('Y-m');

        $monthCarbon = Carbon::parse($month);

        $records = Attendance::where('user_id', auth()->id())
            ->whereMonth('clock_in', $monthCarbon->month)
            ->whereYear('clock_in', $monthCarbon->year)
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
    | Clock In (Pakistan Time + Late Detection)
    |--------------------------------------------------------------------------
    */
    public function clockIn(Request $request)
    {
        $now = Carbon::now('Asia/Karachi');
        $today = $now->toDateString();

        $exists = Attendance::where('user_id', auth()->id())
            ->whereDate('clock_in', $today)
            ->exists();

        if ($exists) {
            return response()->json(['message'=>'Already clocked in'], 400);
        }

        // HR Rules
        $lateAfter = Carbon::createFromTime(9, 45, 0, 'Asia/Karachi');
        $status = $now->gt($lateAfter) ? 'late' : 'present';

        Attendance::create([
            'user_id'   => auth()->id(),
            'clock_in'  => $now,
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude,
            'status'    => $status,
        ]);

        return response()->json(['success'=>true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Clock Out (Half Day Detection)
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

        $now = Carbon::now('Asia/Karachi');

        $attendance->clock_out = $now;

        $hours = Carbon::parse($attendance->clock_in)
            ->diffInMinutes($now) / 60;

        $attendance->total_hours = round($hours,2);

        // Working Hours Rule Engine
        if ($hours < 4) {
            $attendance->status = 'half_day';
        } elseif ($attendance->status !== 'late') {
            $attendance->status = 'present';
        }

        $attendance->save();

        return response()->json(['success'=>true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Edit Attendance
    |--------------------------------------------------------------------------
    */
    public function edit($id)
    {
        $attendance = Attendance::with('user')->findOrFail($id);
        return view('attendance.edit', compact('attendance'));
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Update Attendance
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $request->validate([
            'clock_in'  => 'required|date',
            'clock_out' => 'nullable|date|after_or_equal:clock_in',
        ]);

        $clockIn  = Carbon::parse($request->clock_in, 'Asia/Karachi');
        $clockOut = $request->clock_out
            ? Carbon::parse($request->clock_out, 'Asia/Karachi')
            : null;

        $totalHours = null;
        $status = 'present';

        if ($clockOut) {
            $totalHours = $clockIn->diffInMinutes($clockOut) / 60;

            if ($totalHours < 4) {
                $status = 'half_day';
            }
        }

        // Late detection
        if ($clockIn->format('H:i:s') > '09:15:00') {
            $status = 'late';
        }

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
    | Admin Dashboard (Latest on Top)
    |--------------------------------------------------------------------------
    */
    public function adminIndex(Request $request)
    {
        $month = $request->month ?? now('Asia/Karachi')->format('Y-m');
        $monthCarbon = Carbon::parse($month);

        $records = Attendance::with('user')
            ->whereMonth('clock_in', $monthCarbon->month)
            ->whereYear('clock_in', $monthCarbon->year)
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
    | Attendance Percentage Calculator
    |--------------------------------------------------------------------------
    */
    public function percentage($userId, $month)
    {
        $monthCarbon = Carbon::parse($month);
        $totalDays = $monthCarbon->daysInMonth;

        $presentDays = Attendance::where('user_id',$userId)
            ->whereMonth('clock_in',$monthCarbon->month)
            ->whereYear('clock_in',$monthCarbon->year)
            ->whereIn('status',['present','late','half_day'])
            ->count();

        return round(($presentDays / $totalDays) * 100,2);
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

    /*
    |--------------------------------------------------------------------------
    | Monthly Analytics API
    |--------------------------------------------------------------------------
    */
    public function analytics($month)
    {
        $monthCarbon = Carbon::parse($month);

        $data = Attendance::selectRaw('status, COUNT(*) as total')
            ->whereMonth('clock_in',$monthCarbon->month)
            ->whereYear('clock_in',$monthCarbon->year)
            ->groupBy('status')
            ->pluck('total','status');

        return response()->json($data);
    }
}
