<form method="POST" action="{{ route('admin.holidays.store') }}">
@csrf

<div class="grid grid-cols-4 gap-4">

<input type="text"
name="title"
placeholder="Holiday Title"
class="border rounded px-3 py-2"
required>

<input type="date"
name="start_date"
class="border rounded px-3 py-2"
required>

<input type="date"
name="end_date"
class="border rounded px-3 py-2"
required>

<select name="for_all"
class="border rounded px-3 py-2"
id="holidayType">

<option value="1">All Employees</option>
<option value="0">Specific Employees</option>

</select>

</div>

<div class="mt-3" id="employeeSelect" style="display:none">

<select name="user_id[]"
multiple
class="border rounded px-3 py-2 w-full">

@foreach($employees as $employee)

<option value="{{ $employee->id }}">
{{ $employee->name }}
</option>

@endforeach

</select>

</div>

<button class="mt-4 bg-green-600 text-white px-4 py-2 rounded">
Add Holiday
</button>

</form>
