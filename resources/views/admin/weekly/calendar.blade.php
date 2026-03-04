<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Employee Weekly Schedule Calendar</h2>

<table class="w-full border">

<thead class="bg-gray-200">
<tr>
<th class="border p-2">Employee</th>
<th class="border p-2">Mon</th>
<th class="border p-2">Tue</th>
<th class="border p-2">Wed</th>
<th class="border p-2">Thu</th>
<th class="border p-2">Fri</th>
<th class="border p-2">Sat</th>
<th class="border p-2">Sun</th>
</tr>
</thead>

<tbody>

@foreach($users as $user)

<tr>

<td class="border p-2 font-semibold">{{ $user->name }}</td>

@php
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
@endphp

@foreach($days as $day)

@php
$schedule = $user->weeklySchedules->where('day_of_week',$day)->first();
@endphp

<td class="border p-2 text-center">

@if($schedule && $schedule->shift)

{{ $schedule->shift->name }}

@else

OFF

@endif

</td>

@endforeach

</tr>

@endforeach

</tbody>

</table>

</div>

</x-app-layout>
