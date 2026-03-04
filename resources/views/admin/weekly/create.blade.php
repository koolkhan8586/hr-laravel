<x-app-layout>

<div class="max-w-5xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Weekly Schedule Assignment</h2>

<form method="POST" action="{{ route('weekly.schedule') }}">

@csrf

<label class="font-bold">Select Employees</label>

<div class="border p-3 mb-4 h-40 overflow-y-scroll">

@foreach($users as $user)

<label class="block">
<input type="checkbox" name="users[]" value="{{ $user->id }}">
{{ $user->name }}
</label>

@endforeach

</div>

<table class="w-full border">

<tr>
<th>Day</th>
<th>Shift</th>
</tr>

@php
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
@endphp

@foreach($days as $day)

<tr>

<td class="border p-2">{{ $day }}</td>

<td class="border p-2">

<select name="{{ $day }}" class="w-full border p-1">

<option value="">OFF</option>

@foreach($shifts as $shift)

<option value="{{ $shift->id }}">{{ $shift->name }}</option>

@endforeach

</select>

</td>

</tr>

@endforeach

</table>

<button class="mt-4 bg-green-600 text-white px-4 py-2 rounded">
Save Weekly Schedule
</button>

</form>

</div>

</x-app-layout>
