<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6">Live Attendance Dashboard</h2>

<div class="grid grid-cols-3 gap-6 mb-8">

<div class="bg-green-100 p-4 rounded shadow">
<h3 class="font-bold text-green-700">🟢 Currently Working</h3>
<p class="text-3xl">{{ $working->count() }}</p>
</div>

<div class="bg-yellow-100 p-4 rounded shadow">
<h3 class="font-bold text-yellow-700">🟡 Late Today</h3>
<p class="text-3xl">{{ $late->count() }}</p>
</div>

<div class="bg-red-100 p-4 rounded shadow">
<h3 class="font-bold text-red-700">🔴 Absent Today</h3>
<p class="text-3xl">{{ $absent->count() }}</p>
</div>

</div>


<h3 class="text-xl font-bold mb-4">Employees Currently Working</h3>

<table class="w-full border">

<thead class="bg-gray-200">
<tr>
<th class="p-2 border">Employee</th>
<th class="p-2 border">Clock In</th>
<th class="p-2 border">Live Working Time</th>
</tr>
</thead>

<tbody>

@foreach($working as $record)

<tr>

<td class="p-2 border">
{{ $record->user->name }}
</td>

<td class="p-2 border">
{{ $record->clock_in }}
</td>

<td class="p-2 border">
<span class="timer"
data-time="{{ \Carbon\Carbon::parse($record->clock_in)->timestamp }}">
00:00:00
</span>
</td>

</tr>

@endforeach

</tbody>

</table>

</div>


<script>

document.querySelectorAll(".timer").forEach(function(el){

let start = el.dataset.time * 1000;

setInterval(function(){

let now = new Date().getTime();
let diff = now - start;

let hours = Math.floor(diff / (1000*60*60));
let minutes = Math.floor((diff % (1000*60*60)) / (1000*60));
let seconds = Math.floor((diff % (1000*60)) / 1000);

el.innerHTML =
String(hours).padStart(2,'0') + ":" +
String(minutes).padStart(2,'0') + ":" +
String(seconds).padStart(2,'0');

},1000);

});

</script>

</x-app-layout>
