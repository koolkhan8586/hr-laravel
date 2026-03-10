<x-app-layout>

<div class="max-w-4xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6">
Edit Work From Home
</h2>

<form method="POST"
action="{{ route('admin.wfh.update',$wfh->id) }}">
@csrf
@method('PUT')

<div class="grid grid-cols-4 gap-4">

<select name="user_id"
class="border rounded px-3 py-2">

@foreach($employees as $employee)

<option value="{{ $employee->id }}"
@if($employee->id == $wfh->user_id) selected @endif>

{{ $employee->name }}

</option>

@endforeach

</select>

<input type="date"
name="start_date"
value="{{ $wfh->start_date }}"
class="border rounded px-3 py-2">

<input type="date"
name="end_date"
value="{{ $wfh->end_date }}"
class="border rounded px-3 py-2">

<input type="text"
name="reason"
value="{{ $wfh->reason }}"
class="border rounded px-3 py-2">

</div>

<button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
Update WFH
</button>

</form>

</div>

</x-app-layout>