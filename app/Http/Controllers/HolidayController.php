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
        $holidays = Holiday::orderBy('date','desc')->get();
        $employees = User::where('role','employee')->get();

        return view('holidays.index', compact('holidays','employees'));
    }

    /**
     * Store new holiday
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'date' => 'required|date'
        ]);

        Holiday::create([
            'title' => $request->title,
            'date' => $request->date,
            'for_all' => $request->for_all,
            'user_id' => $request->user_id
        ]);

        return redirect()->back()->with('success','Holiday added successfully');
    }

    /**
     * Delete holiday
     */
    public function destroy($id)
    {
        Holiday::findOrFail($id)->delete();

        return redirect()->back()->with('success','Holiday deleted');
    }
}
