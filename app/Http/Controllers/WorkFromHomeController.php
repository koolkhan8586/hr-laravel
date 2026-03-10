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

$wfh = WorkFromHome::with('user')
->orderBy('start_date','desc')
->get();

return view('wfh.index', compact('employees','wfh'));

}
public function store(Request $request)
{

$request->validate([
'user_id' => 'required',
'start_date' => 'required',
'end_date' => 'required'
]);

foreach($request->user_id as $user){

WorkFromHome::create([
'user_id' => $user,
'start_date' => $request->start_date,
'end_date' => $request->end_date,
'reason' => $request->reason
]);

}

return back()->with('success','WFH assigned');

}


public function edit($id)
{

$wfh = WorkFromHome::findOrFail($id);

$employees = User::where('role','employee')->get();

return view('wfh.edit', compact('wfh','employees'));

}


public function update(Request $request, $id)
{

$request->validate([
'user_id' => 'required',
'start_date' => 'required',
'end_date' => 'required'
]);

$wfh = WorkFromHome::findOrFail($id);

$wfh->update([
'user_id' => $request->user_id,
'start_date' => $request->start_date,
'end_date' => $request->end_date,
'reason' => $request->reason
]);

return redirect()->route('admin.wfh.index')
->with('success','WFH updated successfully');

}

public function destroy($id)
{

WorkFromHome::findOrFail($id)->delete();

return back()->with('success','WFH removed');

}

public function employeeWFH()
{

$wfh = \App\Models\WorkFromHome::where('user_id',auth()->id())
->orderBy('start_date','desc')
->get();

return view('employees.wfh', compact('wfh'));

}


}
