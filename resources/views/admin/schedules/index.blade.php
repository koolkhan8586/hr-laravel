<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Employee Schedules</h2>

<a href="{{ route('schedules.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded">
Assign Schedule
</a>

<table class="w-full mt-4 border">

<thead class="bg-gray-200">
<tr>
<th class="p-2 border">Employee</th>
<th class="p-2 border">Date</th>
<th class="p-2 border">Shift</th>
</tr>
</thead>

<tbody>

@foreach($schedules as $schedule)

<tr>

<td class="p-2 border">{{ $schedule->user->name }}</td>
<td class="p-2 border">{{ $schedule->date }}</td>
<td class="p-2 border">

@if($schedule->shift)

{{ $schedule->shift->name }}

@else

OFF

@endif

</td>

</tr>

@endforeach

</tbody>

</table>

</div>

</x-app-layout>
