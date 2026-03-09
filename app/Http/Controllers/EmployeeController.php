<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

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
}
