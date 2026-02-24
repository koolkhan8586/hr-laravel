<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StaffSampleExport;
use App\Exports\StaffExport;

class StaffController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Staff List
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Staff::with('user');

        if ($request->search) {
    $query->whereHas('user', function ($q) use ($request) {
        $q->where('name', 'like', '%' . $request->search . '%')
          ->orWhere('employee_code', 'like', '%' . $request->search . '%');
    });
}

        if ($request->department) {
            $query->where('department', $request->department);
        }

        $staff = $query->orderByDesc('created_at')->get();

        return view('staff.index', compact('staff'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create Staff
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('staff.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Store Staff
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users,email',
        'department' => 'required',
        'designation' => 'required',
        'salary' => 'required|numeric',
        'joining_date' => 'required|date'
    ]);

    // AUTO GENERATE EMPLOYEE CODE
    $lastUser = User::whereNotNull('employee_code')
        ->orderByDesc('id')
        ->first();

    if ($lastUser && preg_match('/EMP(\d+)/', $lastUser->employee_code, $matches)) {
        $nextNumber = (int)$matches[1] + 1;
    } else {
        $nextNumber = 1;
    }

    $employeeCode = 'EMP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    $password = \Str::random(8);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'employee_code' => $employeeCode,
        'password' => \Hash::make($password),
        'role' => 'employee',
        'force_password_change' => true
    ]);

    Staff::create([
        'user_id' => $user->id,
        'department' => $request->department,
        'designation' => $request->designation,
        'salary' => $request->salary,
        'joining_date' => $request->joining_date,
        'status' => 'active'
    ]);
    
    // Send Welcome Email
        Mail::raw(
            "Welcome to HR System\n\nLogin URL: " . url('/login') .
            "\nEmployee Code: " . $request->employee_code .
            "\nEmail: " . $request->email .
            "\nPassword: " . $password,
            function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Welcome to HR System');
            }
        );

        return redirect()->route('admin.staff.index')
        ->with('success', 'Staff Added Successfully (Code: '.$employeeCode.')');
    }

    /*
    |--------------------------------------------------------------------------
    | Download Sample File
    |--------------------------------------------------------------------------
    */
    public function downloadSample()
    {
        return Excel::download(new StaffSampleExport, 'staff_sample.xlsx');
    }

    /*
    |--------------------------------------------------------------------------
    | Import Staff
    |--------------------------------------------------------------------------
    */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        Excel::import(new \App\Imports\StaffImport, $request->file('file'));

        return back()->with('success', 'Staff Imported Successfully');
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
        $staff = Staff::with('user')->findOrFail($id);

        $request->validate([
            'name'          => 'required',
            'email'         => 'required|email|unique:users,email,' . $staff->user->id,
            'employee_code' => 'required|unique:users,employee_code,' . $staff->user->id,
            'department'    => 'required',
            'designation'   => 'required',
            'salary'        => 'required|numeric'
        ]);

        // Update User Table
        $staff->user->update([
            'name'          => $request->name,
            'email'         => $request->email,
            'employee_code' => strtoupper($request->employee_code),
        ]);

        // Update Staff Table
        $staff->update([
            'department'  => $request->department,
            'designation' => $request->designation,
            'salary'      => $request->salary
        ]);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff Updated Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Export Staff
    |--------------------------------------------------------------------------
    */
    public function export()
    {
        return Excel::download(new StaffExport, 'staff_list.xlsx');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Staff
    |--------------------------------------------------------------------------
    */
    public function destroy($id)
    {
        $staff = Staff::findOrFail($id);

        $staff->user()->delete();
        $staff->delete();

        return back()->with('success', 'Staff Deleted Successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Status
    |--------------------------------------------------------------------------
    */
    public function toggleStatus($id)
    {
        $staff = Staff::findOrFail($id);

        $staff->update([
            'status' => $staff->status == 'active' ? 'inactive' : 'active'
        ]);

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Delete
    |--------------------------------------------------------------------------
    */
    public function bulkDelete(Request $request)
    {
        if (!$request->staff_ids) {
            return back()->with('error', 'No staff selected.');
        }

        foreach ($request->staff_ids as $id) {
            $staff = Staff::find($id);
            if ($staff) {
                $staff->user()->delete();
                $staff->delete();
            }
        }

        return back()->with('success', 'Selected staff deleted.');
    }
}
