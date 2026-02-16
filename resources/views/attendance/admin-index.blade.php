<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-6">

    <h2 class="text-2xl font-bold mb-6">Attendance Management</h2>

    {{-- FILTER + EXPORT --}}
    <form method="GET" class="mb-6 flex items-center gap-3">
        <input type="month" name="month" value="{{ $month }}" class="border p-2 rounded">

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            Filter
        </button>

        <a href="{{ route('admin.attendance.export',['month'=>$month]) }}"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
           Export Excel
        </a>
    </form>


    {{-- MONTH SUMMARY CARD --}}
    @php
        $present = $records->where('status','present')->count();
        $late = $records->where('status','late')->count();
        $absent = $records->where('status','absent')->count();
        $totalHours = $records->sum('total_hours');
    @endphp

    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-green-100 p-4 rounded shadow text-center">
            <div class="text-lg font-bold">{{ $present }}</div>
            <div class="text-sm">Present</div>
        </div>

        <div class="bg-yellow-100 p-4 rounded shadow text-center">
            <div class="text-lg font-bold">{{ $late }}</div>
            <div class="text-sm">Late</div>
        </div>

        <div class="bg-red-100 p-4 rounded shadow text-center">
            <div class="text-lg font-bold">{{ $absent }}</div>
            <div class="text-sm">Absent</div>
        </div>

        <div class="bg-blue-100 p-4 rounded shadow text-center">
            <div class="text-lg font-bold">{{ round($totalHours,2) }}</div>
            <div class="text-sm">Total Hours</div>
        </div>
    </div>

    <canvas id="attendanceChart" height="100"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch("{{ route('admin.attendance.analytics',$month) }}")
.then(res => res.json())
.then(data => {
    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(data),
            datasets: [{
                data: Object.values(data)
            }]
        }
    });
});
</script>

    
    {{-- ATTENDANCE TABLE --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-gray-200 text-gray-700">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Clock In</th>
                    <th class="p-3 text-left">Clock Out</th>
                    <th class="p-3 text-left">Hours</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-center">Action</th>
                </tr>
            </thead>

            <tbody>

            @forelse($records->sortByDesc('clock_in') as $r)
                <tr class="border-t hover:bg-gray-50">

                    {{-- Employee --}}
                    <td class="p-3 font-medium">
                        {{ $r->user->name }}
                    </td>

                    {{-- Date (safe formatting) --}}
                    <td class="p-3">
                        {{ \Carbon\Carbon::parse($r->clock_in)->format('Y-m-d') }}
                    </td>

                    {{-- Clock In clickable to map --}}
                    <td class="p-3">
                        @if($r->clock_in)
                            <a target="_blank"
                               class="text-blue-600 underline"
                               href="https://www.google.com/maps?q={{ $r->latitude }},{{ $r->longitude }}">
                                {{ \Carbon\Carbon::parse($r->clock_in)->format('H:i:s') }}
                            </a>
                        @else
                            -
                        @endif
                    </td>

                    {{-- Clock Out clickable to map --}}
                    <td class="p-3">
                        @if($r->clock_out)
                            <a target="_blank"
                               class="text-blue-600 underline"
                               href="https://www.google.com/maps?q={{ $r->latitude }},{{ $r->longitude }}">
                                {{ \Carbon\Carbon::parse($r->clock_out)->format('H:i:s') }}
                            </a>
                        @else
                            -
                        @endif
                    </td>

                    {{-- Hours --}}
                    <td class="p-3 font-semibold">
                        {{ $r->total_hours ? round($r->total_hours,2) : '-' }}
                    </td>

                    {{-- Status --}}
                    <td class="p-3">
                        @if($r->status == 'late')
                            <span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded text-xs font-bold">
                                Late
                            </span>
                        @elseif($r->status == 'absent')
                            <span class="bg-red-200 text-red-800 px-2 py-1 rounded text-xs font-bold">
                                Absent
                            </span>
                        @else
                            <span class="bg-green-200 text-green-800 px-2 py-1 rounded text-xs font-bold">
                                Present
                            </span>
                        @endif
                    </td>

                    {{-- ACTIONS --}}
                    <td class="p-3 text-center space-x-2">

                        <a href="{{ route('admin.attendance.edit',$r->id) }}"
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs shadow">
                           Edit
                        </a>

                        <form action="{{ route('admin.attendance.destroy',$r->id) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('Delete this attendance?')">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs shadow">
                                Delete
                            </button>
                        </form>

                    </td>

                </tr>

            @empty
                <tr>
                    <td colspan="7" class="p-6 text-center text-gray-500">
                        No attendance records found.
                    </td>
                </tr>
            @endforelse

            </tbody>
        </table>

    </div>

</div>
</x-app-layout>
