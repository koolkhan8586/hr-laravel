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

</form>

<div class="overflow-x-auto">

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
@endphp

<th class="border p-2 text-center
{{ $isWeekend ? 'bg-red-100' : '' }}">

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

$dayDate = $start->copy()->day($d);
$isWeekend = $dayDate->isWeekend();
@endphp

<td class="border text-center
{{ $isWeekend ? 'bg-red-50' : '' }}">

@if($record)

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

</x-app-layout>
