<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\EmployeeSchedule;
use Illuminate\Http\Request;

class EmployeeScheduleController extends Controller
{
    public function index()
    {
        $schedules = EmployeeSchedule::with(['user','shift'])->latest()->get();

        return view('admin.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $users = User::all();
        $shifts = Shift::all();

        return view('admin.schedules.create', compact('users','shifts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'=>'required',
            'date'=>'required',
        ]);

        EmployeeSchedule::create($request->all());

        return redirect()->route('schedules.index')
        ->with('success','Schedule assigned successfully');
    }
}
