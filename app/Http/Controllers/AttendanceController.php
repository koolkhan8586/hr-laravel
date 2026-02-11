<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceNotification;
use Illuminate\Support\Facades\Mail;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
{
    $month = $request->month ?? now()->format('Y-m');

    $records = Attendance::where('user_id', auth()->id())
        ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])
        ->orderBy('created_at', 'desc')
        ->get();

    $active = Attendance::where('user_id', auth()->id())
    ->whereNull('clock_out')
    ->latest('clock_in')
    ->first();


    return view('attendance.index', compact('records', 'month', 'active'));
}

public function clockIn(Request $request)
{
    $alreadyClocked = Attendance::where('user_id', auth()->id())
        ->whereDate('clock_in', today())
        ->exists();

    if ($alreadyClocked) {
        return response()->json(['error' => 'Already clocked in today'], 400);
    }

    $attendance = Attendance::create([
        'user_id' => auth()->id(),
        'clock_in' => now(),
        'latitude' => $request->latitude,
        'longitude' => $request->longitude
    ]);

    Mail::to(auth()->user()->email)
        ->send(new AttendanceNotification('Clock In', $attendance));

    return response()->json(['success' => true]);
}

public function clockOut()
{
    $attendance = Attendance::where('user_id', auth()->id())
        ->whereNull('clock_out')
        ->latest('clock_in')
        ->first();

    if (!$attendance) {
        return response()->json(['error' => 'No active clock-in found'], 400);
    }

    $attendance->clock_out = now();

    $attendance->total_hours =
        \Carbon\Carbon::parse($attendance->clock_in)
            ->diffInMinutes(now()) / 60;

    $attendance->save();

    return response()->json(['success' => true]);
}

}
