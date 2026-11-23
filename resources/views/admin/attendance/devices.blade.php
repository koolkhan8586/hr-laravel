<x-app-layout>

<div class="p-6">

    <div class="flex items-start justify-between flex-wrap gap-4 mb-4">
        <div>
            <h2 class="text-2xl font-bold">Attendance Devices</h2>
            <p class="text-sm text-gray-600 mt-1 max-w-3xl">
                What marked the attendance, not just where it was marked from.
                Checking the position cannot catch somebody who connects to an
                office computer from home and marks attendance on it - the
                machine is standing in the office, so its position and its
                address are both perfect. What gives it away is the machine
                itself: a desktop browser rather than a phone, and usually one
                that several employees have marked from.
            </p>
        </div>

        <form method="GET" class="flex items-end gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Month</label>
                <input type="month" name="month" value="{{ $month }}"
                       class="border p-2 rounded">
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                Show
            </button>
        </form>
    </div>

    {{-- Which rules are on, and where to change them --}}
    <div class="bg-white shadow rounded p-4 mb-6 text-sm">
        <div class="flex flex-wrap gap-6">
            <div>
                <span class="text-gray-500">Only from a phone:</span>
                <strong class="{{ $requireMobile ? 'text-green-700' : 'text-gray-700' }}">
                    {{ $requireMobile ? 'On' : 'Off' }}
                </strong>
            </div>
            <div>
                <span class="text-gray-500">One device, one employee a day:</span>
                <strong class="{{ $onePerDevice ? 'text-green-700' : 'text-gray-700' }}">
                    {{ $onePerDevice ? 'On' : 'Off' }}
                </strong>
            </div>
            <div>
                <a href="{{ route('admin.settings.index') }}" class="text-blue-600 underline">
                    Change these in Settings
                </a>
            </div>
        </div>

        @if(! $requireMobile && ! $onePerDevice)
            <p class="text-xs text-gray-500 mt-2">
                Both rules are off, so nothing is being refused - every mark is
                only being recorded. Look at the list below first, decide who
                genuinely needs a shared computer and tick the exemption on
                their staff record, then switch the rules on.
            </p>
        @endif
    </div>

    {{-- The summary line that says whether there is anything to look at --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">Marks with a device recorded</div>
            <div class="text-2xl font-bold">{{ number_format($recorded) }}</div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">Devices used by more than one employee</div>
            <div class="text-2xl font-bold {{ $sharedCount ? 'text-red-600' : '' }}">
                {{ number_format($sharedCount) }}
            </div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">Marks from before devices were recorded</div>
            <div class="text-2xl font-bold text-gray-400">{{ number_format($unrecorded) }}</div>
            <div class="text-xs text-gray-500 mt-1">
                Nothing can be said about these - they were marked before this
                was kept.
            </div>
        </div>

    </div>

    {{-- One device, several names: the thing worth looking at --}}
    <div class="bg-white shadow rounded p-5 mb-6">

        <h3 class="text-lg font-semibold mb-1">Devices</h3>
        <p class="text-xs text-gray-500 mb-4">
            One row per device, the ones shared between employees first. A
            phone with one name on it is ordinary. A computer with several
            names on it is one machine marking other people's attendance.
        </p>

        @if($devices->isEmpty())
            <p class="text-sm text-gray-500">
                No marks in {{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}
                carry a device yet. Devices are recorded from the moment this
                was switched on, so give it a day and come back.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="p-2">Device</th>
                            <th class="p-2">Kind</th>
                            <th class="p-2">Marked for</th>
                            <th class="p-2 text-right">Marks</th>
                            <th class="p-2">Address</th>
                            <th class="p-2">Last used</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($devices as $device)
                            <tr class="border-t {{ $device['shared'] ? 'bg-red-50' : '' }}">

                                <td class="p-2 align-top">
                                    <div class="font-mono text-xs text-gray-600">
                                        {{ \Illuminate\Support\Str::limit($device['id'], 12, '…') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ \App\Support\AttendanceDevice::agentLabel($device['agent']) }}
                                    </div>
                                </td>

                                <td class="p-2 align-top">
                                    <span class="{{ $device['kind'] === 'computer' ? 'text-red-700 font-semibold' : '' }}">
                                        {{ \App\Support\AttendanceDevice::kindLabel($device['kind']) }}
                                    </span>
                                </td>

                                <td class="p-2 align-top">
                                    @foreach($device['people'] as $person)
                                        <div>
                                            {{ $person->name }}
                                            @if($person->employee_code)
                                                <span class="text-xs text-gray-500">({{ $person->employee_code }})</span>
                                            @endif
                                            @if($person->attendance_device_exempt)
                                                <span class="text-xs text-green-700">exempt</span>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if($device['shared'])
                                        <div class="text-xs text-red-700 mt-1">
                                            {{ $device['people']->count() }} different employees have
                                            marked from this one device.
                                        </div>

                                        @foreach($device['days'] as $day => $names)
                                            <div class="text-xs text-red-700">
                                                {{ \Carbon\Carbon::parse($day)->format('d M') }}:
                                                {{ $names->implode(', ') }} - the same day.
                                            </div>
                                        @endforeach
                                    @endif
                                </td>

                                <td class="p-2 align-top text-right">{{ $device['marks'] }}</td>

                                <td class="p-2 align-top text-xs text-gray-600">
                                    @foreach($device['ips'] as $ip)
                                        <div>{{ $ip }}</div>
                                    @endforeach
                                </td>

                                <td class="p-2 align-top text-xs">
                                    {{ \Carbon\Carbon::parse($device['last'])->format('d M Y') }}
                                    @if($device['first'] !== $device['last'])
                                        <div class="text-gray-500">
                                            from {{ \Carbon\Carbon::parse($device['first'])->format('d M') }}
                                        </div>
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

    {{-- Marked from a computer --}}
    <div class="bg-white shadow rounded p-5 mb-6">

        <h3 class="text-lg font-semibold mb-1">Marked from a computer</h3>
        <p class="text-xs text-gray-500 mb-4">
            A remote desktop session is a computer. So is the machine on
            somebody's own desk, which is why this list on its own proves
            nothing - read it next to the devices above, and against who
            actually needs a shared machine.
        </p>

        @if($fromComputer->isEmpty())
            <p class="text-sm text-gray-500">
                Every recorded mark this month came from a phone.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="p-2">Employee</th>
                            <th class="p-2 text-right">Marks from a computer</th>
                            <th class="p-2">Last one</th>
                            <th class="p-2">Browser</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fromComputer as $row)
                            <tr class="border-t">
                                <td class="p-2">
                                    {{ $row['user']->name ?? 'Unknown' }}
                                    @if($row['user']?->employee_code)
                                        <span class="text-xs text-gray-500">({{ $row['user']->employee_code }})</span>
                                    @endif
                                    @if($row['user']?->attendance_device_exempt)
                                        <span class="text-xs text-green-700">exempt</span>
                                    @endif
                                </td>
                                <td class="p-2 text-right">{{ $row['marks'] }}</td>
                                <td class="p-2 text-xs">
                                    {{ \Carbon\Carbon::parse($row['last'])->format('d M Y') }}
                                </td>
                                <td class="p-2 text-xs text-gray-600">
                                    {{ \App\Support\AttendanceDevice::agentLabel($row['agent']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

    {{-- What this can and cannot do --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded p-4 text-sm">
        <strong>What this catches, and what it does not.</strong>
        <p class="mt-1 text-gray-700">
            A browser can be made to say what somebody likes, and clearing its
            storage gives it a new token, so none of this is proof on its own.
            What it does is turn a quiet daily habit into something that has to
            be worked at, and leave a record of every attempt either way. The
            two things that actually close the door are the phone-only rule and
            one device per employee a day - and, past a point, a mark that
            cannot be made from a desk at all: a daily photo at clock-in, or a
            fingerprint reader at the gate.
        </p>
    </div>

</div>

</x-app-layout>
