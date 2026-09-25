<x-app-layout>

<div class="max-w-5xl mx-auto py-6 px-4">

    <div class="flex justify-between items-start mb-5 flex-wrap gap-3 no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">My Schedule</h2>
            <p class="text-sm text-gray-500 mt-1">
                Your duty timings. Ask your manager if anything here looks wrong.
            </p>
        </div>

        <button type="button" onclick="window.print()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
            Print
        </button>
    </div>

    {{-- ================= TODAY ================= --}}
    @if($today)
    <div class="rounded shadow p-5 mb-6
        {{ $today['working'] ? 'bg-emerald-50 border border-emerald-200' : 'bg-gray-100 border border-gray-200' }}">

        <div class="text-xs uppercase tracking-wide text-gray-500">
            Today &mdash; {{ $today['date']->format('l, d M Y') }}
        </div>

        @if($today['working'])
            <div class="text-2xl font-bold text-emerald-800 mt-1">{{ $today['timing'] }}</div>
            <div class="text-sm text-emerald-900 mt-1">
                {{ $today['shift']->name }}
                @if($today['changed'])
                    <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded text-xs ml-1">
                        changed for today
                    </span>
                @endif
            </div>
        @else
            <div class="text-2xl font-bold text-gray-700 mt-1">{{ $today['status'] }}</div>
            @if($today['note'])
                <div class="text-sm text-gray-600 mt-1">{{ $today['note'] }}</div>
            @endif
        @endif
    </div>
    @endif

    {{-- ================= USUAL WEEK ================= --}}
    <div class="bg-white shadow rounded p-5 mb-6">
        <h3 class="font-semibold text-gray-800 mb-1">My Usual Week</h3>
        <p class="text-xs text-gray-500 mb-3">
            The hours you normally work. Individual dates can differ &mdash; see the month below.
        </p>

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-2 text-left w-40">Day</th>
                    <th class="p-2 text-left">Duty Timing</th>
                    <th class="p-2 text-left w-40">Shift</th>
                </tr>
            </thead>
            <tbody>
                @foreach($week as $row)
                <tr class="border-t {{ $row['working'] ? '' : 'bg-gray-50 text-gray-500' }}">
                    <td class="p-2 font-medium">{{ $row['day'] }}</td>
                    <td class="p-2">{{ $row['timing'] ?? 'Off' }}</td>
                    <td class="p-2">{{ $row['shift']->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if(collect($week)->every(fn ($r) => !$r['working']))
        <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded p-3 mt-3">
            No weekly schedule has been set for you yet. Please ask your manager to add one.
        </p>
        @endif
    </div>

    {{-- ================= MONTH ================= --}}
    <div class="bg-white shadow rounded p-5">

        <div class="flex justify-between items-center flex-wrap gap-3 mb-4">
            <h3 class="font-semibold text-gray-800">{{ $monthName }}</h3>

            <form method="GET" class="flex gap-2 items-center no-print">
                <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                       class="border rounded px-3 py-2 text-sm">
                <button class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded text-sm">
                    Show
                </button>
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-2 text-left w-32">Date</th>
                    <th class="p-2 text-left w-16">Day</th>
                    <th class="p-2 text-left">Duty Timing</th>
                    <th class="p-2 text-left w-36">Status</th>
                    <th class="p-2 text-left">Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as $row)
                <tr class="border-t
                    @if($row['is_today']) bg-emerald-50 font-semibold
                    @elseif(!$row['working']) bg-gray-50 text-gray-500
                    @elseif($row['is_past']) text-gray-600 @endif">

                    <td class="p-2 whitespace-nowrap">{{ $row['date']->format('d M Y') }}</td>
                    <td class="p-2">{{ $row['day_name'] }}</td>
                    <td class="p-2">{{ $row['working'] ? $row['timing'] : '-' }}</td>
                    <td class="p-2">
                        {{ $row['status'] }}
                        @if($row['changed'])
                            <span class="bg-amber-100 text-amber-800 px-1 rounded text-[10px]">changed</span>
                        @endif
                    </td>
                    <td class="p-2">{{ $row['note'] ?: '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</div>

<style>
@media print {
    .no-print, nav, aside, header { display: none !important; }
    body { background: #fff !important; }
    table { font-size: 10pt; }
}
</style>

</x-app-layout>
