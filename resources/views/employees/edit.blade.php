<x-app-layout>

<div class="max-w-4xl mx-auto py-6 px-4">

<h2 class="text-xl font-bold mb-4">Edit Employee</h2>

<form method="POST" action="{{ route('employees.update',$employee->id) }}">
@csrf

<div class="mb-3">
<label>Name</label>
<input type="text" name="name" value="{{ $employee->name }}" class="border p-2 w-full">
</div>

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" value="{{ $employee->email }}" class="border p-2 w-full">
</div>

{{-- ATTENDANCE LOCATIONS --}}
@php
    $assignedOffices = old('office_location_ids', $employee->officeLocations->pluck('id')->all());
@endphp

<div class="mb-3">
<label>Attendance Locations</label>

@if($locations->isEmpty())
<p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2">
    No office locations have been added yet.
</p>
@else
<div class="border rounded p-2 grid grid-cols-1 md:grid-cols-2 gap-1">
    @foreach($locations as $loc)
    <label class="flex items-start gap-2 text-sm">
        <input type="checkbox" name="office_location_ids[]" value="{{ $loc->id }}" class="mt-1"
               {{ in_array($loc->id, $assignedOffices) ? 'checked' : '' }}>
        <span>{{ $loc->name }}
            <span class="block text-xs text-gray-500">within {{ (int) ($loc->radius ?: 100) }}m</span>
        </span>
    </label>
    @endforeach
</div>
<p class="text-xs text-gray-500 mt-1">
    Tick none to let this employee clock in from anywhere.
</p>
@endif
</div>

{{-- ALLOW ANYWHERE --}}
<div class="mb-3">
<label>
<input type="checkbox" name="allow_anywhere_attendance"
{{ $employee->allow_anywhere_attendance ? 'checked' : '' }}>
Allow Attendance Anywhere
</label>
</div>

{{-- EARLY LEAVE ALLOWANCE --}}
<div class="mb-3">
<label>Leaving Early Costs Half a Day From</label>

<div style="display:flex; align-items:center; gap:8px;">
    <input type="number" name="early_leave_allowance_minutes" min="1" max="480"
           value="{{ $employee->early_leave_allowance_minutes }}"
           placeholder="{{ \App\Support\AttendanceRules::earlyMinutes() }}"
           class="border p-2" style="width:120px;">
    <span class="text-sm text-gray-600">minutes before their shift ends</span>
</div>

<p class="text-xs text-gray-500 mt-1">
    Leave this blank to use the company rule, currently
    <strong>{{ \App\Support\AttendanceRules::earlyMinutes() }} minutes</strong>.
    Put a bigger number here for somebody who is allowed to go earlier - at
    60, for instance, they can leave up to 59 minutes early and nothing is
    charged.
</p>
</div>

{{-- TEMP OVERRIDE --}}
<div class="mb-3">
<label>Override Until</label>
<input type="datetime-local" name="attendance_override_until"
value="{{ $employee->attendance_override_until }}"
class="border p-2 w-full">
</div>

<button class="bg-green-600 text-white px-4 py-2 rounded">
Update Employee
</button>

</form>

</div>

</x-app-layout>
