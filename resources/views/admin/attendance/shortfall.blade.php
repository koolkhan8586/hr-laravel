<x-app-layout>

<div class="p-6">

    <div class="flex items-start justify-between flex-wrap gap-4 mb-5">
        <div>
            <h2 class="text-2xl font-bold">Time Short</h2>
            <p class="text-sm text-gray-600 mt-1 max-w-3xl">
                Coming late and leaving early are counted in minutes, not
                punished half a day at a time, and the month is settled once at
                the end of it. While the month is running this is a warning
                everybody can still act on - each employee sees the same figure
                on their own My Timing screen.
            </p>
        </div>

        <form method="GET" class="flex items-end gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Month</label>
                <input type="month" name="month" value="{{ $month->format('Y-m') }}"
                       class="border p-2 rounded">
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                Show
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded p-3 mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 mb-4 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @unless($enabled)
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4 text-sm mb-6">
            <strong>Time is not being counted yet.</strong>
            Half a day is still charged each time somebody leaves early or
            forgets a clock-out. The figures below are what each month would
            come to under the time rule, so you can see what changes before
            switching it on in
            <a href="{{ route('admin.settings.index') }}" class="text-blue-600 underline">Settings</a>.
        </div>
    @endunless

    {{-- Days before the start date are skipped; say so rather than let a
         short list read as a clean month. --}}
    @php $from = \App\Support\TimeShortfall::rulesFrom(); @endphp
    @if($enabled && $from->gt($month->copy()->startOfMonth()))
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4 text-sm mb-6">
            <strong>Only days from {{ $from->format('d M Y') }} are being counted.</strong>
            Anything in {{ $month->format('F Y') }} before that is skipped, so
            the figures below cover part of the month. To count the whole month,
            set <em>Count time from this date onwards</em> to
            {{ $month->copy()->startOfMonth()->format('d M Y') }} in
            <a href="{{ route('admin.settings.index') }}" class="text-blue-600 underline">Settings</a>.
            Earlier days with no clock-out marked will each count 4 hours, so
            check the list before charging the month.
        </div>
    @endif

    {{-- Where the month stands --}}
    @php
        $totalMinutes = $rows->sum('minutes');
        $totalDays    = $rows->sum('days_charged');
        $unpaid       = $rows->sum('without_pay');
        $settled      = $charged === $rows->count() && $rows->count() > 0;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">Employees short</div>
            <div class="text-2xl font-bold">{{ $rows->count() }}</div>
            <div class="text-xs text-gray-500 mt-1">of {{ $staffCount }} tracked</div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">Time short in all</div>
            <div class="text-2xl font-bold">
                {{ \App\Support\TimeShortfall::label((int) $totalMinutes) }}
            </div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">Comes to</div>
            <div class="text-2xl font-bold">
                {{ \App\Support\LeaveEntitlement::days((float) $totalDays) }}
                <span class="text-base font-normal">day(s)</span>
            </div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">Of that, without pay</div>
            <div class="text-2xl font-bold {{ $unpaid > 0 ? 'text-red-600' : '' }}">
                {{ \App\Support\LeaveEntitlement::days((float) $unpaid) }}
                <span class="text-base font-normal">day(s)</span>
            </div>
            <div class="text-xs text-gray-500 mt-1">no leave balance left to cover it</div>
        </div>

    </div>

    {{-- Settling the month --}}
    <div class="bg-white shadow rounded p-5 mb-6">
        @if(! $closed)
            <p class="text-sm text-gray-700">
                <strong>{{ $month->format('F Y') }} is still running.</strong>
                Nothing is charged and nothing can be: the total is only fair
                once the month can be seen whole, and everybody still has days
                left to make it up in.
            </p>
        @elseif($charged > 0 && $charged === $rows->count())
            <p class="text-sm text-green-800">
                <strong>{{ $month->format('F Y') }} has been settled.</strong>
                {{ $charged }} employee(s) were charged, and each was told by
                email and WhatsApp.
            </p>
        @else
            <form method="POST" action="{{ route('admin.attendance.shortfall.charge') }}"
                  onsubmit="return confirm('Charge {{ $month->format('F Y') }}? Leave balances come down, anything over the balance becomes without pay, and every employee affected is told by email and WhatsApp. A month already settled is left alone.');">
                @csrf
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">

                <div class="flex items-center justify-between flex-wrap gap-3">
                    <p class="text-sm text-gray-700">
                        <strong>{{ $month->format('F Y') }} is over.</strong>
                        It is settled automatically on the first of the month;
                        this charges it now instead. Running it twice is safe -
                        a month already settled is left exactly as it was.
                        @if($charged > 0)
                            <span class="block text-xs text-gray-500 mt-1">
                                {{ $charged }} of {{ $rows->count() }} already settled.
                            </span>
                        @endif
                    </p>

                    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm"
                            {{ $enabled ? '' : 'disabled' }}>
                        Charge {{ $month->format('F Y') }}
                    </button>
                </div>
            </form>
        @endif
    </div>

    {{-- Everybody, worst first --}}
    <div class="bg-white shadow rounded p-5 mb-6">

        <h3 class="text-lg font-semibold mb-1">Who was short</h3>
        <p class="text-xs text-gray-500 mb-4">
            Worst first. Anybody not listed worked every rostered day in full.
        </p>

        @if($rows->isEmpty())
            <p class="text-sm text-green-700">
                Nobody was short in {{ $month->format('F Y') }}.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="p-2">Employee</th>
                            <th class="p-2 text-right">Late</th>
                            <th class="p-2 text-right">Left early</th>
                            <th class="p-2 text-right">No clock-out</th>
                            <th class="p-2 text-right">Total short</th>
                            <th class="p-2 text-right">Days</th>
                            <th class="p-2">Paid for by</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr class="border-t">
                                <td class="p-2">
                                    {{ $row['user']->name }}
                                    @if($row['user']->employee_code)
                                        <span class="text-xs text-gray-500">({{ $row['user']->employee_code }})</span>
                                    @endif
                                    <div class="text-xs text-gray-500">
                                        {{ count($row['days']) }} day(s)
                                    </div>
                                </td>

                                <td class="p-2 text-right text-xs">
                                    {{ $row['late'] > 0 ? \App\Support\TimeShortfall::label($row['late']) : '-' }}
                                </td>
                                <td class="p-2 text-right text-xs">
                                    {{ $row['early'] > 0 ? \App\Support\TimeShortfall::label($row['early']) : '-' }}
                                </td>
                                <td class="p-2 text-right text-xs">
                                    {{ $row['missing'] > 0 ? \App\Support\TimeShortfall::label($row['missing']) : '-' }}
                                </td>

                                <td class="p-2 text-right font-semibold whitespace-nowrap">
                                    {{ \App\Support\TimeShortfall::label($row['minutes']) }}
                                </td>

                                <td class="p-2 text-right font-semibold">
                                    {{ \App\Support\LeaveEntitlement::days($row['days_charged']) }}
                                </td>

                                <td class="p-2 text-xs">
                                    @if($row['from_balance'] > 0)
                                        <div>
                                            {{ \App\Support\LeaveEntitlement::days($row['from_balance']) }}
                                            off the leave balance
                                        </div>
                                    @endif
                                    @if($row['without_pay'] > 0)
                                        <div class="text-red-600">
                                            {{ \App\Support\LeaveEntitlement::days($row['without_pay']) }}
                                            without pay
                                        </div>
                                    @endif
                                    @if($row['charge'])
                                        <span style="display:inline-block; margin-top:3px; padding:1px 7px;
                                                     border-radius:9999px; font-size:11px;
                                                     background:#dcfce7; color:#166534;">
                                            Charged {{ $row['charge']->charged_at?->format('d M') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

    </div>

    <div class="bg-blue-50 border border-blue-200 rounded p-4 text-sm">
        <strong>How the figure is reached</strong>
        <p class="mt-1 text-gray-700">
            Arriving past the shift's own grace counts the whole lateness from
            the shift's start time. Leaving more than
            {{ \App\Support\TimeShortfall::earlyGraceMinutes() }} minutes before
            it ends counts the whole of that - unless the employee has their own
            allowance on their staff record, which is used instead. A day with
            no clock-out counts half the shift. Holidays, approved leave and
            weekly offs are never counted, and a day absent with no leave is a
            separate rule, so no day is charged twice. At month end the total is
            rounded to the nearest half day:
            {{ \App\Support\TimeShortfall::hoursPerHalfDay() }} hours is half a
            day, {{ \App\Support\TimeShortfall::hoursPerDay() }} hours a full one.
        </p>
    </div>

</div>

</x-app-layout>
