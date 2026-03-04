<x-app-layout>

<div class="max-w-4xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">
Edit Weekly Schedule - {{ $user->name }}
</h2>

<form method="POST" action="{{ route('weekly.schedule') }}">

@csrf

<input type="hidden" name="users[]" value="{{ $user->id }}">

<table class="w-full border">

<tr>
<th class="border p-2">Day</th>
<th class="border p-2">Shift</th>
</tr>

@php
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
@endphp

@foreach($days as $day)

@php
$schedule = $schedules->where('day_of_week',$day)->first();
@endphp

<tr>

<td class="border p-2">{{ $day }}</td>

<td class="border p-2">

<select name="{{ $day }}" class="w-full border p-2">

<option value="">OFF</option>

@foreach($shifts as $shift)

<option value="{{ $shift->id }}"
@if($schedule && $schedule->shift_id == $shift->id) selected @endif>

{{ $shift->name }}

</option>

@endforeach

</select>

</td>

</tr>

@endforeach

</table>

<button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
Update Schedule
</button>

</form>

</div>

</x-app-layout>
