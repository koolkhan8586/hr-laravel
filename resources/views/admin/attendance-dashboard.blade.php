<x-app-layout>

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">

<a href="{{ route('admin.attendance.list','present') }}">
<div class="bg-green-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<div class="text-green-700 font-semibold">Present</div>
<div class="text-3xl font-bold text-green-800">{{ $present }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','late') }}">
<div class="bg-yellow-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<div class="text-yellow-700 font-semibold">Late</div>
<div class="text-3xl font-bold text-yellow-800">{{ $late }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','halfday') }}">
<div class="bg-purple-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<div class="text-purple-700 font-semibold">Half Day</div>
<div class="text-3xl font-bold text-purple-800">{{ $halfday }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','leave') }}">
<div class="bg-blue-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<div class="text-blue-700 font-semibold">Leave</div>
<div class="text-3xl font-bold text-blue-800">{{ $leave }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','absent') }}">
<div class="bg-red-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<div class="text-red-700 font-semibold">Absent</div>
<div class="text-3xl font-bold text-red-800">{{ $absent }}</div>
</div>
</a>


<a href="{{ route('admin.attendance.list','working') }}">
<div class="bg-red-100 p-6 rounded-xl text-center shadow hover:shadow-lg">
<div class="text-red-700 font-semibold">Working</div>
<p class="text-3xl font-bold text-pink-800"">{{ $working->count() }}</p>
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
    ->diffInMinutes(now('Asia/Karachi'));

$hours = floor($minutes / 60);
$mins = $minutes % 60;
@endphp

<tr>
<td class="p-2 border">
{{ $attendance->user->name ?? 'Unknown' }}
</td>

<td class="p-2 border">
{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s') }}
</td>

<td class="p-2 border">
{{ $hours }}h {{ $mins }}m
</td>
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
