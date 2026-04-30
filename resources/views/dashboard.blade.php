<x-app-layout>

@if(session('success'))
<script>
alert("{{ session('success') }}");
</script>
@endif

<x-slot name="header">
<div class="mobile-header flex items-center justify-between w-full">

<div class="flex items-center gap-3">
<span class="font-semibold text-lg">
LSAF HR
</span>
</div>

</div>
</x-slot>

<div class="space-y-4">

<!-- GREETING -->

<div class="bg-white p-4 rounded-xl shadow">
<h2 class="text-lg font-semibold">
As-salamu alaykum (السلام عليكم) {{ Auth::user()->name }}
</h2>
<p class="text-sm text-gray-500">
Welcome to your HR dashboard
</p>
</div>

<!-- ✅ NEW LIVE DATE & TIME CLOCK (ADDED) -->

<div class="bg-white p-4 rounded-xl shadow text-center">
<h5 id="currentDate" class="font-semibold text-gray-700"></h5>
<h2 id="currentTime" class="text-blue-600 text-2xl font-bold"></h2>
</div>

@php

$nextHoliday = \App\Models\Holiday::where(function($q){

$q->where('for_all',1)
  ->orWhere('user_id',auth()->id());

})
->whereDate('start_date','>=',now())
->orderBy('start_date')
->first();

@endphp

<!-- Next Holiday Widget -->

<div class="bg-white shadow rounded-xl p-5 text-center">

<h3 class="text-gray-600 mb-2">
Next Holiday
</h3>

@if($nextHoliday)

<div class="text-lg font-bold text-green-700">
{{ $nextHoliday->title }}
</div>

<div class="text-sm text-gray-500">

{{ \Carbon\Carbon::parse($nextHoliday->start_date)->format('d M Y') }}

(
{{ \Carbon\Carbon::parse($nextHoliday->start_date)->format('l') }}
)

</div>

@else

<div class="text-gray-400">
No upcoming holidays
</div>

@endif

</div>



<!-- CLOCK IN / CLOCK OUT -->

<div class="bg-white p-5 rounded-xl shadow text-center">

<h3 class="text-gray-600 mb-2">Attendance</h3>

@php
$today = \App\Models\Attendance::where('user_id', auth()->id())
->whereDate('created_at', today())
->first();
@endphp


@if(!$today)

<form id="clockInForm" method="POST" action="{{ route('attendance.clockin') }}">
@csrf

<input type="hidden" name="latitude" id="latitude">
<input type="hidden" name="longitude" id="longitude">

<button type="button"
onclick="clockInWithGPS()"
class="bg-green-500 text-white px-6 py-2 rounded-lg shadow hover:bg-green-600">

Clock In

</button>
<div id="locationStatus" class="mt-2 font-semibold text-sm"></div>

</form>


@elseif($today && !$today->clock_out)

<form id="clockOutForm" method="POST" action="{{ route('attendance.clockout') }}">
@csrf

<input type="hidden" name="latitude" id="out_latitude">
<input type="hidden" name="longitude" id="out_longitude">

<button type="button"
onclick="clockOutWithGPS()"
class="bg-red-500 text-white px-6 py-2 rounded-lg shadow hover:bg-red-600">

Clock Out

</button>

</form>


@else

<button
class="bg-gray-400 text-white px-6 py-2 rounded-lg shadow cursor-not-allowed"
disabled>

Today's Attendance Completed

</button>

@endif


@if(!$today)

<div class="mt-2 text-gray-500 text-sm">
Status: Not Clocked In
</div>

@elseif($today && !$today->clock_out)

<div class="mt-2 text-green-600 text-sm font-semibold">
Status: Present
</div>

@else

<div class="mt-2 text-blue-600 text-sm font-semibold">
Status: Completed
</div>

@endif

</div>



<!-- WORKING TIME -->

<div class="bg-white p-5 rounded-xl shadow text-center">

<h3 class="text-gray-600 mb-2">Working Time Today</h3>

<div id="workingTimer" class="text-2xl font-bold text-blue-600">
00:00:00
</div>

</div>



<!-- QUICK ACTIONS -->

<div class="grid grid-cols-2 gap-3">

<a href="{{ route('attendance.index') }}"
class="bg-white shadow rounded-xl p-4 text-center hover:bg-gray-50">

<div class="text-2xl">⏰</div>
<div class="text-sm mt-1">My Attendance</div>

</a>


<a href="{{ route('leave.index') }}"
class="bg-white shadow rounded-xl p-4 text-center hover:bg-gray-50">

<div class="text-2xl">📅</div>
<div class="text-sm mt-1">My Leave</div>

</a>


<a href="{{ route('salary.index') }}"
class="bg-white shadow rounded-xl p-4 text-center hover:bg-gray-50">

<div class="text-2xl">💰</div>
<div class="text-sm mt-1">Salary Slips</div>

</a>


<a href="{{ route('loan.my') }}"
class="bg-white shadow rounded-xl p-4 text-center hover:bg-gray-50">

<div class="text-2xl">🏦</div>
<div class="text-sm mt-1">My Loans</div>

</a>

</div>

</div>



<!-- ✅ NEW CLOCK SCRIPT (ADDED) -->

<script>
function updateClock() {
    const now = new Date();

    const dateOptions = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };

    const date = now.toLocaleDateString('en-GB', dateOptions);
    const time = now.toLocaleTimeString('en-GB', { hour12: false });

    document.getElementById('currentDate').innerText = date;
    document.getElementById('currentTime').innerText = time;
}

setInterval(updateClock, 1000);
updateClock();
</script>



<!-- TIMER SCRIPT -->

<script>

let clockInTime = "{{ $today && $today->clock_in ? $today->clock_in : '' }}";
let clockOutTime = "{{ $today && $today->clock_out ? $today->clock_out : '' }}";

function updateTimer(){

if(!clockInTime) return;

if(clockOutTime) return;

let start = new Date(clockInTime);
let now = new Date();

let diff = Math.floor((now - start) / 1000);

let hrs = Math.floor(diff / 3600);
let mins = Math.floor((diff % 3600) / 60);
let secs = diff % 60;

document.getElementById("workingTimer").innerHTML =
String(hrs).padStart(2,'0') + ":" +
String(mins).padStart(2,'0') + ":" +
String(secs).padStart(2,'0');

}

setInterval(updateTimer,1000);

</script>
