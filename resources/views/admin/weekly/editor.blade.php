<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Schedule Grid Editor</h2>

@if(session('success'))
<div class="bg-green-200 p-3 mb-4 rounded">
{{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('schedule.editor.update') }}">
@csrf

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

@php
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
@endphp

@foreach($users as $user)

<tr>

<td class="border p-2 font-semibold">{{ $user->name }}</td>

@foreach($days as $day)

@php
$schedule = $user->weeklySchedules->where('day_of_week',$day)->first();
@endphp

<td class="border p-2">

<select name="schedule[{{ $user->id }}][{{ $day }}]" class="w-full border p-1">

<option value="">OFF</option>

@foreach($shifts as $shift)

<option value="{{ $shift->id }}"
@if($schedule && $schedule->shift_id == $shift->id) selected @endif>

{{ $shift->name }}

</option>

@endforeach

</select>

</td>

@endforeach

</tr>

@endforeach

</tbody>

</table>

<button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
Save Changes
</button>

</form>

</div>

</x-app-layout>
