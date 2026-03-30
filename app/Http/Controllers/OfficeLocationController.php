<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OfficeLocationController extends Controller
{
    public function index()
    {
        $locations = OfficeLocation::all();
        return view('admin.locations.index', compact('locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'radius' => 'required'
        ]);

        OfficeLocation::create($request->all());

        return back()->with('success', 'Location added');
    }

    public function update(Request $request, $id)
    {
        $location = OfficeLocation::findOrFail($id);
        $location->update($request->all());

        return back()->with('success', 'Location updated');
    }

    public function destroy($id)
    {
        OfficeLocation::destroy($id);
        return back()->with('success', 'Deleted');
    }
}
