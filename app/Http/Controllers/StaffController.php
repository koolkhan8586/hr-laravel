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
use Carbon\Carbon;
use App\Models\OfficeLocation;

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

        // Sortable columns. Name lives on the user, the rest on the staff row.
        $sort = in_array($request->sort, ['code', 'name', 'department', 'designation', 'salary', 'status'])
            ? $request->sort
            : 'code';

        $dir = $request->dir === 'desc' ? 'desc' : 'asc';

        if ($sort === 'name') {
            $query->leftJoin('users', 'users.id', '=', 'staff.user_id')
                ->select('staff.*')
                ->orderBy('users.name', $dir);
        } else {
            $column = $sort === 'code' ? 'employee_id' : $sort;
            $query->orderBy($column, $dir);
        }

        $staff = $query->get();

        return view('staff.index', compact('staff', 'sort', 'dir'));
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
    'mobile' => 'nullable|string|max:20',
    'employee_code' => 'required|unique:staff,employee_id',
    'department' => 'required',
    'designation' => 'required',
    'salary' => 'required|numeric',
    'joining_date' => 'required|date',
    'role' => 'required|in:employee,manager,admin',
    'tracks_attendance' => 'nullable|in:0,1',
]);

    // AUTO GENERATE EMPLOYEE CODE
   $employeeCode = strtoupper(trim($request->employee_code));

    $password = \Str::random(8);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'mobile' => $request->mobile,
        'employee_code' => $employeeCode,
        'password' => \Hash::make($password),
        'force_password_change' => true,
        'tracks_attendance' => $request->input('tracks_attendance', '1') === '0' ? false : true,
    ]);

    $user->role = $request->role;
    $user->save();

    Staff::create([
    'user_id' => $user->id,
    'employee_id' => $employeeCode,
    'department' => $request->department,
    'designation' => $request->designation,
    'salary' => $request->salary,
    'joining_date' => $request->joining_date
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

    // 🔥 ADD THIS LINE
    $locations = \App\Models\OfficeLocation::all();

    // Possible bank payees: any other employee, ordered for the dropdown
    $payees = User::where('role', 'employee')
        ->where('id', '!=', $staff->user_id)
        ->orderBy('name')
        ->get(['id', 'name', 'employee_code']);

    return view('staff.edit', compact('staff','locations','payees'));
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
        'mobile'        => 'nullable|string|max:20',
        'employee_code' => 'required|unique:users,employee_code,' . $staff->user->id,
        'department'    => 'required',
        'designation'   => 'required',
        'salary'          => 'required|numeric',
        'role'            => 'required|in:employee,manager,admin',
        'joining_date'    => 'required|date',
        'salary_category' => 'nullable|in:teacher,staff',
        'bank_account_no' => 'nullable|string|max:50',
        'bank_payee_id'   => 'nullable|exists:users,id',
        'tracks_attendance' => 'nullable|in:0,1',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Update User Table
    |--------------------------------------------------------------------------
    */
    $staff->user->update([
        'name'          => $request->name,
        'email'         => $request->email,
        'mobile'        => $request->mobile,
        'employee_code' => strtoupper($request->employee_code),
        'office_location_id' => $request->office_location_id,

        // Payroll / salary sheet details
        'salary_category' => $request->salary_category ?? 'staff',
        'bank_account_no' => $request->bank_account_no,

        // Never let an employee point at themselves as their own payee
        'bank_payee_id'   => $request->bank_payee_id == $staff->user_id
            ? null
            : $request->bank_payee_id,

        'can_manage_salary' => $request->boolean('can_manage_salary'),
        'can_manage_loan'   => $request->boolean('can_manage_loan'),

        // Payroll-only employees stay out of the attendance reports
        'tracks_attendance' => $request->input('tracks_attendance', '1') === '0' ? false : true,

        // 🔓 Allow Anywhere Attendance
        'allow_anywhere_attendance' => $request->has('allow_anywhere_attendance'),

        // ⏱ Temporary Override
        'attendance_override_until' => $request->attendance_override_until
            ? Carbon::parse($request->attendance_override_until)
            : null,
    ]);

    $staff->user->role = $request->role;
    $staff->user->save();

    /*
    |--------------------------------------------------------------------------
    | Update Staff Table
    |--------------------------------------------------------------------------
    */
    $staff->update([
    'employee_id'  => strtoupper($request->employee_code),
    'department'   => $request->department,
    'designation'  => $request->designation,
    'salary'       => $request->salary,
    'joining_date' => $request->joining_date,
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
    | Bulk Attendance Tracking
    |--------------------------------------------------------------------------
    | Turns the selected staff into payroll-only records (or back again) in one
    | go, for the usual case where a whole group never marks attendance.
    */
    public function bulkTracking(Request $request)
    {
        if (!$request->staff_ids) {
            return back()->with('error', 'No staff selected.');
        }

        $tracks = $request->input('tracks') === '1';

        $userIds = Staff::whereIn('id', $request->staff_ids)->pluck('user_id');

        User::whereIn('id', $userIds)->update(['tracks_attendance' => $tracks]);

        return back()->with('success', $userIds->count().' employee(s) set to '.
            ($tracks ? 'attendance tracking.' : 'payroll only.'));
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
