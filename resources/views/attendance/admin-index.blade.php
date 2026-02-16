<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

<h2 class="text-2xl font-bold mb-6">Attendance Management</h2>

<a href="{{ route('admin.attendance.create') }}"
   class="bg-blue-600 text-white px-4 py-2 rounded mb-4 inline-block">
   + Add Attendance
</a>

@if(session('success'))
<div class="bg-green-100 text-green-700 p-3 rounded mb-4">
    {{ session('success') }}
</div>
@endif

<table class="w-full bg-white shadow rounded text-sm">
<thead class="bg-gray-100">
<tr>
<th class="p-3">Employee</th>
<th>Date</th>
<th>Clock In</th>
<th>Clock Out</th>
<th>Total Hours</th>
<th>Action</th>
</tr>
</thead>

<tbody>
@foreach($attendances as $item)
<tr class="border-t">
<td class="p-3">{{ $item->user->name }}</td>
<td>{{ \Carbon\Carbon::parse($item->clock_in)->format('d-m-Y') }}</td>
<td>{{ \Carbon\Carbon::parse($item->clock_in)->format('H:i') }}</td>
<td>
    {{ $item->clock_out ? \Carbon\Carbon::parse($item->clock_out)->format('H:i') : '-' }}
</td>
<td>{{ $item->total_hours ?? '-' }}</td>
<td>
    <a href="{{ route('admin.attendance.edit',$item->id) }}"
       class="text-yellow-600">Edit</a>

    <form action="{{ route('admin.attendance.delete',$item->id) }}"
          method="POST" class="inline">
        @csrf
        @method('DELETE')
        <button class="text-red-600 ml-2">Delete</button>
    </form>
</td>
</tr>
@endforeach
</tbody>
</table>

</div>
</x-app-layout>

