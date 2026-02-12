<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StaffController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Staff List
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $staff = Staff::with('user')->get();
        return view('staff.index', compact('staff'));
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('staff.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Staff
    |--------------------------------------------------------------------------
    */
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

        // Default password (NOT changing as you requested)
        $password = '12345678';

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'role' => 'employee',
            'annual_leave_balance' => 14
        ]);

        // Create Staff Record
        Staff::create([
            'user_id' => $user->id,
            'employee_id' => $request->employee_id,
            'department' => $request->department,
            'designation' => $request->designation,
            'salary' => $request->salary,
            'joining_date' => $request->joining_date
        ]);

        // Send Email with Credentials
        Mail::raw(
            "Welcome to HR Management System\n\n".
            "Login URL: " . url('/login') . "\n".
            "Email: {$user->email}\n".
            "Password: {$password}\n\n".
            "Please change your password after login.",
            function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Your HR System Login Credentials');
            }
        );

        return redirect()->route('staff.index')
            ->with('success', 'Staff Created & Email Sent Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Staff
    |--------------------------------------------------------------------------
    */
    public function edit($id)
    {
        $staff = Staff::with('user')->findOrFail($id);
        return view('staff.edit', compact('staff'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Staff
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $staff->user_id,
            'department' => 'required',
            'designation' => 'required',
            'salary' => 'required|numeric',
        ]);

        // Update User
        $staff->user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Update Staff
        $staff->update([
            'department' => $request->department,
            'designation' => $request->designation,
            'salary' => $request->salary,
        ]);

        return redirect()->route('staff.index')
            ->with('success', 'Staff Updated Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Reset Password
    |--------------------------------------------------------------------------
    */
    public function resetPassword($id)
    {
        $staff = Staff::findOrFail($id);
        $user = $staff->user;

        $newPassword = '12345678';

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        Mail::raw(
            "Your password has been reset.\n\n".
            "Login URL: " . url('/login') . "\n".
            "Email: {$user->email}\n".
            "New Password: {$newPassword}",
            function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Password Reset');
            }
        );

        return back()->with('success', 'Password Reset & Email Sent');
    }

    public function destroy($id)
{
    $staff = Staff::findOrFail($id);

    // Delete related user
    $staff->user()->delete();

    // Delete staff record
    $staff->delete();

    return redirect()->route('staff.index')
        ->with('success', 'Staff Deleted Successfully');
}

}
