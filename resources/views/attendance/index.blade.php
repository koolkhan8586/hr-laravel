<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-xl sm:text-2xl font-bold mb-4">Attendance</h2>

    {{-- Monthly Filter --}}
    <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-2 sm:items-center">
        <input type="month" name="month" value="{{ $month }}"
               class="border p-2 rounded w-full sm:w-auto">
        <button class="bg-blue-600 text-white px-4 py-2 rounded w-full sm:w-auto">
            Filter
        </button>
    </form>

    {{-- Clock Buttons --}}
    <div class="mb-6">
        @if(!$active)
        <button onclick="clockIn()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded w-full sm:w-auto">
            Clock In
        </button>
        @endif

        @if($active)
        <button onclick="clockOut()"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded w-full sm:w-auto">
            Clock Out
        </button>
        @endif
    </div>

    {{-- Responsive Table --}}
    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full border text-xs sm:text-sm">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 border">Date</th>
                    <th class="p-2 border">Clock In</th>
                    <th class="p-2 border">Clock Out</th>
                    <th class="p-2 border">Hours</th>
                    <th class="p-2 border">In Map</th>
                    <th class="p-2 border">Out Map</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $record)
                <tr class="text-center">
                    <td class="p-2 border">
                        {{ \Carbon\Carbon::parse($record->created_at)->format('Y-m-d') }}
                    </td>

                    <td class="p-2 border break-words">
                        {{ $record->clock_in ?? '-' }}
                    </td>

                    <td class="p-2 border break-words">
                        {{ $record->clock_out ?? '-' }}
                    </td>

                    <td class="p-2 border">
                        {{ $record->total_hours ? round($record->total_hours, 2) : '-' }}
                    </td>

                    {{-- Clock In Map --}}
                    <td class="p-2 border">
                        @if($record->clock_in_latitude && $record->clock_in_longitude)
                            <div class="w-32 sm:w-40 mx-auto">
                                <iframe
                                    class="w-full h-24 sm:h-28 rounded"
                                    loading="lazy"
                                    src="https://www.google.com/maps?q={{ $record->clock_in_latitude }},{{ $record->clock_in_longitude }}&hl=en&z=15&output=embed">
                                </iframe>
                            </div>
                        @else
                            -
                        @endif
                    </td>

                    {{-- Clock Out Map --}}
                    <td class="p-2 border">
                        @if($record->clock_out_latitude && $record->clock_out_longitude)
                            <div class="w-32 sm:w-40 mx-auto">
                                <iframe
                                    class="w-full h-24 sm:h-28 rounded"
                                    loading="lazy"
                                    src="https://www.google.com/maps?q={{ $record->clock_out_latitude }},{{ $record->clock_out_longitude }}&hl=en&z=15&output=embed">
                                </iframe>
                            </div>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @php
        $total = $records->sum('total_hours');
    @endphp

    <div class="mt-6 text-base sm:text-lg font-semibold">
        Total Hours This Month: {{ round($total, 2) }}
    </div>

</div>

<script>
function clockIn() {

    if (!navigator.geolocation) {
        alert("Geolocation not supported.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {

            fetch("{{ route('attendance.clockin') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    setTimeout(() => location.reload(), 700);
                }
            })
            .catch(() => alert("Something went wrong"));

        },
        function(){
            alert("Please allow location access.");
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function clockOut() {

    if (!navigator.geolocation) {
        alert("Geolocation not supported.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {

            fetch("{{ route('attendance.clockout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    setTimeout(() => location.reload(), 700);
                }
            })
            .catch(() => alert("Something went wrong"));

        },
        function(){
            alert("Please allow location access.");
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}
</script>

</x-app-layout>
