<x-app-layout>

<div class="grid grid-cols-5 gap-6 mb-8">

<a href="{{ route('admin.attendance.list','present') }}">
<div class="bg-green-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<h3 class="text-lg font-semibold text-green-700">Present</h3>
<p class="text-3xl font-bold text-green-800">{{ $present }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','late') }}">
<div class="bg-yellow-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<h3 class="text-lg font-semibold text-yellow-700">Late</h3>
<p class="text-3xl font-bold text-yellow-800">{{ $late }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','halfday') }}">
<div class="bg-purple-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<h3 class="text-lg font-semibold text-purple-700">Half Day</h3>
<p class="text-3xl font-bold text-purple-800">{{ $halfday }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','leave') }}">
<div class="bg-blue-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<h3 class="text-lg font-semibold text-blue-700">Leave</h3>
<p class="text-3xl font-bold text-blue-800">{{ $leave }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','absent') }}">
<div class="bg-red-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<h3 class="text-lg font-semibold text-red-700">Absent</h3>
<p class="text-3xl font-bold text-red-800">{{ $absent }}</p>
</div>
</a>

<a href="{{ route('admin.attendance.list','working') }}">
<div class="bg-indigo-100 p-5 rounded-xl shadow text-center">
<h3 class="text-lg font-semibold text-indigo-700">Working</h3>
<p class="text-3xl font-bold">{{ $working->count() }}</p>
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
<script>
setInterval(function(){
    location.reload();
},10000);
</script>
    
</div>
</x-app-layout>
