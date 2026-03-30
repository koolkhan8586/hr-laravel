<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-4">Attendance</h2>

{{-- Monthly Filter --}}
<form method="GET" class="mb-6 flex gap-2">
    <input type="month" name="month" value="{{ $month }}" class="border p-2">
    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Filter
    </button>
</form>

@php
$today = \Carbon\Carbon::today('Asia/Karachi')->toDateString();

$todayAttendance = \App\Models\Attendance::where('user_id', auth()->id())
    ->whereDate('created_at', $today)
    ->latest()
    ->first();
@endphp

@if($todayShift)

<div class="bg-blue-100 border border-blue-300 text-blue-800 p-4 rounded mb-4">
<strong>Today's Shift</strong><br>

{{ $todayShift->name }}<br>

{{ \Carbon\Carbon::parse($todayShift->start_time)->format('H:i') }}
-
{{ \Carbon\Carbon::parse($todayShift->end_time)->format('H:i') }}

</div>

@else

<div class="bg-yellow-100 border border-yellow-300 text-yellow-800 p-4 rounded mb-4">
<strong>Today is your OFF day</strong>
</div>

@endif

    
{{-- ===============================
    TODAY ATTENDANCE CARD
================================ --}}
<div class="bg-white shadow rounded-lg p-6 mb-6 border">

<div class="flex justify-between items-center">

<div>

<h3 class="text-lg font-bold">Today's Attendance</h3>

{{-- ✅ LOCATION STATUS --}}
@if($todayAttendance && $todayAttendance->location_status)
    <p class="mt-1 text-sm font-semibold
        {{ $todayAttendance->location_status == 'inside' ? 'text-green-600' : 'text-yellow-600' }}">
        
        📍 Location Status:
        {{ ucfirst($todayAttendance->location_status) }}
    </p>
@endif

@if(!$todayAttendance)
<p class="text-gray-500">Not Clocked In</p>

@elseif($todayAttendance->clock_in && !$todayAttendance->clock_out)

<p class="text-green-600 font-semibold">
Working since {{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('h:i A') }}
</p>

@elseif($todayAttendance->clock_out)

<p class="text-blue-600 font-semibold">
Completed
</p>

@else

<p class="text-yellow-600">
Absent (Auto marked)
</p>

@endif

</div>

<div>

@if(!$todayAttendance || !$todayAttendance->clock_in)

<button onclick="clockIn()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded text-lg shadow">
Clock In
</button>

@elseif($todayAttendance && !$todayAttendance->clock_out)

<button onclick="clockOut()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded text-lg shadow">
Clock Out
</button>

@else

<button disabled class="bg-gray-400 text-white px-6 py-3 rounded text-lg">
Completed
</button>

@endif

</div>

</div>

</div>


{{-- ===============================
    LIVE WORKING TIMER
================================ --}}
@if($todayAttendance && $todayAttendance->clock_in && !$todayAttendance->clock_out)

<div class="bg-green-50 border border-green-200 p-4 rounded mb-6">

<div class="text-lg font-semibold text-green-700">
Working Time
</div>

<div id="workingTimer" class="text-2xl font-bold mt-2">
00:00:00
</div>

</div>

@endif


<table class="w-full border">

<thead class="bg-gray-200">
<tr>
<th class="p-2 border">Date</th>
<th class="p-2 border">Clock In</th>
<th class="p-2 border">Clock Out</th>
<th class="p-2 border">Total Hours</th>
<th class="p-2 border">Clock In Map</th>
<th class="p-2 border">Clock Out Map</th>
</tr>
</thead>

<tbody>

@foreach($records as $record)

<tr>

<td class="p-2 border">
{{ \Carbon\Carbon::parse($record->created_at)->format('Y-m-d') }}
</td>

<td class="p-2 border">
{{ $record->clock_in }}
</td>

<td class="p-2 border">
{{ $record->clock_out }}
</td>

<td class="p-2 border">
{{ $record->total_hours ? round($record->total_hours,2) : '-' }}
</td>


<td class="p-2 border text-center">

@if($record->clock_in_latitude && $record->clock_in_longitude)

<iframe
width="180"
height="120"
style="border:0"
loading="lazy"
allowfullscreen
src="https://www.google.com/maps?q={{ $record->clock_in_latitude }},{{ $record->clock_in_longitude }}&hl=en&z=15&output=embed">
</iframe>

@else
-
@endif

</td>


<td class="p-2 border text-center">

@if($record->clock_out_latitude && $record->clock_out_longitude)

<iframe
width="180"
height="120"
style="border:0"
loading="lazy"
allowfullscreen
src="https://www.google.com/maps?q={{ $record->clock_out_latitude }},{{ $record->clock_out_longitude }}&hl=en&z=15&output=embed">
</iframe>

@else
-
@endif

</td>

</tr>

@endforeach

</tbody>

</table>


@php
$total = $records->sum('total_hours');
@endphp


<div class="mt-6 text-lg font-bold">
Total Hours This Month: {{ round($total,2) }}
</div>


</div>


<script>


@if($todayAttendance && $todayAttendance->clock_in && !$todayAttendance->clock_out)

let clockInTime = new Date("{{ \Carbon\Carbon::parse($todayAttendance->clock_in)->format('Y-m-d H:i:s') }}").getTime();

setInterval(function(){

let now = new Date().getTime();

let diff = now - clockInTime;

let hours = Math.floor(diff / (1000 * 60 * 60));
let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
let seconds = Math.floor((diff % (1000 * 60)) / 1000);

document.getElementById("workingTimer").innerHTML =
String(hours).padStart(2,'0') + ":" +
String(minutes).padStart(2,'0') + ":" +
String(seconds).padStart(2,'0');

},1000);

@endif



function clockIn() {

if (!navigator.geolocation) {
alert("Geolocation not supported.");
return;
}

navigator.geolocation.getCurrentPosition(

function(position) {

fetch("{{ route('attendance.clockin') }}", {

method:'POST',

headers:{
'Content-Type':'application/json',
'X-CSRF-TOKEN':'{{ csrf_token() }}'
},

body:JSON.stringify({

latitude:position.coords.latitude,
longitude:position.coords.longitude

})

})

.then(res => res.text())

.then(text => {

console.log("RAW RESPONSE:", text);

try {

const data = JSON.parse(text);

alert(data.message);

if(data.success){
location.reload();
}

} catch(e) {

alert("Server did not return valid JSON");

}

})

.catch(error => {

console.error(error);

alert("Something went wrong");

});

},

function(){
alert("Please allow location access.");
},

{
enableHighAccuracy:true,
timeout:10000,
maximumAge:0
}

);

}


function clockOut() {

if (!navigator.geolocation) {
alert("Geolocation not supported.");
return;
}

navigator.geolocation.getCurrentPosition(function(position) {

fetch("{{ route('attendance.clockout') }}", {

method:'POST',

headers:{
'Content-Type':'application/json',
'X-CSRF-TOKEN':'{{ csrf_token() }}'
},

body:JSON.stringify({

latitude:position.coords.latitude,
longitude:position.coords.longitude

})

})

.then(res => res.json())

.then(data => {

alert(data.message);

if(data.success){
location.reload();
}

})

.catch(() => alert("Something went wrong"));

},

function(){
alert("Please allow location access.");
});

}

</script>



</x-app-layout>
