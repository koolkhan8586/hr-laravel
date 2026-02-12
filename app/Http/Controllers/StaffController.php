<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

        $password = '12345678'; // 🔒 your default password

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'role' => 'employee',
            'annual_leave_balance' => 14
        ]);

        // Create Staff
        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $request->employee_id,
            'department' => $request->department,
            'designation' => $request->designation,
            'salary' => $request->salary,
            'joining_date' => $request->joining_date
        ]);

        // Send Email
        try {
            Mail::raw(
                "Welcome to HR Management System

Login URL: " . url('/login') . "

Email: {$request->email}
Password: {$password}

Please change your password after login.",
                function ($message) use ($request) {
                    $message->to($request->email)
                            ->subject('Your HR Account Created');
                }
            );
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

        return redirect()->route('staff.index')
            ->with('success', 'Staff Created Successfully');
    }
}
