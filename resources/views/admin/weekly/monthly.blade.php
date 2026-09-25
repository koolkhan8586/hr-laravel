<x-app-layout>

<div class="max-w-full mx-auto py-6 px-4">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Monthly Schedule</h2>
            <p class="text-sm text-gray-500 mt-1">
                Set particular dates, for when a day alternates &mdash; one Saturday off,
                the next one working. Anything left on <strong>Usual</strong> follows the
                weekly schedule.
            </p>
        </div>

        <a href="{{ route('schedule.editor') }}"
           class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm">
            Weekly Grid
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- ================= FILTERS ================= --}}
    <form method="GET" class="flex gap-3 items-end flex-wrap bg-white p-4 rounded shadow mb-5">

        <div>
            <label class="block text-xs text-gray-500 mb-1">Month</label>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                   class="border px-3 py-2 rounded text-sm">
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Show</label>
            <select name="weekday" class="border px-3 py-2 rounded text-sm">
                @foreach($weekdays as $day)
                <option value="{{ $day }}" {{ $weekday === $day ? 'selected' : '' }}>
                    {{ $day }}s only
                </option>
                @endforeach
                <option value="all" {{ $weekday === null ? 'selected' : '' }}>Every date</option>
            </select>
        </div>

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
            Show
        </button>

    </form>

    @if(empty($dates))

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
        There are no {{ $weekday }}s in {{ $monthName }}.
    </div>

    @elseif($users->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
        No employees found.
    </div>

    @else

    <form method="POST" action="{{ route('schedule.monthly.update') }}">
        @csrf
        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
        <input type="hidden" name="weekday" value="{{ $weekday ?? 'all' }}">

        <div class="bg-white shadow rounded overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3 text-left sticky left-0 bg-gray-100">Employee</th>
                        @foreach($dates as $date)
                        <th class="p-3 text-center whitespace-nowrap">
                            {{ $date->format('d M') }}
                            <div class="text-[11px] font-normal text-gray-500">
                                {{ $date->format('D') }}
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                @foreach($users as $user)
                <tr class="border-t">

                    <td class="p-3 font-medium whitespace-nowrap sticky left-0 bg-white">
                        {{ $user->name }}
                        <div class="text-[11px] text-gray-500">
                            {{ $user->employee_code ?: '' }}
                            @if($weekday)
                                &middot; usually {{ $usual[$user->id]->name ?? 'off' }}
                            @endif
                        </div>
                    </td>

                    @foreach($dates as $date)
                    @php
                        $key = $date->toDateString();
                        $override = $overrides[$user->id][$key] ?? null;

                        // "" usual, "off" taken off the day, otherwise a shift id
                        $selected = $override
                            ? ($override->shift_id ? (string) $override->shift_id : 'off')
                            : '';
                    @endphp
                    <td class="p-2">
                        <select name="duty[{{ $user->id }}][{{ $key }}]"
                                class="w-full border rounded p-1 text-xs
                                       {{ $selected !== '' ? 'bg-amber-50 border-amber-300' : '' }}">

                            <option value="">Usual</option>
                            <option value="off" {{ $selected === 'off' ? 'selected' : '' }}>OFF</option>

                            @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}"
                                {{ $selected === (string) $shift->id ? 'selected' : '' }}>
                                {{ $shift->name }}
                            </option>
                            @endforeach

                        </select>
                    </td>
                    @endforeach

                </tr>
                @endforeach
                </tbody>

            </table>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                Save Changes
            </button>
            <span class="text-xs text-gray-500">
                Shaded boxes are dates set individually. Employees see these on
                <strong>My Schedule</strong>.
            </span>
        </div>

    </form>

    @endif

</div>

</x-app-layout>
