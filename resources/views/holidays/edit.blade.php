<x-app-layout>

<div class="max-w-4xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-6">Edit Holiday</h2>

<form method="POST" action="{{ route('admin.holidays.update',$holiday->id) }}">

@csrf
@method('PUT')

<div class="grid grid-cols-4 gap-4 mb-4">

<!-- Title -->

<input type="text"
name="title"
value="{{ $holiday->title }}"
class="border rounded px-3 py-2"
placeholder="Holiday Title"
required>

<!-- Start Date -->

<input type="date"
name="start_date"
value="{{ $holiday->start_date }}"
class="border rounded px-3 py-2"
required>

<!-- End Date -->

<input type="date"
name="end_date"
value="{{ $holiday->end_date }}"
class="border rounded px-3 py-2"
required>

<!-- Employee -->

<select name="user_id"
class="border rounded px-3 py-2">

<option value="">All Employees</option>

@foreach($employees as $employee)

<option value="{{ $employee->id }}"
@if($holiday->user_id == $employee->id) selected @endif>

{{ $employee->name }}

</option>

@endforeach

</select>

</div>

<button
class="bg-blue-600 text-white px-4 py-2 rounded">
Update Holiday
</button>

<a href="{{ url()->previous() }}"
class="ml-3 bg-gray-500 text-white px-4 py-2 rounded">
Cancel
</a>

</form>

</div>

</x-app-layout>
