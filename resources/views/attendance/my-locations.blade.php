<x-app-layout>

<div class="max-w-4xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold text-gray-800">My Work Locations</h2>
    <p class="text-sm text-gray-500 mt-1 mb-5">
        Where you can mark attendance from.
    </p>

    @if(session('success'))
    <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        <ul class="list-disc ml-5 text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- ================= WHERE I CAN CLOCK IN ================= --}}
    <div class="bg-white shadow rounded p-5 mb-6">

        @if($anywhere)

            <div class="bg-emerald-50 border border-emerald-200 rounded p-4">
                <div class="font-semibold text-emerald-800">You can mark attendance from anywhere</div>
                <p class="text-sm text-emerald-900 mt-1">
                    No location check applies to you at the moment.
                </p>
            </div>

        @elseif($assigned->isEmpty())

            <div class="bg-gray-50 border border-gray-200 rounded p-4">
                <div class="font-semibold text-gray-800">No location set</div>
                <p class="text-sm text-gray-600 mt-1">
                    You can currently mark attendance from anywhere. Ask HR if this looks wrong.
                </p>
            </div>

        @else

            <h3 class="font-semibold text-gray-800 mb-1">You can clock in at</h3>
            <p class="text-xs text-gray-500 mb-3">
                You need to be inside the listed distance of one of these when you clock in.
            </p>

            <div class="space-y-2">
                @foreach($assigned as $office)
                <div class="border rounded p-3 flex justify-between items-start gap-3 flex-wrap">
                    <div>
                        <div class="font-medium">{{ $office->name }}</div>
                        <div class="text-xs text-gray-500">
                            Within {{ (int) ($office->radius ?: 100) }} m
                            @if($office->address) &middot; {{ $office->address }} @endif
                        </div>
                    </div>

                    @if($office->latitude && $office->longitude)
                    <a href="https://www.google.com/maps?q={{ $office->latitude }},{{ $office->longitude }}"
                       target="_blank" rel="noopener"
                       class="text-sm text-blue-600 hover:underline whitespace-nowrap">
                        View on map
                    </a>
                    @endif
                </div>
                @endforeach
            </div>

        @endif

    </div>

    {{-- ================= ASK FOR A CHANGE ================= --}}
    <div class="bg-white shadow rounded p-5 mb-6">

        <h3 class="font-semibold text-gray-800 mb-1">Need to work somewhere else?</h3>
        <p class="text-xs text-gray-500 mb-3">
            Ask for another location and HR will decide. You cannot change this yourself.
        </p>

        @if($unassigned->isEmpty())

            <p class="text-sm text-gray-600">
                There are no other locations to ask for at the moment.
            </p>

        @else

        <form method="POST" action="{{ route('location-requests.store') }}" class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1">Location</label>
                <select name="office_location_id" class="w-full border rounded px-3 py-2 text-sm" required>
                    <option value="">Choose a location</option>
                    @foreach($unassigned as $office)
                    <option value="{{ $office->id }}" {{ old('office_location_id') == $office->id ? 'selected' : '' }}>
                        {{ $office->name }}@if($office->address) &mdash; {{ $office->address }} @endif
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Why do you need it?</label>
                <textarea name="reason" rows="3" required
                          class="w-full border rounded px-3 py-2 text-sm"
                          placeholder="e.g. I am teaching at Main Campus on Mondays this term">{{ old('reason') }}</textarea>
            </div>

            <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded text-sm">
                Send Request
            </button>
        </form>

        @endif

    </div>

    {{-- ================= MY REQUESTS ================= --}}
    @if($requests->isNotEmpty())
    <div class="bg-white shadow rounded p-5">

        <h3 class="font-semibold text-gray-800 mb-3">My requests</h3>

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-2 text-left">Sent</th>
                    <th class="p-2 text-left">Location</th>
                    <th class="p-2 text-left">Outcome</th>
                    <th class="p-2 text-left">Note</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $item)
                <tr class="border-t">
                    <td class="p-2 whitespace-nowrap">{{ $item->created_at->format('d M Y') }}</td>
                    <td class="p-2">{{ $item->officeLocation->name ?? '-' }}</td>
                    <td class="p-2">
                        <span class="@if($item->status === 'approved') text-green-700
                                     @elseif($item->status === 'rejected') text-red-700
                                     @else text-amber-700 @endif">
                            {{ $item->outcome() }}
                        </span>
                    </td>
                    <td class="p-2 text-gray-600">{{ $item->decision_note ?: '-' }}</td>
                    <td class="p-2 text-right">
                        @if($item->isPending())
                        <form method="POST" action="{{ route('location-requests.cancel', $item->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Withdraw</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    @endif

</div>

</x-app-layout>
