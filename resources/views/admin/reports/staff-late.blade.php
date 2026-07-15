<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <h2 class="text-2xl font-bold">Staff-wise Late Arrival (Month)</h2>

        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="month"
                   name="month"
                   value="{{ $month }}"
                   class="border rounded px-3 py-2">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Filter
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-yellow-100 rounded-xl shadow p-4">
            <div class="text-sm text-gray-600">Month</div>
            <div class="text-2xl font-bold">{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</div>
        </div>
        <div class="bg-yellow-100 rounded-xl shadow p-4">
            <div class="text-sm text-gray-600">Total Late Arrivals</div>
            <div class="text-2xl font-bold text-yellow-700">{{ $totalLate }}</div>
        </div>
        <div class="bg-yellow-100 rounded-xl shadow p-4">
            <div class="text-sm text-gray-600">Staff with Late Arrival</div>
            <div class="text-2xl font-bold text-yellow-700">{{ $staffWithLate }}</div>
        </div>
    </div>

    <div class="bg-white shadow rounded overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-3 text-left">#</th>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Employee ID</th>
                    <th class="p-3 text-left">Department</th>
                    <th class="p-3 text-center">Late Count</th>
                    <th class="p-3 text-left">Late Dates / Clock In</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $row)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3">{{ $index + 1 }}</td>
                    <td class="p-3 font-medium">{{ $row['user']->name }}</td>
                    <td class="p-3">{{ $row['user']->staff->employee_id ?? '-' }}</td>
                    <td class="p-3">{{ $row['user']->staff->department ?? '-' }}</td>
                    <td class="p-3 text-center font-semibold text-yellow-700">{{ $row['count'] }}</td>
                    <td class="p-3">
                        @if($row['count'] > 0)
                            <ul class="space-y-1">
                                @foreach($row['records'] as $record)
                                    <li>
                                        {{ \Carbon\Carbon::parse($record->date)->format('d M Y') }}
                                        —
                                        {{ $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('h:i A') : '-' }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <span class="text-gray-400">No late arrivals</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-6 text-center text-gray-500">No employees found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

</x-app-layout>
