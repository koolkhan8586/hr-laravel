<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\WeeklySchedule;
use Illuminate\Http\Request;

class WeeklyScheduleController extends Controller
{
    public function create()
    {
        $users = User::all();
        $shifts = Shift::all();

        return view('admin.weekly.create', compact('users','shifts'));
    }

    public function index()
{
    $schedules = \App\Models\WeeklySchedule::with(['user','shift'])->get();

    return view('admin.weekly.index', compact('schedules'));
}
    
    public function store(Request $request)
    {
        $days = [
            'Monday','Tuesday','Wednesday',
            'Thursday','Friday','Saturday','Sunday'
        ];

        foreach ($request->users as $user) {

            foreach ($days as $day) {

                WeeklySchedule::updateOrCreate(
                    [
                        'user_id' => $user,
                        'day_of_week' => $day
                    ],
                    [
                        'shift_id' => $request->$day
                    ]
                );
            }
        }

        return back()->with('success','Weekly schedule saved');
    }
}
