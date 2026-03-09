<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6">Work From Home Management</h2>

@if(session('success'))
<div class="bg-green-100 text-green-700 p-3 mb-4 rounded">
{{ session('success') }}
</div>
@endif


<!-- WFH ASSIGN FORM -->

<div class="bg-white shadow rounded p-6 mb-8">

<form method="POST" action="{{ route('admin.wfh.store') }}">
@csrf

<div class="grid grid-cols-4 gap-4">

<!-- Employee Select -->
<select name="user_id[]" multiple class="border rounded w-full p-2">
@foreach($employees as $employee)
<option value="{{ $employee->id }}">
{{ $employee->name }}
</option>
@endforeach
</select>

<!-- Start Date -->
<input type="date"
name="start_date"
class="border rounded px-3 py-2"
required>

<!-- End Date -->
<input type="date"
name="end_date"
class="border rounded px-3 py-2"
required>

<!-- Reason -->
<input type="text"
name="reason"
placeholder="Reason"
class="border rounded px-3 py-2">

</div>

<button class="mt-4 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
Assign WFH
</button>

</form>

</div>


<!-- ASSIGNED WFH LIST -->

<div class="bg-white shadow rounded p-6">

<h3 class="text-lg font-semibold mb-4">
Assigned Work From Home
</h3>

<table class="w-full border">

<thead class="bg-gray-100">
<tr>

<th class="p-2 border">Employee</th>
<th class="p-2 border">From</th>
<th class="p-2 border">To</th>
<th class="p-2 border">Reason</th>
<th class="p-2 border">Action</th>

</tr>
</thead>

<tbody>

@forelse($wfh as $item)

<tr>

<td class="p-2 border">
{{ $item->user->name ?? 'N/A' }}
</td>

<td class="p-2 border">
{{ $item->start_date }}
</td>

<td class="p-2 border">
{{ $item->end_date }}
</td>

<td class="p-2 border">
{{ $item->reason }}
</td>

<td class="p-2 border flex gap-2">

<!-- Edit -->
<a href="{{ route('admin.wfh.edit',$item->id) }}"
class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded">
Edit
</a>

<!-- Delete -->
<form method="POST"
action="{{ route('admin.wfh.delete',$item->id) }}">
@csrf
@method('DELETE')

<button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">
Delete
</button>

</form>

</td>

</tr>

@empty

<tr>
<td colspan="5" class="text-center p-4 text-gray-500">
No WFH assigned yet
</td>
</tr>

@endforelse

</tbody>

</table>

</div>

</div>

</x-app-layout>
