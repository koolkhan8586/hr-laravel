<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Holiday;
use App\Models\User;

class HolidayController extends Controller
{
    /**
     * Show all holidays
     */
   public function index()
{

$holidays = Holiday::where(function($q){

$q->where('for_all',1)
  ->orWhere('user_id',auth()->id());

})
->orderBy('start_date','desc')
->get();

$employees = \App\Models\User::where('role','employee')
->orderBy('name','asc')
->get();

if(auth()->user()->role == 'admin'){
return view('holidays.index',compact('holidays','employees'));
}

return view('employees.holidays',compact('holidays'));

}

    /**
     * Store new holiday
     */
    public function store(Request $request)
{

$request->validate([
'title' => 'required',
'start_date' => 'required|date',
'end_date' => 'required|date'
]);

if($request->for_all){

Holiday::create([
'title'=>$request->title,
'start_date'=>$request->start_date,
'end_date'=>$request->end_date,
'for_all'=>1
]);

}else{

foreach($request->user_id as $user){

Holiday::create([
'title'=>$request->title,
'start_date'=>$request->start_date,
'end_date'=>$request->end_date,
'for_all'=>0,
'user_id'=>$user
]);

}

}

return back()->with('success','Holiday added successfully');

}

    /**
     * Delete holiday
     */
    public function destroy($id)
    {
        Holiday::findOrFail($id)->delete();

        return redirect()->back()->with('success','Holiday deleted');
    }

    public function edit($id)
{

$holiday = Holiday::findOrFail($id);
$employees = User::where('role','employee')->get();

return view('holidays.edit',compact('holiday','employees'));

}
public function update(Request $request,$id)
{

$holiday = Holiday::findOrFail($id);

$holiday->update([
'title'=>$request->title,
'start_date'=>$request->start_date,
'end_date'=>$request->end_date,
'user_id'=>$request->user_id,
'for_all'=>$request->user_id ? 0 : 1
]);

return redirect()->back()->with('success','Holiday updated');

}

}
