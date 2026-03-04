<x-app-layout>
<div class="mb-4">
<a href="{{ route('admin.attendance.dashboard') }}"
class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
← Back to Dashboard
</a>
</div>
<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6 capitalize">
{{ $type }} Employees Today
</h2>

<table class="w-full border">

<thead class="bg-gray-200">
<tr>
<th class="p-2 border">Employee</th>
<th class="p-2 border">Clock In</th>
<th class="p-2 border">Clock Out</th>
<th class="p-2 border">Status</th>
</tr>
</thead>

<tbody>

@if($type == 'absent')

@foreach($records as $user)
<tr>
<td class="p-2 border">{{ $user->name }}</td>
<td class="p-2 border">-</td>
<td class="p-2 border">-</td>
<td class="p-2 border text-red-600 font-semibold">Absent</td>
</tr>
@endforeach

@else

@foreach($records as $record)
<tr>
<td class="p-2 border">{{ $record->user->name }}</td>
<td class="p-2 border">{{ $record->clock_in }}</td>
<td class="p-2 border">{{ $record->clock_out ?? '-' }}</td>
<td class="p-2 border">{{ ucfirst($type) }}</td>
</tr>
@endforeach

@endif

</tbody>

</table>

</div>
</x-app-layout>
