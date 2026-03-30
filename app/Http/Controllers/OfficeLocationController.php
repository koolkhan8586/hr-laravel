<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OfficeLocation;

class OfficeLocationController extends Controller
{
    public function index()
    {
        $locations = OfficeLocation::latest()->get();
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

        return back()->with('success','Location added');
    }

    public function destroy($id)
    {
        OfficeLocation::findOrFail($id)->delete();
        return back()->with('success','Deleted');
    }
}
