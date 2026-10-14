<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-xl font-bold mb-2">
Monthly Attendance Summary
</h2>

<p class="text-sm text-gray-500 mb-6">
Counted the same way as each employee's own monthly report, so the two agree.
A half day taken as leave counts under Half Day, not Leave. Holidays and
weekly offs are not working days, so they count neither for nor against
anybody.
</p>

<form method="GET" class="mb-6">

<input type="month"
name="month"
value="{{ $month }}"
class="border rounded p-2">

@if($includePayrollOnly ?? false)
<input type="hidden" name="include_payroll_only" value="1">
@endif

<button class="bg-blue-600 text-white px-4 py-2 rounded">
View
</button>

</form>

@include('admin.partials.payroll-only-note')

<div class="bg-white shadow rounded overflow-x-auto">

<table class="w-full border text-sm">

<thead class="bg-gray-200">

<tr>
<th class="border p-2">Employee</th>
<th class="border p-2 text-center">Present</th>
<th class="border p-2 text-center">Late</th>
<th class="border p-2 text-center">Half Day</th>
<th class="border p-2 text-center">Leave</th>
<th class="border p-2 text-center">Work From Home</th>
<th class="border p-2 text-center">Absent</th>
<th class="border p-2 text-center">Holiday</th>
<th class="border p-2 text-center">Weekly Off</th>
<th class="border p-2 text-center">Working Days</th>
<th class="border p-2 text-center">Hours</th>
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

<td class="border p-2 text-center text-indigo-600">
{{ $row['wfh'] }}
</td>

<td class="border p-2 text-center text-red-600">
{{ $row['absent'] }}
</td>

<td class="border p-2 text-center text-gray-500">
{{ $row['holiday'] }}
</td>

<td class="border p-2 text-center text-gray-500">
{{ $row['off'] }}
</td>

<td class="border p-2 text-center">
{{ $row['working_days'] }}
</td>

<td class="border p-2 text-center">
{{ number_format($row['hours'], 2) }}
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
