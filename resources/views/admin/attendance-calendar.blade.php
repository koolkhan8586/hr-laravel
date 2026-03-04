<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-xl font-bold mb-6">Monthly Attendance Calendar</h2>

<form method="GET" class="mb-6">

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

<th class="border p-2">Employee</th>

@for($d=1;$d<=$end->day;$d++)
<th class="border p-2 text-center">{{ $d }}</th>
@endfor

</tr>

</thead>

<tbody>

@foreach($users as $user)

<tr>

<td class="border p-2 font-medium">
{{ $user->name }}
</td>

@for($d=1;$d<=$end->day;$d++)

@php

$date = $start->copy()->day($d)->toDateString();

$record = $attendances[$user->id]->where('date',$date)->first() ?? null;

@endphp

<td class="border text-center">

@if($record)

@if($record->status == 'present')
✔
@elseif($record->status == 'late')
⏰
@elseif($record->status == 'half_day')
🕒
@endif

@else
✖
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
