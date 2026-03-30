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

{{-- LOCATION --}}
<div class="mb-3">
<label>Office Location</label>
<select name="office_location_id" class="border p-2 w-full">
<option value="">Select Location</option>

@foreach($locations as $loc)
<option value="{{ $loc->id }}"
{{ $employee->office_location_id == $loc->id ? 'selected' : '' }}>
{{ $loc->name }}
</option>
@endforeach

</select>
</div>

{{-- ALLOW ANYWHERE --}}
<div class="mb-3">
<label>
<input type="checkbox" name="allow_anywhere_attendance"
{{ $employee->allow_anywhere_attendance ? 'checked' : '' }}>
Allow Attendance Anywhere
</label>
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
