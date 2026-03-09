<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6">Employee Directory</h2>

<div class="bg-white shadow rounded-xl overflow-x-auto">

<table class="min-w-full text-sm">

<thead class="bg-gray-100">
<tr>
<th class="p-3 text-left">Name</th>
<th class="p-3 text-left">Employee ID</th>
<th class="p-3 text-left">Designation</th>
<th class="p-3 text-left">Mobile</th>
<th class="p-3 text-left">Email</th>
</tr>
</thead>

<tbody>

@foreach($employees as $employee)

<tr class="border-t">
<td class="p-3">{{ $employee->name }}</td>
<td class="p-3">{{ $employee->employee_id }}</td>
<td class="p-3">{{ $employee->designation }}</td>
<td class="p-3">{{ $employee->mobile }}</td>
<td class="p-3">{{ $employee->email }}</td>
</tr>

@endforeach

</tbody>

</table>

</div>

</div>

</x-app-layout>
