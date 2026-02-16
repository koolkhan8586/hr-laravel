<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Employee Clock In / Clock Out
    |--------------------------------------------------------------------------
    */

    public function clockIn(Request $request)
    {
        $today = Carbon::today();

        // Prevent double clock in same day
        $exists = Attendance::where('user_id', auth()->id())
            ->whereDate('clock_in', $today)
            ->exists();

        if ($exists) {
            return back()->with('error','Already clocked in today.');
        }

        Attendance::create([
            'user_id' => auth()->id(),
            'clock_in' => now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);

        return back()->with('success','Clocked In Successfully');
    }

    public function clockOut()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('clock_in', Carbon::today())
            ->first();

        if (!$attendance) {
            return back()->with('error','No clock-in found.');
        }

        if ($attendance->clock_out) {
            return back()->with('error','Already clocked out.');
        }

        $attendance->clock_out = now();

        // Calculate total hours
        $hours = Carbon::parse($attendance->clock_in)
                    ->diffInMinutes($attendance->clock_out) / 60;

        $attendance->total_hours = round($hours,2);

        $attendance->save();

        return back()->with('success','Clocked Out Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Attendance Dashboard
    |--------------------------------------------------------------------------
    */

    public function adminIndex()
    {
        $attendances = Attendance::with('user')
            ->orderByDesc('clock_in') // latest on top
            ->get();

        return view('attendance.admin-index', compact('attendances'));
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Add Attendance
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $employees = User::where('role','employee')->get();
        return view('attendance.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'clock_in' => 'required|date',
            'clock_out' => 'nullable|date'
        ]);

        $totalHours = null;

        if ($request->clock_out) {
            $totalHours = Carbon::parse($request->clock_in)
                ->diffInMinutes(Carbon::parse($request->clock_out)) / 60;
        }

        Attendance::create([
            'user_id' => $request->user_id,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'total_hours' => round($totalHours,2)
        ]);

        return redirect()->route('admin.attendance.index')
            ->with('success','Attendance Added Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Attendance
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $attendance = Attendance::findOrFail($id);
        $employees = User::where('role','employee')->get();

        return view('attendance.edit', compact('attendance','employees'));
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $totalHours = null;

        if ($request->clock_out) {
            $totalHours = Carbon::parse($request->clock_in)
                ->diffInMinutes(Carbon::parse($request->clock_out)) / 60;
        }

        $attendance->update([
            'user_id' => $request->user_id,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'total_hours' => round($totalHours,2)
        ]);

        return redirect()->route('admin.attendance.index')
            ->with('success','Attendance Updated');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Attendance
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        Attendance::findOrFail($id)->delete();

        return back()->with('success','Attendance Deleted');
    }
}
