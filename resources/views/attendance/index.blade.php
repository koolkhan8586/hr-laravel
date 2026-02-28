<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold mb-4">Attendance</h2>

    {{-- Monthly Filter --}}
    <form method="GET" class="mb-6 flex gap-2">
        <input type="month" name="month" value="{{ $month }}" class="border p-2">
        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Filter
        </button>
    </form>

   

    <div class="mb-6">
        @if(!$active)
        <button onclick="clockIn()" class="bg-green-600 text-white px-4 py-2 rounded mr-2">
            Clock In
        </button>
        @endif

        @if($active)
        <button onclick="clockOut()" class="bg-red-600 text-white px-4 py-2 rounded">
            Clock Out
        </button>
        @endif
    </div>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Date</th>
                <th class="p-2 border">Clock In</th>
                <th class="p-2 border">Clock Out</th>
                <th class="p-2 border">Total Hours</th>
                <th class="p-2 border">Clock In Map</th>
                <th class="p-2 border">Clock Out Map</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                <td class="p-2 border">
                    {{ \Carbon\Carbon::parse($record->created_at)->format('Y-m-d') }}
                </td>
                <td class="p-2 border">{{ $record->clock_in }}</td>
                <td class="p-2 border">{{ $record->clock_out }}</td>
                <td class="p-2 border">
                    {{ $record->total_hours ? round($record->total_hours, 2) : '-' }}
                </td>
                <td class="p-2 border text-center">
    @if($record->clock_in_latitude && $record->clock_in_longitude)
        <a target="_blank"
           class="text-green-600 font-semibold underline"
           href="https://www.google.com/maps/search/?api=1&query={{ $record->clock_in_latitude }},{{ $record->clock_in_longitude }}">
            View In
        </a>
    @else
        -
    @endif
</td>

<td class="p-2 border text-center">
    @if($record->clock_out_latitude && $record->clock_out_longitude)
        <a target="_blank"
           class="text-red-600 font-semibold underline"
           href="https://www.google.com/maps/search/?api=1&query={{ $record->clock_out_latitude }},{{ $record->clock_out_longitude }}">
            View Out
        </a>
    @else
        -
    @endif
</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $total = $records->sum('total_hours');
    @endphp

    <div class="mt-6 text-lg font-bold">
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
                if (data.message) {
                    alert(data.message);
                }

                if (data.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 800); // small delay so alert is visible
                }
            })
            .catch(error => {
                console.error(error);
                alert("Something went wrong");
            });

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

    navigator.geolocation.getCurrentPosition(function(position) {

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
                location.reload();
            }
        })
        .catch(() => alert("Something went wrong"));

    }, function(){
        alert("Please allow location access.");
    });
}
</script>

</x-app-layout>
