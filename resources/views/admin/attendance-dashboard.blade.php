<x-app-layout>

<h3 class="text-xl font-bold mb-4">Employees Currently Working</h3>

<table class="w-full border">
<thead class="bg-gray-200">
<tr>
<th class="p-2 border">Employee</th>
<th class="p-2 border">Clock In</th>
<th class="p-2 border">Working Time</th>
</tr>
</thead>

<tbody>

@foreach($working as $attendance)

@php
$minutes = \Carbon\Carbon::parse($attendance->clock_in)
->diffInMinutes(now());

$hours = floor($minutes / 60);
$mins = $minutes % 60;
@endphp

<tr>
<td class="p-2 border">{{ $attendance->user->name }}</td>
<td class="p-2 border">{{ $attendance->clock_in }}</td>
<td class="p-2 border">{{ $hours }}h {{ $mins }}m</td>
</tr>

@endforeach

</tbody>
</table>

</div>
</x-app-layout>
