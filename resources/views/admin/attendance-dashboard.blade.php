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

@foreach(\App\Models\User::where('role','employee')->get() as $emp)
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


<!-- Dashboard Cards -->

<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">

<a href="{{ route('admin.attendance.list','present') }}">
<div class="bg-green-100 p-6 rounded-xl text-center shadow hover:shadow-lg transition">
<div class="text-green-700 font-semibold">Present</div>
<div class="text-3xl font-bold text-green-800 mt-2">{{ $present }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','late') }}">
<div class="bg-yellow-100 p-6 rounded-xl text-center shadow hover:shadow-lg transition">
<div class="text-yellow-700 font-semibold">Late</div>
<div class="text-3xl font-bold text-yellow-800 mt-2">{{ $late }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','halfday') }}">
<div class="bg-purple-100 p-6 rounded-xl text-center shadow hover:shadow-lg transition">
<div class="text-purple-700 font-semibold">Half Day</div>
<div class="text-3xl font-bold text-purple-800 mt-2">{{ $halfday }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','leave') }}">
<div class="bg-blue-100 p-6 rounded-xl text-center shadow hover:shadow-lg transition">
<div class="text-blue-700 font-semibold">Leave</div>
<div class="text-3xl font-bold text-blue-800 mt-2">{{ $leave }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','absent') }}">
<div class="bg-red-100 p-6 rounded-xl text-center shadow hover:shadow-lg transition">
<div class="text-red-700 font-semibold">Absent</div>
<div class="text-3xl font-bold text-red-800 mt-2">{{ $absent }}</div>
</div>
</a>

<a href="{{ route('admin.attendance.list','working') }}">
<div class="bg-pink-100 p-6 rounded-xl text-center shadow hover:shadow-lg transition">
<div class="text-pink-700 font-semibold">Working</div>
<p class="text-3xl font-bold text-pink-800 mt-2">{{ $working->count() }}</p>
</div>
</a>

</div>


<!-- Working Employees -->

<h3 class="text-xl font-bold mb-4 text-gray-700">
Employees Currently Working
</h3>

<div id="attendance-table" class="bg-white rounded-xl shadow border">

<table class="w-full border-collapse">

<thead class="bg-gray-100">
<tr>
<th class="p-3 border text-left">Employee</th>
<th class="p-3 border text-left">Clock In</th>
<th class="p-3 border text-left">Working Time</th>
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

<tr class="hover:bg-gray-50">

<td class="p-3 border">
{{ $attendance->user->name ?? 'Unknown' }}
</td>

<td class="p-3 border">
{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s') }}
</td>

<td class="p-3 border">

<span class="working-timer"
data-clockin="{{ $attendance->clock_in }}">

{{ $hours }}h {{ $mins }}m

</span>

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
