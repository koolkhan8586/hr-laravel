<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Weekly Schedules</h2>

<table class="w-full border">

<thead class="bg-gray-200">
<tr>
<th class="border p-2">Employee</th>
<th class="border p-2">Day</th>
<th class="border p-2">Shift</th>
</tr>
</thead>

<tbody>

@foreach($schedules as $schedule)

<tr>
<td class="border p-2">{{ $schedule->user->name }}</td>
<td class="border p-2">{{ $schedule->day_of_week }}</td>
<td class="border p-2">
{{ $schedule->shift->name ?? 'OFF' }}
</td>
</tr>

@endforeach

</tbody>

</table>

</div>

</x-app-layout>
