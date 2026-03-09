<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkFromHome;
use App\Models\User;

class WorkFromHomeController extends Controller
{

public function index()
{

$employees = User::where('role','employee')->get();

$wfh = WorkFromHome::latest()->get();

return view('wfh.index',compact('employees','wfh'));

}

public function store(Request $request)
{

$request->validate([
'user_id'=>'required',
'start_date'=>'required',
'end_date'=>'required'
]);

WorkFromHome::create($request->all());

return back()->with('success','WFH assigned');

}

public function destroy($id)
{

WorkFromHome::findOrFail($id)->delete();

return back()->with('success','WFH removed');

}

}
