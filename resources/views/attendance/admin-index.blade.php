<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-6">

    <h2 class="text-2xl font-bold mb-6">Attendance Management</h2>

    {{-- FILTER + EXPORT --}}
    <form method="GET" class="mb-6 flex gap-3 items-center">
        <input type="month"
               name="month"
               value="{{ $month }}"
               class="border rounded px-3 py-2">

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
            Filter
        </button>

        <a href="{{ route('admin.attendance.export',['month'=>$month]) }}"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
           Export Excel
        </a>
    </form>

    {{-- SUMMARY CARDS --}}
    @php
        $present = $records->where('status','present')->count();
        $late    = $records->where('status','late')->count();
        $absent  = $records->where('status','absent')->count();
        $totalHours = round($records->sum('total_hours'),2);
        $daysInMonth = \Carbon\Carbon::parse($month)->daysInMonth;
        $percentage = $daysInMonth > 0 ? round(($present/$daysInMonth)*100,2) : 0;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">

        <div class="bg-green-100 rounded-xl shadow p-6 text-center">
            <div class="text-3xl font-bold text-green-700">{{ $present }}</div>
            <div class="text-gray-600 mt-1">Present</div>
        </div>

        <div class="bg-yellow-100 rounded-xl shadow p-6 text-center">
            <div class="text-3xl font-bold text-yellow-700">{{ $late }}</div>
            <div class="text-gray-600 mt-1">Late</div>
        </div>

        <div class="bg-red-100 rounded-xl shadow p-6 text-center">
            <div class="text-3xl font-bold text-red-700">{{ $absent }}</div>
            <div class="text-gray-600 mt-1">Absent</div>
        </div>

        <div class="bg-blue-100 rounded-xl shadow p-6 text-center">
            <div class="text-3xl font-bold text-blue-700">{{ $totalHours }}</div>
            <div class="text-gray-600 mt-1">Total Hours</div>
        </div>

        <div class="bg-purple-100 rounded-xl shadow p-6 text-center">
            <div class="text-3xl font-bold text-purple-700">{{ $percentage }}%</div>
            <div class="text-gray-600 mt-1">Attendance %</div>
        </div>

    </div>

    {{-- CHART --}}
    <div class="max-w-3xl mx-auto mb-10">
        <canvas id="attendanceChart"></canvas>
    </div>

    {{-- TABLE --}}
    <div class="bg-white shadow rounded overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">Clock In</th>
                    <th class="p-3 text-left">Clock Out</th>
                    <th class="p-3 text-left">Hours</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
            @foreach($records as $r)
                <tr class="border-t hover:bg-gray-50">

                    <td class="p-3">{{ $r->user->name }}</td>

                    <td class="p-3">
                        {{ \Carbon\Carbon::parse($r->clock_in)->format('Y-m-d') }}
                    </td>

                    {{-- Clock In with Map --}}
                    <td class="p-3">
                        @if($r->latitude)
                            <a target="_blank"
                               href="https://www.google.com/maps?q={{ $r->latitude }},{{ $r->longitude }}"
                               class="text-blue-600 underline">
                                {{ \Carbon\Carbon::parse($r->clock_in)->format('H:i:s') }}
                            </a>
                        @else
                            {{ \Carbon\Carbon::parse($r->clock_in)->format('H:i:s') }}
                        @endif
                    </td>

                    {{-- Clock Out --}}
                    <td class="p-3">
                        @if($r->clock_out)
                            {{ \Carbon\Carbon::parse($r->clock_out)->format('H:i:s') }}
                        @else
                            -
                        @endif
                    </td>

                    <td class="p-3">
                        {{ $r->total_hours ?? '-' }}
                    </td>

                    <td class="p-3">
                        @if($r->status=='late')
                            <span class="text-yellow-600 font-semibold">Late</span>
                        @elseif($r->status=='absent')
                            <span class="text-red-600 font-semibold">Absent</span>
                        @else
                            <span class="text-green-600 font-semibold">Present</span>
                        @endif
                    </td>

                    <td class="p-3 space-x-2">

                        <a href="{{ route('admin.attendance.edit',$r->id) }}"
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs shadow">
                           Edit
                        </a>

                        <form action="{{ route('admin.attendance.delete',$r->id) }}"
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
            @endforeach
            </tbody>
        </table>
    </div>

</div>


{{-- CHART SCRIPT --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch("{{ route('admin.attendance.analytics',['month'=>$month]) }}")
.then(res => res.json())
.then(data => {
    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Present','Late','Absent'],
            datasets: [{
                data: [
                    data.present ?? 0,
                    data.late ?? 0,
                    data.absent ?? 0
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
});
</script>

</x-app-layout>
