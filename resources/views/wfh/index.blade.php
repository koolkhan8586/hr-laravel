<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6">Work From Home Management</h2>

@if(session('success'))
<div class="bg-green-100 text-green-700 p-3 mb-4">
{{ session('success') }}
</div>
@endif


<form method="POST" action="{{ route('admin.wfh.store') }}">
@csrf

<div class="grid grid-cols-4 gap-4">

<select name="user_id" class="border rounded px-3 py-2">

@foreach($employees as $employee)

<option value="{{ $employee->id }}">
{{ $employee->name }}
</option>

@endforeach

</select>

<input type="date" name="start_date" class="border rounded px-3 py-2">

<input type="date" name="end_date" class="border rounded px-3 py-2">

<input type="text" name="reason" placeholder="Reason"
class="border rounded px-3 py-2">

</div>

<button class="mt-4 bg-purple-600 text-white px-4 py-2 rounded">
Assign WFH
</button>

</form>

</div>

</x-app-layout>
