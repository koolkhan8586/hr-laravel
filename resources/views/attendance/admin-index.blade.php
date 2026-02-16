<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-6">

    <h2 class="text-2xl font-bold mb-6">Attendance Management</h2>

    {{-- FILTER + EXPORT --}}
    <form method="GET" class="mb-6 flex gap-3 items-center">
        <input type="month"
               name="month"
               value="{{ $month }}"
               class="border rounded px-3 py-2">

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            Filter
        </button>

        <a href="{{ route('admin.attendance.export',['month'=>$month]) }}"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
           Export Excel
        </a>
    </form>

    @php
        $present = $records->where('status','present')->count();
        $late = $records->where('status','late')->count();
        $absent = $records->where('status','absent')->count();
        $totalHours = round($records->sum('total_hours'),2);

        $daysInMonth = \Carbon\Carbon::parse($month)->daysInMonth;
        $percentage = $daysInMonth > 0
            ? round((($present+$late)/$daysInMonth)*100,2)
            : 0;
    @endphp

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-green-100 rounded-xl shadow p-6 text-center">
            <div class="text-gray-600 mb-1">Present</div>
<div class="text-4xl font-bold text-green-700">{{ $present }}</div>
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
    <div class="text-gray-600">Attendance %</div>
</div>

    </div>

    {{-- DONUT CHART --}}
    <div class="flex justify-center mt-8">
    <div class="w-80">
        <canvas id="attendanceChart"></canvas>
    </div>
</div>

    {{-- ATTENDANCE TABLE --}}
    <table class="w-full bg-white shadow rounded overflow-hidden">
        <thead class="bg-gray-200 text-gray-700">
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
                {{ \Carbon\Carbon::parse($r->clock_in)
                    ->timezone('Asia/Karachi')
                    ->format('Y-m-d') }}
            </td>

            {{-- Clock In with Map --}}
            <td class="p-3">
                @if($r->latitude && $r->longitude)
                    <a target="_blank"
                       class="text-blue-600 underline"
                       href="https://www.google.com/maps?q={{ $r->latitude }},{{ $r->longitude }}">
                        {{ \Carbon\Carbon::parse($r->clock_in)
                            ->timezone('Asia/Karachi')
                            ->format('H:i:s') }}
                    </a>
                @else
                    {{ \Carbon\Carbon::parse($r->clock_in)
                        ->timezone('Asia/Karachi')
                        ->format('H:i:s') }}
                @endif
            </td>

            {{-- Clock Out --}}
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

            <td class="p-3">
                {{ $r->total_hours ?? '-' }}
            </td>

            <td class="p-3">
                @if($r->status=='late')
                    <span class="text-yellow-600 font-bold">Late</span>
                @elseif($r->status=='absent')
                    <span class="text-red-600 font-bold">Absent</span>
                @elseif($r->status=='half-day')
                    <span class="text-purple-600 font-bold">Half Day</span>
                @else
                    <span class="text-green-600 font-bold">Present</span>
                @endif
            </td>

            <td class="p-3 space-x-2">

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
        @endforeach
        </tbody>
    </table>

</div>

{{-- CHART JS --}}
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
                ],
                backgroundColor: [
                    '#22c55e',
                    '#facc15',
                    '#ef4444'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
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
