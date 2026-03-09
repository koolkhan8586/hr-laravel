<?php

namespace App\Http\Controllers;

use App\Models\User;

public function index()
{
    $employees = User::orderBy('name')->get();
    return view('employees.index', compact('employees'));
}
