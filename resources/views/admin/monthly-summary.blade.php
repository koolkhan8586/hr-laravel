<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-xl font-bold mb-6">
Monthly Attendance Summary
</h2>

<form method="GET" class="mb-6">

<input type="month"
name="month"
value="{{ $month }}"
class="border rounded p-2">

<button class="bg-blue-600 text-white px-4 py-2 rounded">
View
</button>

</form>

<div class="bg-white shadow rounded">

<table class="w-full border text-sm">

<thead class="bg-gray-200">

<tr>
<th class="border p-2">Employee</th>
<th class="border p-2 text-center">Present</th>
<th class="border p-2 text-center">Late</th>
<th class="border p-2 text-center">Half Day</th>
<th class="border p-2 text-center">Leave</th>
<th class="border p-2 text-center">Absent</th>
<th class="border p-2 text-center">Attendance %</th>
</tr>

</thead>

<tbody>

@foreach($data as $row)

<tr>

<td class="border p-2">
{{ $row['user']->name }}
</td>

<td class="border p-2 text-center text-green-600">
{{ $row['present'] }}
</td>

<td class="border p-2 text-center text-yellow-600">
{{ $row['late'] }}
</td>

<td class="border p-2 text-center text-purple-600">
{{ $row['halfday'] }}
</td>

<td class="border p-2 text-center text-blue-600">
{{ $row['leave'] }}
</td>

<td class="border p-2 text-center text-red-600">
{{ $row['absent'] }}
</td>

<td class="border p-2 text-center font-bold">
{{ $row['percent'] }}%
</td>

</tr>

@endforeach

</tbody>

</table>

</div>

</div>

</x-app-layout>
