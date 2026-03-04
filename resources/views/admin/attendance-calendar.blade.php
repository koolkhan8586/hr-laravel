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
<div class="mb-4 flex gap-6 text-sm">

<span class="text-green-600 font-bold">✔ Present</span>

<span class="text-yellow-600 font-bold">⏰ Late</span>

<span class="text-purple-600 font-bold">🕒 Half Day</span>

<span class="text-blue-600 font-bold">🌴 Leave</span>

<span class="text-red-600 font-bold">✖ Absent</span>

</div>

<div class="overflow-x-auto" id="calendarArea">

<table class="w-full border text-sm">

<thead class="bg-gray-200">

<tr>

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

<tr>

<td class="border p-2 font-medium sticky left-0 bg-white z-10">
{{ $user->name }}
</td>

@for($d=1;$d<=$end->day;$d++)

@php

$date = $start->copy()->day($d)->toDateString();

$record = isset($attendances[$user->id])
    ? $attendances[$user->id]->where('date',$date)->first()
    : null;


/*
|--------------------------------------------------------------------------
| FIXED LEAVE CHECK (handles datetime correctly)
|--------------------------------------------------------------------------
*/

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


$dayDate = $start->copy()->day($d);
$isWeekend = $dayDate->isWeekend();

$today = now()->toDateString();

@endphp

<td class="border text-center {{ $isWeekend ? 'bg-red-50' : '' }}">

{{-- Future dates --}}
@if($date > $today)

<span class="text-gray-300">-</span>

{{-- Leave priority --}}
@elseif($leave)

<span class="text-blue-600 font-bold"
title="Leave">🌴</span>

{{-- Attendance --}}
@elseif($record)

@if($record->status == 'present')

<span class="text-green-600 font-bold"
title="Present">✔</span>

@elseif($record->status == 'late')

<span class="text-yellow-600 font-bold"
title="Late">⏰</span>

@elseif($record->status == 'half_day')

<span class="text-purple-600 font-bold"
title="Half Day">🕒</span>

@endif

{{-- Absent --}}
@else

<span class="text-red-500 font-bold"
title="Absent">✖</span>

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

</x-app-layout>
