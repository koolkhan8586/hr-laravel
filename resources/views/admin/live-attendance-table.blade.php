<table class="min-w-full border">

<tbody>

@foreach($working as $attendance)

@php
$minutes = \Carbon\Carbon::parse($attendance->clock_in)
->diffInMinutes(now('Asia/Karachi'));

$hours = floor($minutes / 60);
$mins = $minutes % 60;
@endphp

<tr>
<td class="p-2 border">{{ $attendance->user->name ?? 'Unknown' }}</td>
<td class="p-2 border">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i:s') }}</td>
<td class="p-2 border">{{ $hours }}h {{ $mins }}m</td>
</tr>

@endforeach

</tbody>

</table>
