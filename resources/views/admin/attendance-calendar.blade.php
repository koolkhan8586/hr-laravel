<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-xl font-bold mb-6">Monthly Attendance Calendar</h2>

<form method="GET" class="mb-6 flex items-center gap-3">

<input type="month"
name="month"
value="{{ $month }}"
class="border rounded p-2">

<button class="bg-blue-600 text-white px-4 py-2 rounded">
View
</button>

<button type="button"
onclick="printCalendar()"
class="bg-green-600 text-white px-4 py-2 rounded">
Print / Export
</button>

</form>

{{-- Legend --}}

<div class="mb-4 flex gap-6 text-sm flex-wrap">

<span class="text-green-600 font-bold">✔ Present</span>
<span class="text-yellow-600 font-bold">⏰ Late</span>
<span class="text-purple-600 font-bold">🕒 Half Day</span>
<span class="text-blue-600 font-bold">🌴 Leave</span>
<span class="text-blue-600 font-bold">🌅 Morning Leave</span>
<span class="text-blue-600 font-bold">🌇 Afternoon Leave</span>
<span class="text-indigo-600 font-bold">🏠 Work From Home</span>
<span class="text-red-600 font-bold">🎉 Holiday</span>
<span class="text-gray-400 font-bold">- Weekend / Future</span>
<span class="text-red-600 font-bold">✖ Absent</span>

</div>

<!-- 🔥 Employee Filter (NEW) -->
<div class="mb-4 p-3 bg-white rounded shadow">

<strong>👁️ Show / Hide Employees:</strong>

<div class="flex flex-wrap gap-3 mt-2">

@foreach($users as $user)
<label class="flex items-center gap-1 text-sm">
<input type="checkbox"
class="employee-toggle"
value="{{ $user->id }}"
checked>
{{ $user->name }}
</label>
@endforeach

</div>

</div>
    
<div class="overflow-x-auto" id="calendarArea">

<table class="w-full border text-sm">

<thead class="bg-gray-200">

<tr class="employee-row" data-id="{{ $user->id }}">

<th class="border p-2 sticky left-0 bg-gray-200 z-10">
Employee
</th>

@for($d=1;$d<=$end->day;$d++)

@php
$dayDate = $start->copy()->day($d);
$isWeekend = $dayDate->isWeekend();
$isToday = $dayDate->toDateString() == now()->toDateString();
@endphp

<th class="border p-2 text-center
{{ $isWeekend ? 'bg-red-100' : '' }}
{{ $isToday ? 'bg-green-200' : '' }}">

<div class="font-semibold">
{{ $d }}
</div>

<div class="text-xs text-gray-600">
{{ $dayDate->format('D') }}
</div>

</th>

@endfor

</tr>

</thead>

<tbody>

@foreach($users as $user)

<tr class="employee-row" data-id="{{ $user->id }}">

<td class="border p-2 font-medium sticky left-0 bg-white z-10">
{{ $user->name }}
</td>

@for($d=1;$d<=$end->day;$d++)

@php

$date = $start->copy()->day($d)->toDateString();
$today = now()->toDateString();
$dayDate = $start->copy()->day($d);
$isWeekend = $dayDate->isWeekend();

/* Attendance */

$key = $user->id.'_'.$date;
$record = $attendances[$key][0] ?? null;

/* Leave */

$leave = null;

if(isset($leaves[$user->id])){
foreach($leaves[$user->id] as $l){

if(
\Carbon\Carbon::parse($l->start_date)->toDateString() <= $date &&
\Carbon\Carbon::parse($l->end_date)->toDateString() >= $date
){
$leave = $l;
break;
}

}
}

/* Work From Home */

$wfh = null;

if(isset($wfhData[$user->id])){
foreach($wfhData[$user->id] as $w){

if(
\Carbon\Carbon::parse($w->start_date)->toDateString() <= $date &&
\Carbon\Carbon::parse($w->end_date)->toDateString() >= $date
){
$wfh = $w;
break;
}

}
}

/* Holiday */

$holiday = null;

foreach($holidays as $h){

$isForEmployee = false;

if($h->for_all == 1){
$isForEmployee = true;
}else{

foreach($h->users as $u){
if($u->id == $user->id){
$isForEmployee = true;
break;
}
}

}

if(
$isForEmployee &&
\Carbon\Carbon::parse($h->start_date)->toDateString() <= $date &&
\Carbon\Carbon::parse($h->end_date)->toDateString() >= $date
){
$holiday = $h;
break;
}

}

