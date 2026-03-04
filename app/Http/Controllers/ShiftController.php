<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::all();
        return view('admin.shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('admin.shifts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'grace_minutes' => 'required|integer'
        ]);

        Shift::create($request->all());

        return redirect()->route('shifts.index')->with('success', 'Shift created successfully');
    }

    public function edit($id)
    {
        $shift = Shift::findOrFail($id);
        return view('admin.shifts.edit', compact('shift'));
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $shift->update($request->all());

        return redirect()->route('shifts.index')->with('success', 'Shift updated');
    }

    public function destroy($id)
    {
        Shift::findOrFail($id)->delete();

        return redirect()->route('shifts.index')->with('success', 'Shift deleted');
    }
}
