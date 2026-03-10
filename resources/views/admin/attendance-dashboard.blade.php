<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<!-- Manual Attendance Entry -->
<div class="bg-white shadow-lg rounded-xl p-6 mb-8 border">

<h3 class="text-lg font-semibold text-gray-700 mb-4">
Manual Attendance Entry (Admin)
</h3>

<form method="POST" action="{{ route('admin.attendance.manual') }}">
@csrf

<div class="grid grid-cols-1 md:grid-cols-4 gap-4">

<div>
<label class="text-sm text-gray-600">Employee</label>
<select name="user_id" class="w-full border rounded-lg p-2 mt-1" required>
<option value="">Select Employee</option>

@foreach(\App\Models\User::where('role','employee')->orderBy('name','asc')->get() as $emp)
<option value="{{ $emp->id }}">
{{ $emp->name }}
</option>
@endforeach

</select>
</div>

<div>
<label class="text-sm text-gray-600">Date</label>
<input type="date" name="date"
class="w-full border rounded-lg p-2 mt-1" required>
</div>

<div>
<label class="text-sm text-gray-600">Clock In</label>
<input type="time" name="clock_in"
class="w-full border rounded-lg p-2 mt-1" required>
</div>

<div>
<label class="text-sm text-gray-600">Clock Out</label>
<input type="time" name="clock_out"
class="w-full border rounded-lg p-2 mt-1">
</div>

</div>

<div class="mt-4">
<button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow">
Mark Attendance
</button>
</div>

</form>

</div>


    <form method="GET" class="mb-6 flex items-center gap-3">

<input type="date"
       name="date"
       value="{{ request('date', now()->toDateString()) }}"
       class="border rounded-lg p-2">

<button class="bg-blue-600 text-white px-4 py-2 rounded-lg">
View
</button>

</form>

<!-- Dashboard Cards -->

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">

<a href="{{ route('admin.attendance.list',['type'=>'present','date'=>$date]) }}">
<div class="bg-green-100 p-6 rounded-xl shadow hover:shadow-lg transition">
<div class="flex items-center justify-between">
<div>
<p class="text-green-700 font-semibold">Present</p>
<p class="text-3xl font-bold text-green-800">{{ $present }}</p>
</div>
<div class="text-green-600 text-3xl">✔</div>
</div>
</div>
</a>

<a href="{{ route('admin.attendance.list',['type'=>'late','date'=>$date]) }}">
<div class="bg-yellow-100 p-6 rounded-xl shadow hover:shadow-lg transition">
<div class="flex items-center justify-between">
<div>
<p class="text-yellow-700 font-semibold">Late</p>
<p class="text-3xl font-bold text-yellow-800">{{ $late }}</p>
</div>
<div class="text-yellow-600 text-3xl">⏰</div>
</div>
</div>
</a>

<a href="{{ route('admin.attendance.list',['type'=>'halfday','date'=>$date]) }}">
<div class="bg-purple-100 p-6 rounded-xl shadow hover:shadow-lg transition">
<div class="flex items-center justify-between">
<div>
<p class="text-purple-700 font-semibold">Half Day</p>
<p class="text-3xl font-bold text-purple-800">{{ $halfday }}</p>
</div>
<div class="text-purple-600 text-3xl">🕒</div>
</div>
</div>
</a>

<a href="{{ route('admin.attendance.list',['type'=>'leave','date'=>$date]) }}">
<div class="bg-blue-100 p-6 rounded-xl shadow hover:shadow-lg transition">
<div class="flex items-center justify-between">
<div>
<p class="text-blue-700 font-semibold">Leave</p>
<p class="text-3xl font-bold text-blue-800">{{ $leave }}</p>
</div>
<div class="text-blue-600 text-3xl">🌴</div>
</div>
</div>
</a>

<div class="bg-indigo-100 p-5 rounded shadow">

<div class="text-indigo-700 font-semibold">
WFH
</div>

<div class="text-3xl font-bold">
{{ $wfhCount }}
</div>

<div class="text-2xl">
🏠
</div>

</div>

<a href="{{ route('admin.attendance.list',['type'=>'absent','date'=>$date]) }}">
<div class="bg-red-100 p-6 rounded-xl shadow hover:shadow-lg transition">
<div class="flex items-center justify-between">
<div>
<p class="text-red-700 font-semibold">Absent</p>
<p class="text-3xl font-bold text-red-800">{{ $absent }}</p>
</div>
<div class="text-red-600 text-3xl">✖</div>
</div>
</div>
</a>

<a href="{{ route('admin.attendance.list',['type'=>'working','date'=>$date]) }}">
<div class="bg-pink-100 p-6 rounded-xl shadow hover:shadow-lg transition">
<div class="flex items-center justify-between">
<div>
<p class="text-pink-700 font-semibold">Working</p>
<p class="text-3xl font-bold text-pink-800">{{ $working->count() }}</p>
</div>
<div class="text-pink-600 text-3xl">💼</div>
</div>
</div>
</a>

</div>


<!-- Working Employees -->

<h3 class="text-xl font-bold mb-4 text-gray-700">
Employees Currently Working
</h3>

<div id="attendance-table" class="bg-white rounded-xl shadow border">

<table class="min-w-full border">

<thead class="bg-gray-200">
<tr>
<th class="p-2 border">Employee</th>
<th class="p-2 border">Clock In</th>
<th class="p-2 border">Working Time</th>
<th class="p-2 border">Location</th>
<th class="p-2 border">Overtime</th>
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

<td class="p-2 border text-center">

@if($attendance->clock_in_latitude && $attendance->clock_in_longitude)

<a href="https://maps.google.com/?q={{ $attendance->clock_in_latitude }},{{ $attendance->clock_in_longitude }}" 
target="_blank"
class="bg-blue-500 text-white px-3 py-1 rounded text-sm">
View Map
</a>

@else

-

@endif

</td>


<!-- Overtime Allow -->

<td class="p-2 border text-center">

<form method="POST" action="{{ route('admin.allow.overtime') }}">

@csrf

<input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

<input type="time"
name="overtime_until"
class="border rounded p-1 text-sm"
required>

<button class="bg-purple-600 text-white px-2 py-1 rounded text-sm mt-1">
Allow OT
</button>

</form>

</td>


</tr>

@endforeach

</tbody>

</table>

</div>


<script>

/*
|--------------------------------------------------------------------------
| LIVE TIMER (updates every second)
|--------------------------------------------------------------------------
*/

function updateWorkingTimers(){

document.querySelectorAll('.working-timer').forEach(function(timer){

let clockIn = new Date(timer.dataset.clockin);
let now = new Date();

let seconds = Math.floor((now - clockIn)/1000);

let hours = Math.floor(seconds/3600);
seconds %= 3600;

let minutes = Math.floor(seconds/60);
seconds %= 60;

timer.innerHTML =
hours + "h " +
minutes + "m " +
seconds + "s";

});

}

setInterval(updateWorkingTimers,1000);
updateWorkingTimers();


/*
|--------------------------------------------------------------------------
| LIVE ATTENDANCE REFRESH (every 10 seconds)
|--------------------------------------------------------------------------
*/

setInterval(function(){

fetch('/admin/live-attendance')

.then(response => response.text())

.then(data => {

document.getElementById('attendance-table').innerHTML = data;

});

},10000);

</script>

</div>

</x-app-layout>
