<x-app-layout>

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

<a href="{{ route('attendance.index') }}"
class="bg-green-500 text-white px-8 py-3 rounded-lg shadow hover:bg-green-600">
Clock In / Clock Out
</a>
<div class="mt-2 text-sm text-green-600 font-semibold">
Status: Present
</div>
</div>

<!-- WORKING TIME -->

<div class="bg-white p-5 rounded-xl shadow text-center">

<h3 class="text-gray-600 mb-2">Working Time Today</h3>

<div id="workingTimer" class="text-3xl font-bold text-blue-600">
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
    
</x-app-layout>
