<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
   

    public function index()
    {
        $staff = Staff::with('user')->get();
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        return view('staff.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'employee_id' => 'required|unique:staff,employee_id',
            'department' => 'required',
            'designation' => 'required',
            'salary' => 'required|numeric',
            'joining_date' => 'required|date'
        ]);

        $password = '12345678';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'role' => 'employee'
        ]);

        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $request->employee_id,
            'department' => $request->department,
            'designation' => $request->designation,
            'salary' => $request->salary,
            'joining_date' => $request->joining_date
        ]);

        return redirect()->route('staff.index')->with('success', 'Staff Created Successfully');
    }
}
