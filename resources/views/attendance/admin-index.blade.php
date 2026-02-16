<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Attendance Management</h2>

        <a href="{{ route('admin.attendance.create') }}"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            + Add Attendance
        </a>
    </div>

    {{-- FILTER --}}
    <form method="GET" class="mb-6 flex gap-3">
        <input type="month"
               name="month"
               value="{{ $month }}"
               class="border px-3 py-2 rounded">

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Filter
        </button>
    </form>

    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- TABLE --}}
    <div class="bg-white shadow rounded overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Clock In</th>
                    <th class="p-3 text-left">Clock Out</th>
                    <th class="p-3 text-left">Total Hours</th>
                    <th class="p-3 text-center">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($records as $record)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3">
                        {{ $record->user->name ?? '-' }}
                    </td>

                    <td class="p-3">
                        {{ \Carbon\Carbon::parse($record->created_at)->format('Y-m-d') }}
                    </td>

                    <td class="p-3">
                        {{ $record->clock_in }}
                    </td>

                    <td class="p-3">
                        {{ $record->clock_out ?? '-' }}
                    </td>

                    <td class="p-3 font-semibold">
                        {{ $record->total_hours ? round($record->total_hours,2) : '-' }}
                    </td>

                    <td class="p-3 text-center space-x-2">

                        {{-- Edit --}}
                        <a href="{{ route('admin.attendance.edit',$record->id) }}"
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs">
                            Edit
                        </a>

                        {{-- Delete --}}
                        <form action="{{ route('admin.attendance.delete',$record->id) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('Delete this attendance?')">
                            @csrf
                            @method('DELETE')
                            <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">
                                Delete
                            </button>
                        </form>

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6"
                        class="text-center p-6 text-gray-500">
                        No attendance found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-app-layout>
