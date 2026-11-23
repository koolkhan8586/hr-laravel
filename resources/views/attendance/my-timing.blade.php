<x-app-layout>

<div class="p-6">

    <div class="flex items-start justify-between flex-wrap gap-4 mb-5">
        <div>
            <h2 class="text-2xl font-bold">My Timing</h2>
            <p class="text-sm text-gray-600 mt-1 max-w-3xl">
                Your whole month, and the time you were short. Nothing is
                deducted while the month is still running - the total is worked
                out once, at the end of it - so anything you see here is
                something you still have time to make up.
            </p>
        </div>

        <form method="GET" class="flex items-end gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Month</label>
                <select name="month" class="border p-2 rounded" onchange="this.form.submit()">
                    @foreach($months as $option)
                        <option value="{{ $option->format('Y-m') }}"
                            {{ $option->format('Y-m') === $month->format('Y-m') ? 'selected' : '' }}>
                            {{ $option->format('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <noscript>
                <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Show</button>
            </noscript>
        </form>
    </div>

    @unless($enabled)
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4 text-sm mb-6">
            Time is not being counted at the moment, so nothing here is charged.
            The figures below are what the month would come to if it were.
        </div>
    @endunless

    @php
        $minutes = $sheet['minutes'];
        $charge  = $sheet['charge'];
        $hoursPerHalf = \App\Support\TimeShortfall::hoursPerHalfDay();
    @endphp

    {{-- Where the month stands --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">
                Time short{{ $sheet['closed'] ? '' : ' so far' }}
            </div>
            <div class="text-3xl font-bold {{ $minutes > 0 ? 'text-orange-600' : 'text-green-700' }}">
                {{ \App\Support\TimeShortfall::label($minutes) }}
            </div>
            @if($minutes > 0)
                <div class="text-xs text-gray-500 mt-1">
                    over {{ count($sheet['days']) }} day(s)
                </div>
            @endif
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">
                {{ $charge ? 'Charged' : ($sheet['closed'] ? 'Comes to' : 'Would come to') }}
            </div>
            <div class="text-3xl font-bold {{ $sheet['days_charged'] > 0 ? 'text-red-600' : 'text-green-700' }}">
                {{ \App\Support\LeaveEntitlement::days($sheet['days_charged']) }}
                <span class="text-base font-normal">day(s)</span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                {{ $hoursPerHalf }} hours is half a day, {{ $hoursPerHalf * 2 }} hours a full one
            </div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-xs text-gray-500">Your annual leave balance</div>
            <div class="text-3xl font-bold">
                {{ \App\Support\LeaveEntitlement::days($sheet['balance']) }}
                <span class="text-base font-normal">day(s)</span>
            </div>
            @if($sheet['without_pay'] > 0)
                <div class="text-xs text-red-600 mt-1">
                    {{ \App\Support\LeaveEntitlement::days($sheet['without_pay']) }} day(s)
                    {{ $charge ? 'were' : 'would be' }} without pay
                </div>
            @endif
        </div>

    </div>

    {{-- What it costs, said in a sentence rather than left to be worked out --}}
    @if($sheet['days_charged'] > 0)
        <div class="bg-white shadow rounded p-5 mb-6">
            <p class="text-sm">
                @if($charge)
                    <strong>{{ $month->format('F Y') }} has been settled.</strong>
                    {{ \App\Support\TimeShortfall::label((int) $charge->minutes) }} short came to
                    {{ \App\Support\LeaveEntitlement::days((float) $charge->days_charged) }} day(s).
                    @if($charge->days_from_balance > 0)
                        {{ \App\Support\LeaveEntitlement::days((float) $charge->days_from_balance) }}
                        day(s) came off your annual leave balance.
                    @endif
                    @if($charge->days_without_pay > 0)
                        <span class="text-red-700">
                            There was no balance left for
                            {{ \App\Support\LeaveEntitlement::days((float) $charge->days_without_pay) }}
                            day(s), so that much is without pay and is deducted from your salary.
                        </span>
                    @endif
                @elseif($sheet['closed'])
                    <strong>{{ $month->format('F Y') }} is over and has not been settled yet.</strong>
                    As it stands it comes to
                    {{ \App\Support\LeaveEntitlement::days($sheet['days_charged']) }} day(s).
                @else
                    <strong>Nothing has been deducted.</strong>
                    If the month ended today it would come to
                    {{ \App\Support\LeaveEntitlement::days($sheet['days_charged']) }} day(s) -
                    @if($sheet['without_pay'] > 0)
                        {{ \App\Support\LeaveEntitlement::days($sheet['from_balance']) }} off your
                        balance and {{ \App\Support\LeaveEntitlement::days($sheet['without_pay']) }}
                        without pay, because your balance does not cover it.
                    @else
                        taken off your annual leave balance.
                    @endif
                    There are still days left in the month.
                @endif
            </p>
        </div>
    @endif

    {{-- The days themselves --}}
    <div class="bg-white shadow rounded p-5 mb-6">

        <h3 class="text-lg font-semibold mb-1">Days that cost time</h3>
        <p class="text-xs text-gray-500 mb-4">
            Only the days that were short are listed. Everything else was a full
            day, a day off, a holiday or approved leave, and costs nothing.
        </p>

        @if(empty($sheet['days']))
            <p class="text-sm text-green-700">
                Nothing short in {{ $month->format('F Y') }}. Every day was worked in full.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="p-2">Date</th>
                            <th class="p-2">Your timing</th>
                            <th class="p-2">Clock in</th>
                            <th class="p-2">Clock out</th>
                            <th class="p-2">What happened</th>
                            <th class="p-2 text-right">Short by</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sheet['days'] as $day)
                            <tr class="border-t">
                                <td class="p-2 whitespace-nowrap">
                                    {{ $day['date']->format('d M') }}
                                    <span class="text-xs text-gray-500">{{ $day['day_name'] }}</span>
                                </td>
                                <td class="p-2 text-xs text-gray-600">{{ $day['timing'] ?? '-' }}</td>
                                <td class="p-2">{{ $day['clock_in'] ?? '-' }}</td>
                                <td class="p-2">
                                    {{ $day['clock_out'] ?? '-' }}
                                    @unless($day['clock_out'])
                                        <span class="text-xs text-red-600">not marked</span>
                                    @endunless
                                </td>
                                <td class="p-2 text-xs">
                                    @if($day['late'] > 0)
                                        <div>Came {{ \App\Support\TimeShortfall::label($day['late']) }} late</div>
                                    @endif
                                    @if($day['early'] > 0)
                                        <div>Left {{ \App\Support\TimeShortfall::label($day['early']) }} early</div>
                                    @endif
                                    @if($day['missing'] > 0)
                                        <div>No clock-out marked - half the shift counted</div>
                                    @endif
                                </td>
                                <td class="p-2 text-right font-semibold whitespace-nowrap">
                                    {{ \App\Support\TimeShortfall::label($day['minutes']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 bg-gray-50 font-semibold">
                            <td class="p-2" colspan="5">Total for {{ $month->format('F Y') }}</td>
                            <td class="p-2 text-right">
                                {{ \App\Support\TimeShortfall::label($minutes) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

    </div>

    {{-- How it is worked out, in plain words --}}
    <div class="bg-blue-50 border border-blue-200 rounded p-4 text-sm">
        <strong>How this is worked out</strong>
        <ul class="mt-2 space-y-1 text-gray-700 list-disc list-inside">
            <li>
                Arriving within your shift's grace costs nothing. Past it, the
                whole lateness counts from your shift's start time.
            </li>
            <li>
                Leaving less than
                {{ \App\Support\TimeShortfall::earlyGraceFor(auth()->user()) }} minutes
                before your shift ends costs nothing. Past that, the whole of it counts.
            </li>
            <li>
                A day with no clock-out marked counts half the shift, because
                there is no record of when you left. Mark it and it does not.
            </li>
            <li>
                Holidays, approved leave and your weekly off are never counted.
                A day absent with no leave is a separate rule.
            </li>
            <li>
                At month end the total is rounded to the nearest half day:
                {{ $hoursPerHalf }} hours is half a day,
                {{ $hoursPerHalf * 2 }} hours a full one. Under
                {{ $hoursPerHalf / 2 }} hours in a whole month comes to nothing.
            </li>
        </ul>
        <p class="text-xs text-gray-600 mt-2">
            Counted from {{ $rulesFrom->format('d M Y') }} onwards. Speak to HR if
            anything here looks wrong - a timing that is set incorrectly on your
            schedule is the usual reason.
        </p>
    </div>

</div>

</x-app-layout>
