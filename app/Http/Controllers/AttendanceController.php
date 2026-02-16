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
    | EMPLOYEE SECTION
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        $records = Attendance::where('user_id', auth()->id())
            ->whereMonth('created_at', Carbon::parse($month)->month)
            ->whereYear('created_at', Carbon::parse($month)->year)
            ->orderByDesc('created_at')
            ->get();

        $active = Attendance::where('user_id', auth()->id())
            ->whereNull('clock_out')
            ->latest()
            ->first();

        return view('attendance.index', compact('records','active','month'));
    }

    public function clockIn(Request $request)
    {
        $active = Attendance::where('user_id', auth()->id())
            ->whereNull('clock_out')
            ->first();

        if ($active) {
            return response()->json(['error'=>'Already clocked in'], 400);
        }

        Attendance::create([
            'user_id' => auth()->id(),
            'clock_in' => now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);

        return response()->json(['success'=>true]);
    }

    public function clockOut()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereNull('clock_out')
            ->latest()
            ->first();

        if (!$attendance) {
            return response()->json(['error'=>'No active clock-in'], 400);
        }

        $attendance->clock_out = now();
        $attendance->total_hours =
            Carbon::parse($attendance->clock_in)
                ->floatDiffInHours(now());

        $attendance->save();

        return response()->json(['success'=>true]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN SECTION
    |--------------------------------------------------------------------------
    */

    public function adminIndex(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');

        $records = Attendance::with('user')
            ->whereMonth('created_at', Carbon::parse($month)->month)
            ->whereYear('created_at', Carbon::parse($month)->year)
            ->orderByDesc('created_at')
            ->get();

        $employees = User::where('role','employee')->get();

        return view('attendance.admin-index',
            compact('records','employees','month'));
    }

    public function create()
    {
        $employees = User::where('role','employee')->get();
        return view('attendance.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'clock_in' => 'required|date',
            'clock_out' => 'nullable|date|after:clock_in'
        ]);

        $hours = null;

        if ($request->clock_out) {
            $hours = Carbon::parse($request->clock_in)
                ->floatDiffInHours($request->clock_out);
        }

        Attendance::create([
            'user_id' => $request->user_id,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'total_hours' => $hours
        ]);

        return redirect()->route('admin.attendance.index')
            ->with('success','Attendance Added Successfully');
    }

    public function edit($id)
    {
        $attendance = Attendance::findOrFail($id);
        $employees = User::where('role','employee')->get();

        return view('attendance.edit',
            compact('attendance','employees'));
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'clock_in' => 'required|date',
            'clock_out' => 'nullable|date|after:clock_in'
        ]);

        $hours = null;

        if ($request->clock_out) {
            $hours = Carbon::parse($request->clock_in)
                ->floatDiffInHours($request->clock_out);
        }

        $attendance->update([
            'user_id' => $request->user_id,
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'total_hours' => $hours
        ]);

        return redirect()->route('admin.attendance.index')
            ->with('success','Attendance Updated Successfully');
    }

    public function destroy($id)
    {
        Attendance::findOrFail($id)->delete();

        return back()->with('success','Attendance Deleted');
    }
}
