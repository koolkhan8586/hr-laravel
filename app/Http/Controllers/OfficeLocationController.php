<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OfficeLocation;

class OfficeLocationController extends Controller
{
    public function index(Request $request)
    {
        $locations = OfficeLocation::latest()->get();

        // ?edit=3 loads that location into the form above, map and all.
        $editing = $request->filled('edit')
            ? OfficeLocation::find($request->edit)
            : null;

        return view('admin.locations.index', compact('locations', 'editing'));
    }

    public function store(Request $request)
    {
        OfficeLocation::create($this->validated($request));

        return redirect()->route('admin.office-locations.index')
            ->with('success', 'Location added.');
    }

    public function update(Request $request, $id)
    {
        $location = OfficeLocation::findOrFail($id);

        $location->update($this->validated($request));

        return redirect()->route('admin.office-locations.index')
            ->with('success', 'Location updated.');
    }

    public function destroy($id)
    {
        $location = OfficeLocation::findOrFail($id);

        // Employees held to this office would silently become unrestricted,
        // so say who is affected rather than letting it happen quietly.
        $assigned = $location->users()->count() + $location->assignedUsers()->count();

        $location->delete();

        return redirect()->route('admin.office-locations.index')
            ->with('success', $assigned
                ? 'Location deleted. '.$assigned.' employee assignment(s) were removed with it.'
                : 'Location deleted.');
    }

    /**
     * The attendance check measures against these numbers, so they have to be
     * real coordinates rather than merely present.
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name'      => 'required|string|max:150',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius'    => 'required|integer|min:10|max:50000',
            'address'   => 'nullable|string|max:255',
        ]);
    }
}
