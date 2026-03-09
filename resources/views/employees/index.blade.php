<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6">Employee Directory</h2>

<!-- SEARCH -->

<form method="GET" class="mb-4">
<input type="text" name="search" value="{{ $search }}"
placeholder="Search employee..."
class="border rounded px-3 py-2 w-64">

<button class="bg-blue-500 text-white px-4 py-2 rounded">
Search
</button>
</form>

<div class="bg-white shadow rounded-xl overflow-x-auto">

<table class="min-w-full text-sm">

<thead class="bg-gray-100">
<tr>
<th class="p-3 text-left">Name</th>
<th class="p-3 text-left">Employee ID</th>
<th class="p-3 text-left">Designation</th>
<th class="p-3 text-left">Department</th>
<th class="p-3 text-left">Mobile</th>
<th class="p-3 text-left">Email</th>
</tr>
</thead>

<tbody>

@forelse($employees as $employee)

<tr class="border-t hover:bg-gray-50">

<td class="p-3 font-semibold">
{{ $employee->name }}
</td>

<td class="p-3">
{{ $employee->employee_id }}
</td>

<td class="p-3">
{{ $employee->designation }}
</td>

<td class="p-3">
{{ $employee->department }}
</td>

<td class="p-3">

<a href="tel:{{ $employee->mobile }}"
class="text-blue-600">
{{ $employee->mobile }}
</a>

</td>

<td class="p-3">

<a href="mailto:{{ $employee->email }}"
class="text-blue-600">
{{ $employee->email }}
</a>

</td>

</tr>

@empty

<tr>
<td colspan="6" class="p-4 text-center text-gray-500">
No employees found
</td>
</tr>

@endforelse

</tbody>

</table>

</div>

</div>

</x-app-layout>
