<x-app-layout>
<div class="grid grid-cols-6 gap-4 mb-6">

<a href="{{ route('admin.attendance.list','present') }}">
<div class="bg-green-100 p-6 rounded shadow text-center hover:shadow-lg">
<h3 class="text-lg font-semibold">Present</h3>
<p class="text-3xl font-bold text-green-700">{{ $present }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','late') }}">
<div class="bg-yellow-100 p-6 rounded shadow text-center hover:shadow-lg">
<h3 class="text-lg font-semibold">Late</h3>
<p class="text-3xl font-bold text-yellow-700">{{ $late }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','halfday') }}">
<div class="bg-purple-100 p-6 rounded shadow text-center hover:shadow-lg">
<h3 class="text-lg font-semibold">Half Day</h3>
<p class="text-3xl font-bold text-purple-700">{{ $halfday }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','leave') }}">
<div class="bg-blue-100 p-6 rounded shadow text-center hover:shadow-lg">
<h3 class="text-lg font-semibold">Leave</h3>
<p class="text-3xl font-bold text-blue-700">{{ $leave }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','absent') }}">
<div class="bg-red-100 p-6 rounded shadow text-center hover:shadow-lg">
<h3 class="text-lg font-semibold">Absent</h3>
<p class="text-3xl font-bold text-red-700">{{ $absent }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','working') }}">
<div class="bg-indigo-100 p-6 rounded shadow text-center hover:shadow-lg">
<h3 class="text-lg font-semibold">Currently Working</h3>
<p class="text-3xl font-bold text-indigo-700">{{ $working->count() }}</p>
</div>
</a>

</div>

<h3 class="text-xl font-bold mb-4">Employees Currently Working</h3>

<table class="w-full border">
<thead class="bg-gray-200">
<tr>
<th class="p-2 border">Employee</th>
<th class="p-2 border">Clock In</th>
<th class="p-2 border">Working Time</th>
</tr>
</thead>

<tbody>

@foreach($working as $attendance)

@php
$minutes = \Carbon\Carbon::parse($attendance->clock_in)
->diffInMinutes(now());

$hours = floor($minutes / 60);
$mins = $minutes % 60;
@endphp

<tr>
<td class="p-2 border">{{ $attendance->user->name }}</td>
<td class="p-2 border">{{ $attendance->clock_in }}</td>
<td class="p-2 border">{{ $hours }}h {{ $mins }}m</td>
</tr>

@endforeach

</tbody>
</table>

</div>
</x-app-layout>
