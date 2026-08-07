<x-app-layout>

<div class="max-w-5xl mx-auto py-8 px-4 space-y-6" x-data="{ audience: '{{ old('audience', 'all') }}' }">

    <h2 class="text-2xl font-bold">Announcements</h2>
    <p class="text-sm text-gray-500">
        Send a message to all employees or selected individuals via WhatsApp and/or Email.
    </p>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow rounded p-5">
        <form method="POST" action="{{ route('admin.announcements.send') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block font-semibold mb-1">Subject (for email / WhatsApp title)</label>
                <input type="text"
                       name="subject"
                       value="{{ old('subject') }}"
                       placeholder="Optional subject"
                       class="w-full border rounded p-2">
            </div>

            <div>
                <label class="block font-semibold mb-1">Message</label>
                <textarea name="message"
                          rows="6"
                          required
                          class="w-full border rounded p-2"
                          placeholder="Write your announcement...">{{ old('message') }}</textarea>
            </div>

            <div>
                <label class="block font-semibold mb-2">Send via</label>
                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="via_whatsapp" value="1"
                               {{ old('via_whatsapp', true) ? 'checked' : '' }}>
                        <span>WhatsApp</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="via_email" value="1"
                               {{ old('via_email') ? 'checked' : '' }}>
                        <span>Email</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block font-semibold mb-2">Audience</label>
                <div class="flex flex-wrap gap-6 mb-3">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="audience" value="all"
                               x-model="audience"
                               {{ old('audience', 'all') === 'all' ? 'checked' : '' }}>
                        <span>All employees</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="audience" value="selected"
                               x-model="audience"
                               {{ old('audience') === 'selected' ? 'checked' : '' }}>
                        <span>Selected employees</span>
                    </label>
                </div>

                <div x-show="audience === 'selected'" x-cloak class="border rounded p-3 max-h-72 overflow-y-auto">
                    <p class="text-xs text-gray-500 mb-2">Select one or more employees:</p>
                    @foreach($employees as $employee)
                        <label class="flex items-start gap-2 py-1 border-b last:border-0">
                            <input type="checkbox"
                                   name="user_ids[]"
                                   value="{{ $employee->id }}"
                                   {{ collect(old('user_ids', []))->contains($employee->id) ? 'checked' : '' }}
                                   class="mt-1">
                            <span class="text-sm">
                                <span class="font-medium">{{ $employee->name }}</span>
                                <span class="text-gray-500 block">
                                    {{ $employee->email ?: 'no email' }}
                                    ·
                                    {{ $employee->mobile ?: 'no mobile' }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded"
                    onclick="return confirm('Send this announcement now?');">
                Send Announcement
            </button>
        </form>
    </div>

    <div class="bg-white shadow rounded p-5">
        <h3 class="text-lg font-semibold mb-4">Recent Announcements</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">When</th>
                        <th class="p-3 text-left">Subject</th>
                        <th class="p-3 text-left">Audience</th>
                        <th class="p-3 text-left">Channels</th>
                        <th class="p-3 text-left">Results</th>
                        <th class="p-3 text-left">Sent By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-t align-top">
                            <td class="p-3 whitespace-nowrap">{{ $log->created_at->format('d M Y h:i A') }}</td>
                            <td class="p-3">
                                <div class="font-medium">{{ $log->subject }}</div>
                                <div class="text-gray-500 text-xs mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</div>
                            </td>
                            <td class="p-3 capitalize">{{ $log->audience }}</td>
                            <td class="p-3">
                                @if($log->via_whatsapp) WhatsApp @endif
                                @if($log->via_whatsapp && $log->via_email) + @endif
                                @if($log->via_email) Email @endif
                            </td>
                            <td class="p-3 text-xs">
                                @if($log->via_whatsapp)
                                    <div>WA: {{ $log->whatsapp_sent }} ok / {{ $log->whatsapp_failed }} fail</div>
                                @endif
                                @if($log->via_email)
                                    <div>Email: {{ $log->email_sent }} ok / {{ $log->email_failed }} fail</div>
                                @endif
                            </td>
                            <td class="p-3">{{ $log->sender->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-gray-500">No announcements sent yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

</x-app-layout>
