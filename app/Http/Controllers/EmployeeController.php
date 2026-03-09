<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::orderBy('name')->get();

        return view('employees.index', compact('employees'));
    }
}
