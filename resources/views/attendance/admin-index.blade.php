<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-6">

<h2 class="text-2xl font-bold mb-6">Attendance Management</h2>

<form method="GET" class="mb-4 flex gap-2">
    <input type="month" name="month" value="{{ $month }}" class="border p-2">
    <button class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
    <a href="{{ route('admin.attendance.export',['month'=>$month]) }}"
       class="bg-green-600 text-white px-4 py-2 rounded">
       Export Excel
    </a>
</form>

<table class="w-full bg-white shadow rounded">
<thead class="bg-gray-200">
<tr>
    <th class="p-2">Employee</th>
    <th class="p-2">Date</th>
    <th class="p-2">Clock In</th>
    <th class="p-2">Clock Out</th>
    <th class="p-2">Hours</th>
    <th class="p-2">Status</th>
    <th class="p-2">Action</th>
</tr>
</thead>

<tbody>
@foreach($records as $r)
<tr class="border-t">
    <td class="p-2">{{ $r->user->name }}</td>
    <td class="p-2">{{ $r->clock_in->format('Y-m-d') }}</td>
    <td class="p-2">{{ $r->clock_in }}</td>
    <td class="p-2">{{ $r->clock_out ?? '-' }}</td>
    <td class="p-2">{{ $r->total_hours ?? '-' }}</td>
    <td class="p-2">
        @if($r->status=='late')
            <span class="text-red-600 font-bold">Late</span>
        @else
            Present
        @endif
    </td>
    <td class="p-2">
        <form method="POST"
              action="{{ route('admin.attendance.delete',$r->id) }}">
            @csrf @method('DELETE')
            <button class="bg-red-600 text-white px-3 py-1 rounded text-xs">
                Delete
            </button>
        </form>
    </td>
</tr>
@endforeach
</tbody>
</table>

</div>
</x-app-layout>
