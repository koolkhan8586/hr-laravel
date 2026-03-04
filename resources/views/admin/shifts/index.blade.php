<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Shifts</h2>

<a href="{{ route('shifts.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded">
Create Shift
</a>

<table class="w-full mt-4 border">

<thead class="bg-gray-200">
<tr>
<th class="p-2 border">Name</th>
<th class="p-2 border">Start</th>
<th class="p-2 border">End</th>
<th class="p-2 border">Grace</th>
<th class="p-2 border">Action</th>
</tr>
</thead>

<tbody>

@foreach($shifts as $shift)

<tr>

<td class="p-2 border">{{ $shift->name }}</td>
<td class="p-2 border">{{ $shift->start_time }}</td>
<td class="p-2 border">{{ $shift->end_time }}</td>
<td class="p-2 border">{{ $shift->grace_minutes }} min</td>

<td class="p-2 border">

<a href="{{ route('shifts.edit',$shift->id) }}" class="text-blue-500">Edit</a>

<form action="{{ route('shifts.destroy',$shift->id) }}" method="POST" style="display:inline;">
@csrf
@method('DELETE')
<button class="text-red-500">Delete</button>
</form>

</td>

</tr>

@endforeach

</tbody>

</table>

</div>

</x-app-layout>
