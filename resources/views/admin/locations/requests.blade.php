<x-app-layout>

<div class="max-w-6xl mx-auto py-6 px-4">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Location Requests</h2>
            <p class="text-sm text-gray-500 mt-1">
                Employees asking to mark attendance somewhere else.
            </p>
        </div>

        <a href="{{ route('admin.office-locations.index') }}"
           class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded text-sm">
            Office Locations
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- ================= TABS ================= --}}
    <div class="flex gap-2 mb-5">
        @foreach(['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Not approved'] as $key => $label)
        <a href="{{ route('admin.location-requests.index', ['status' => $key]) }}"
           class="px-4 py-2 rounded text-sm
                  {{ $status === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700' }}">
            {{ $label }}
            @if($key === 'pending' && $pendingCount) ({{ $pendingCount }}) @endif
        </a>
        @endforeach
    </div>

    @if($requests->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
        Nothing here.
    </div>

    @else

    <div class="space-y-4">
        @foreach($requests as $item)
        <div class="bg-white shadow rounded p-5">

            <div class="flex justify-between items-start flex-wrap gap-3">
                <div>
                    <div class="font-semibold text-gray-800">
                        {{ $item->user->name ?? 'Unknown' }}
                        <span class="text-xs text-gray-500">
                            {{ $item->user->employee_code ?? '' }}
                        </span>
                    </div>

                    <div class="text-sm text-gray-600 mt-1">
                        Asking for <strong>{{ $item->officeLocation->name ?? '-' }}</strong>
                        @if($item->officeLocation)
                            <span class="text-xs text-gray-500">
                                (within {{ (int) ($item->officeLocation->radius ?: 100) }} m)
                            </span>
                        @endif
                    </div>

                    <div class="text-xs text-gray-500 mt-1">
                        Currently:
                        {{ $item->user?->officeLocations->pluck('name')->implode(', ') ?: 'anywhere (no location set)' }}
                    </div>
                </div>

                <div class="text-xs text-gray-500 whitespace-nowrap">
                    {{ $item->created_at->format('d M Y, h:i A') }}
                </div>
            </div>

            @if($item->reason)
            <p class="text-sm text-gray-700 bg-gray-50 border rounded p-3 mt-3">
                {{ $item->reason }}
            </p>
            @endif

            @if($item->isPending())

            <div class="mt-4 border-t pt-4 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">

                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Note (optional, the employee sees this)</label>
                    <input type="text" form="decide-{{ $item->id }}" name="note"
                           class="w-full border rounded px-3 py-2 text-sm"
                           placeholder="e.g. approved for this term only">
                </div>

                <div class="flex gap-2 flex-wrap">
                    <form method="POST" id="decide-{{ $item->id }}"
                          action="{{ route('admin.location-requests.approve', $item->id) }}">
                        @csrf
                        <input type="hidden" name="mode" value="add">
                        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                            Approve &mdash; add
                        </button>
                    </form>

                    <form method="POST"
                          action="{{ route('admin.location-requests.approve', $item->id) }}">
                        @csrf
                        <input type="hidden" name="mode" value="replace">
                        <button onclick="return confirm('Replace this employee\'s locations with {{ addslashes($item->officeLocation->name ?? '') }}?')"
                                class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded text-sm">
                            Approve &mdash; replace
                        </button>
                    </form>

                    <form method="POST"
                          action="{{ route('admin.location-requests.reject', $item->id) }}">
                        @csrf
                        <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                            Reject
                        </button>
                    </form>
                </div>

            </div>

            <p class="text-[11px] text-gray-500 mt-2">
                <strong>Add</strong> keeps their current locations and allows this one as well.
                <strong>Replace</strong> makes this their only location.
            </p>

            @else

            <div class="mt-3 border-t pt-3 text-sm">
                <span class="@if($item->status === 'approved') text-green-700 @else text-red-700 @endif font-medium">
                    {{ $item->outcome() }}
                </span>
                <span class="text-gray-500">
                    by {{ $item->decidedBy->name ?? 'HR' }}
                    on {{ $item->decided_at?->format('d M Y, h:i A') }}
                </span>
                @if($item->decision_note)
                <div class="text-gray-600 mt-1">{{ $item->decision_note }}</div>
                @endif
            </div>

            @endif

        </div>
        @endforeach
    </div>

    @endif

</div>

</x-app-layout>
