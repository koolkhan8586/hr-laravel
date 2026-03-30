<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\OfficeLocation;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $employees = User::where('role','employee')
            ->when($search,function($query,$search){
                return $query->where('name','like','%'.$search.'%')
                             ->orWhere('employee_id','like','%'.$search.'%')
                             ->orWhere('designation','like','%'.$search.'%');
            })
            ->orderBy('name')
            ->get();

        return view('employees.index',compact('employees','search'));
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Employee (NEW)
    |--------------------------------------------------------------------------
    */
    public function edit($id)
    {
        $employee = User::findOrFail($id);
        $locations = OfficeLocation::all(); // ✅ important

        return view('employees.edit', compact('employee', 'locations'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Employee (NEW)
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $employee->update([
            'name' => $request->name,
            'email' => $request->email,
            'designation' => $request->designation,
            'department' => $request->department,

            // ✅ LOCATION ASSIGN
            'office_location_id' => $request->office_location_id,

            // ✅ ALLOW ANYWHERE
            'allow_anywhere_attendance' => $request->has('allow_anywhere_attendance'),

            // ✅ TEMPORARY PERMISSION
            'attendance_override_until' => $request->attendance_override_until
                ? Carbon::parse($request->attendance_override_until)
                : null,
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully');
    }
}
