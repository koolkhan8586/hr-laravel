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

</form>
@else
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

<script>

let clockInTime = "{{ $today && $today->clock_in ? $today->clock_in : '' }}";

function updateTimer() {

    if(!clockInTime) return;

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

<script>

let seconds = 0;

function updateTimer() {

seconds++;

let hrs = Math.floor(seconds / 3600);
let mins = Math.floor((seconds % 3600) / 60);
let secs = seconds % 60;

document.getElementById("workingTimer").innerHTML =
String(hrs).padStart(2,'0') + ":" +
String(mins).padStart(2,'0') + ":" +
String(secs).padStart(2,'0');

}

setInterval(updateTimer,1000);

</script>

<script>

function clockOutWithGPS(){

navigator.geolocation.getCurrentPosition(

function(position){

document.getElementById("out_latitude").value =
position.coords.latitude;

document.getElementById("out_longitude").value =
position.coords.longitude;

document.getElementById("clockOutForm").submit();

},

function(){
alert("Location not detected. Please enable GPS.");
},

{
enableHighAccuracy:true
}

);

}

</script>
    
</x-app-layout>
