<x-app-layout>

<div class="max-w-5xl mx-auto py-8 px-4 space-y-6">

    <h2 class="text-2xl font-bold">Settings</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded">{{ $errors->first() }}</div>
    @endif

    {{-- Connection status --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="bg-white shadow rounded p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">WhatsApp (WAHA)</h3>
                @if($wahaStatus['connected'])
                    <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded">Connected</span>
                @else
                    <span class="bg-red-100 text-red-700 text-xs font-semibold px-3 py-1 rounded">Not Connected</span>
                @endif
            </div>
            <p class="text-sm text-gray-600 mb-1"><span class="font-semibold">Status:</span> {{ $wahaStatus['status'] }}</p>
            <p class="text-sm text-gray-600 mb-1"><span class="font-semibold">Session:</span> {{ config('services.waha.session', 'default') }}</p>
            @if($wahaStatus['me'])
                <p class="text-sm text-gray-600 mb-1"><span class="font-semibold">Linked account:</span> {{ $wahaStatus['me'] }}</p>
            @endif
            <p class="text-sm text-gray-500 mt-2">{{ $wahaStatus['detail'] }}</p>
            <a href="{{ route('admin.settings.index') }}"
               class="inline-block mt-4 bg-gray-700 hover:bg-gray-800 text-white text-sm px-4 py-2 rounded">
                Refresh Status
            </a>
        </div>

        <div class="bg-white shadow rounded p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Email Server</h3>
                @if($mailStatus['connected'])
                    <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded">Connected</span>
                @else
                    <span class="bg-red-100 text-red-700 text-xs font-semibold px-3 py-1 rounded">Not Connected</span>
                @endif
            </div>
            <p class="text-sm text-gray-600 mb-1"><span class="font-semibold">Status:</span> {{ $mailStatus['status'] }}</p>
            <p class="text-sm text-gray-600 mb-1"><span class="font-semibold">Mailer:</span> {{ $mailStatus['config']['mailer'] }}</p>
            <p class="text-sm text-gray-600 mb-1"><span class="font-semibold">Host:</span> {{ $mailStatus['config']['host'] }}:{{ $mailStatus['config']['port'] }}</p>
            <p class="text-sm text-gray-600 mb-1"><span class="font-semibold">From:</span> {{ $mailStatus['config']['from'] }}</p>
            <p class="text-sm text-gray-500 mt-2">{{ $mailStatus['detail'] }}</p>

            <form method="POST" action="{{ route('admin.settings.test.email') }}" class="mt-4 flex flex-wrap gap-2">
                @csrf
                <input type="email"
                       name="test_email"
                       value="{{ old('test_email', auth()->user()->email) }}"
                       placeholder="Test email address"
                       class="border rounded px-3 py-2 text-sm flex-1 min-w-[200px]"
                       required>
                <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded">
                    Send Test Email
                </button>
            </form>
        </div>

    </div>

    {{-- Daily report timing --}}
    <div class="bg-white shadow rounded p-5 mb-6">

        <h3 class="text-lg font-semibold mb-1">Daily Report Time</h3>
        <p class="text-sm text-gray-500 mb-4">
            When the Absent / Late / Leave report goes out each day (Asia/Karachi).
        </p>

        <form method="POST" action="{{ route('admin.settings.daily-schedule.save') }}"
              class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1">Send at</label>
                <input type="time" name="daily_report_time" value="{{ $reportTime }}" required
                       class="border p-2 rounded w-full">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Keep trying until</label>
                <input type="time" name="daily_report_retry_until" value="{{ $reportUntil }}" required
                       class="border p-2 rounded w-full">
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="daily_report_retry" value="1"
                           {{ $reportRetry ? 'checked' : '' }}>
                    Retry if WhatsApp is down
                </label>
                <p class="text-[11px] text-gray-500 mt-1">
                    Tries again every minute until the report gets through.
                </p>
            </div>

            <div>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">
                    Save Time
                </button>
            </div>
        </form>

        {{-- Today's outcome, and a way to send it by hand --}}
        <div class="mt-5 pt-5 border-t flex flex-wrap items-center justify-between gap-3">

            <div class="text-sm">
                @if($todayRun && $todayRun->wasSent())
                    <span class="text-green-700 font-semibold">
                        ✅ Today's report went out at {{ $todayRun->sent_at?->timezone('Asia/Karachi')->format('h:i A') }}
                    </span>
                    <span class="text-gray-500">
                        to {{ $todayRun->sent_count }} number(s){{ $todayRun->manual ? ' (sent by hand)' : '' }}.
                    </span>
                @elseif($todayRun && $todayRun->status === 'failed')
                    <span class="text-red-700 font-semibold">
                        ⚠️ Today's report has not gone out
                    </span>
                    <span class="text-gray-600">
                        &mdash; {{ $todayRun->attempts }} attempt(s), last at
                        {{ $todayRun->last_attempt_at?->timezone('Asia/Karachi')->format('h:i A') }}.
                    </span>
                    @if($todayRun->last_error)
                    <div class="text-xs text-red-600 mt-1">{{ $todayRun->last_error }}</div>
                    @endif
                @else
                    <span class="text-gray-600">
                        Today's report is due at {{ \App\Support\DailyReportSchedule::label($reportTime) }}.
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.settings.daily-report.send') }}">
                @csrf
                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                    Send Report Now
                </button>
            </form>

        </div>

        <p class="text-xs text-gray-500 mt-3">
            <strong>Send Report Now</strong> works at any time of day and can be used
            as often as you like &mdash; useful when WhatsApp was disconnected at the
            usual time and the report needs to go out once it is back.
        </p>

        @if($recentRuns->isNotEmpty())
        <details class="mt-4">
            <summary class="cursor-pointer text-sm text-gray-600">Last 7 days</summary>
            <table class="w-full text-xs mt-2">
                <tr class="text-left text-gray-500">
                    <th class="py-1 pr-4">Date</th>
                    <th class="py-1 pr-4">Result</th>
                    <th class="py-1 pr-4">Sent</th>
                    <th class="py-1">Attempts</th>
                </tr>
                @foreach($recentRuns as $past)
                <tr class="border-t">
                    <td class="py-1 pr-4">{{ $past->report_date->format('d M Y') }}</td>
                    <td class="py-1 pr-4">
                        @if($past->wasSent())
                            <span class="text-green-700">
                                Sent {{ $past->sent_at?->timezone('Asia/Karachi')->format('h:i A') }}
                            </span>
                        @else
                            <span class="text-red-700">Not sent</span>
                        @endif
                    </td>
                    <td class="py-1 pr-4">{{ $past->sent_count }}</td>
                    <td class="py-1">{{ $past->attempts }}</td>
                </tr>
                @endforeach
            </table>
        </details>
        @endif

    </div>

    {{-- Daily report numbers --}}
    <div class="bg-white shadow rounded p-5">
        <h3 class="text-lg font-semibold mb-1">Daily Report WhatsApp Numbers</h3>
        <p class="text-sm text-gray-500 mb-4">
            These numbers receive the Absent / Late / Leave report every day at
            {{ \App\Support\DailyReportSchedule::label($reportTime) }} (Asia/Karachi).
            You can add multiple numbers.
        </p>

        <form method="POST" action="{{ route('admin.settings.daily-numbers.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
            @csrf
            <input type="text" name="name" placeholder="Name (optional)" class="border p-2 rounded">
            <input type="text" name="mobile" placeholder="WhatsApp number (e.g. 03001234567)" class="border p-2 rounded" required>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Add Number
            </button>
        </form>

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Mobile</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailyNumbers as $item)
                    <tr class="border-t">
                        <td class="p-3">{{ $item->name ?? '-' }}</td>
                        <td class="p-3">{{ $item->mobile }}</td>
                        <td class="p-3">
                            @if($item->is_active)
                                <span class="text-green-700 font-semibold">Active</span>
                            @else
                                <span class="text-gray-500">Disabled</span>
                            @endif
                        </td>
                        <td class="p-3 space-x-2">
                            <form method="POST" action="{{ route('admin.settings.daily-numbers.toggle', $item->id) }}" class="inline">
                                @csrf
                                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs">
                                    {{ $item->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <form method="POST"
                                  action="{{ route('admin.settings.daily-numbers.destroy', $item->id) }}"
                                  class="inline"
                                  onsubmit="return confirm('Remove this number?');">
                                @csrf
                                @method('DELETE')
                                <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-gray-500">No numbers added yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(count($envDailyNumbers))
            <p class="text-xs text-gray-500 mt-4">
                Also included from .env (<code>WAHA_DAILY_REPORT_MOBILES</code>):
                {{ implode(', ', $envDailyNumbers) }}
            </p>
        @endif
    </div>

</div>

</x-app-layout>
