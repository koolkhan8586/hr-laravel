<?php

namespace App\Http\Controllers;

use App\Models\LocationChangeRequest;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Support\AttendanceLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LocationChangeRequestController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Employee side
    |--------------------------------------------------------------------------
    */

    /** Where this employee may mark attendance, and how to ask for a change. */
    public function mine(Request $request)
    {
        $user = $request->user();

        $assigned = $user->attendanceOffices();

        return view('attendance.my-locations', [
            'user'        => $user,
            'assigned'    => $assigned,
            'unassigned'  => OfficeLocation::orderBy('name')
                ->whereNotIn('id', $assigned->pluck('id'))
                ->get(),
            'anywhere'    => AttendanceLocation::hasOverride($user),
            'restricted'  => AttendanceLocation::isRestricted($user),
            'requests'    => LocationChangeRequest::with(['officeLocation', 'decidedBy'])
                ->where('user_id', $user->id)
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'office_location_id' => 'required|exists:office_locations,id',
            'reason'             => 'required|string|max:1000',
        ]);

        // One open request per office, so a repeated tap does not queue five.
        $already = LocationChangeRequest::pending()
            ->where('user_id', $user->id)
            ->where('office_location_id', $request->office_location_id)
            ->exists();

        if ($already) {
            return back()->with('error', 'You already have a request waiting for this location.');
        }

        if ($user->attendanceOffices()->contains('id', (int) $request->office_location_id)) {
            return back()->with('error', 'You can already mark attendance at that location.');
        }

        $changeRequest = LocationChangeRequest::create([
            'user_id'            => $user->id,
            'office_location_id' => $request->office_location_id,
            'reason'             => $request->reason,
            'status'             => 'pending',
        ]);

        $this->notifyAdmins($changeRequest);

        return back()->with('success', 'Request sent. You will be told once it has been looked at.');
    }

    /** An employee may take back a request nobody has acted on yet. */
    public function cancel(Request $request, $id)
    {
        $changeRequest = LocationChangeRequest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (!$changeRequest->isPending()) {
            return back()->with('error', 'That request has already been decided.');
        }

        $changeRequest->delete();

        return back()->with('success', 'Request withdrawn.');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin side
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $status = in_array($request->status, ['pending', 'approved', 'rejected'])
            ? $request->status
            : 'pending';

        return view('admin.locations.requests', [
            'requests' => LocationChangeRequest::with(['user.officeLocations', 'officeLocation', 'decidedBy'])
                ->where('status', $status)
                ->latest()
                ->get(),
            'status'       => $status,
            'pendingCount' => LocationChangeRequest::pending()->count(),
        ]);
    }

    /**
     * Approve, either adding the location to the ones they already have or
     * replacing them with it.
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'mode' => 'required|in:add,replace',
            'note' => 'nullable|string|max:1000',
        ]);

        $changeRequest = LocationChangeRequest::with('user')->findOrFail($id);

        if (!$changeRequest->isPending()) {
            return back()->with('error', 'That request has already been decided.');
        }

        $employee = $changeRequest->user;

        $offices = $request->mode === 'replace'
            ? collect([$changeRequest->office_location_id])
            : $employee->attendanceOffices()->pluck('id')->push($changeRequest->office_location_id);

        $employee->syncOfficeLocations($offices);

        $changeRequest->update([
            'status'        => 'approved',
            'applied_as'    => $request->mode === 'replace' ? 'replaced' : 'added',
            'decided_by'    => $request->user()->id,
            'decided_at'    => now(),
            'decision_note' => $request->note,
        ]);

        $this->notifyEmployee($changeRequest);

        return back()->with('success', 'Approved. '.$employee->name.'\'s locations have been updated.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['note' => 'nullable|string|max:1000']);

        $changeRequest = LocationChangeRequest::findOrFail($id);

        if (!$changeRequest->isPending()) {
            return back()->with('error', 'That request has already been decided.');
        }

        $changeRequest->update([
            'status'        => 'rejected',
            'decided_by'    => $request->user()->id,
            'decided_at'    => now(),
            'decision_note' => $request->note,
        ]);

        $this->notifyEmployee($changeRequest);

        return back()->with('success', 'Request marked as not approved.');
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications - best effort, never block the request itself
    |--------------------------------------------------------------------------
    */

    protected function notifyAdmins(LocationChangeRequest $changeRequest): void
    {
        $changeRequest->loadMissing(['user', 'officeLocation']);

        $admins = User::where('role', 'admin')->pluck('email')->filter();

        if ($admins->isEmpty()) {
            return;
        }

        $body = "Attendance location request\n\n"
            ."Employee: ".$changeRequest->user->name."\n"
            ."Location asked for: ".$changeRequest->officeLocation->name."\n"
            ."Reason: ".$changeRequest->reason."\n\n"
            ."Review it at: ".route('admin.location-requests.index');

        try {
            Mail::raw($body, function ($message) use ($admins) {
                $message->to($admins->all())
                    ->subject('Attendance location request');
            });
        } catch (\Throwable $e) {
            Log::error('Location request admin mail failed: '.$e->getMessage());
        }
    }

    protected function notifyEmployee(LocationChangeRequest $changeRequest): void
    {
        $changeRequest->loadMissing(['user', 'officeLocation']);

        if (!filled($changeRequest->user->email)) {
            return;
        }

        $body = "Your attendance location request\n\n"
            ."Location: ".$changeRequest->officeLocation->name."\n"
            ."Outcome: ".$changeRequest->outcome()."\n"
            .($changeRequest->decision_note ? "Note: ".$changeRequest->decision_note."\n" : '');

        try {
            Mail::raw($body, function ($message) use ($changeRequest) {
                $message->to($changeRequest->user->email)
                    ->subject('Your attendance location request');
            });
        } catch (\Throwable $e) {
            Log::error('Location request employee mail failed: '.$e->getMessage());
        }
    }
}