@endphp

<td
class="border text-center cursor-pointer hover:bg-blue-50 {{ $isWeekend ? 'bg-red-50' : '' }}"
onclick="showAttendance('{{ $user->id }}','{{ $date }}','{{ $user->name }}')"
>

@if($date > $today)

<span class="text-gray-300">-</span>

@elseif($holiday)

<span
class="text-red-600 font-bold cursor-pointer"
title="{{ $holiday->title }}"
onclick="event.stopPropagation(); showHoliday('{{ $holiday->title }}','{{ $holiday->start_date }}','{{ $holiday->end_date }}')">
🎉
</span>

@elseif($wfh)

<span class="text-indigo-600 font-bold"
title="Work From Home">🏠</span>

@elseif($leave)

@if($leave->duration_type == 'half_day')

@if($leave->half_day_type == 'morning')

<span class="text-blue-600 font-bold"
title="Half Day Leave (Morning)">🌅</span>

@elseif($leave->half_day_type == 'afternoon')

<span class="text-blue-600 font-bold"
title="Half Day Leave (Afternoon)">🌇</span>

@endif

@else

<span class="text-blue-600 font-bold"
title="Full Day Leave">🌴</span>

@endif

@elseif($record)

@if($record->status == 'present')

<span class="text-green-600 font-bold">✔</span>

@elseif($record->status == 'late')

<span class="text-yellow-600 font-bold">⏰</span>

@elseif($record->status == 'half_day')

<span class="text-purple-600 font-bold">🕒</span>

@endif

@elseif($isWeekend)

<span class="text-gray-300">-</span>

@else

<span class="text-red-500 font-bold" title="Absent">✖</span>

@endif

</td>

@endfor
</tr>

@endforeach

</tbody>

</table>

</div>

</div>

<script>

function printCalendar(){

let printContents = document.getElementById('calendarArea').innerHTML;
let originalContents = document.body.innerHTML;

document.body.innerHTML = printContents;
window.print();

document.body.innerHTML = originalContents;
location.reload();

}

</script>

<div id="attendanceModal"
class="fixed inset-0 bg-black bg-opacity-40 hidden flex items-center justify-center z-50">

<div class="bg-white rounded-xl shadow-xl w-96 p-6">

<h3 class="text-lg font-bold mb-4 text-gray-700" id="modalTitle"></h3>

<div id="modalContent" class="text-sm text-gray-600 space-y-2">

Loading...

</div>

<div class="mt-6 text-right">

<button
onclick="closeModal()"
class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
Close
</button>

</div>

</div>

</div>

<script>

function showAttendance(userId,date,userName){

document.getElementById('attendanceModal').classList.remove('hidden');

document.getElementById('modalTitle').innerHTML =
userName + " - " + date;

fetch('/admin/attendance-details/'+userId+'/'+date)

.then(res => res.json())

.then(data => {

let html = '';

if(data){

html += "<p><b>Status:</b> "+(data.status ?? '-')+"</p>";
html += "<p><b>Clock In:</b> "+(data.clock_in ?? '-')+"</p>";
html += "<p><b>Clock Out:</b> "+(data.clock_out ?? '-')+"</p>";
html += "<p><b>Total Hours:</b> "+(data.total_hours ?? '-')+"</p>";

}else{

html = "No attendance record.";

}

document.getElementById('modalContent').innerHTML = html;

}).catch(()=>{

document.getElementById('modalContent').innerHTML = "Unable to load attendance.";

});

}

function showHoliday(title,start,end){

document.getElementById('attendanceModal').classList.remove('hidden');

document.getElementById('modalTitle').innerHTML =
"Holiday";

let html = "";

html += "<p><b>Title:</b> "+title+"</p>";
html += "<p><b>Start Date:</b> "+start+"</p>";
html += "<p><b>End Date:</b> "+end+"</p>";
html += "<p class='text-green-600 font-semibold mt-2'>This day is marked as a holiday.</p>";

document.getElementById('modalContent').innerHTML = html;

}

function closeModal(){
document.getElementById('attendanceModal').classList.add('hidden');
}

</script>

<script>

// 🔥 Employee Show/Hide Toggle
document.querySelectorAll('.employee-toggle').forEach(toggle => {

toggle.addEventListener('change', function () {

let empId = this.value;
let row = document.querySelector(`tr[data-id="${empId}"]`);

if (!row) return;

if (this.checked) {
row.style.display = '';
} else {
row.style.display = 'none';
}

});

});

</script>

</x-app-layout>
