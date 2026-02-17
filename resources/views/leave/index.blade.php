<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- ================= LEAVE SUMMARY CARDS ================= --}}
    @if(isset($balance))
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-600">
            <p class="text-gray-500 text-sm">Opening Balance</p>
            <h3 class="text-2xl font-bold text-blue-700">
                {{ $balance->opening_balance }} Days
            </h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border-l-4 border-red-600">
            <p class="text-gray-500 text-sm">Used Leaves</p>
            <h3 class="text-2xl font-bold text-red-600">
                {{ $balance->used_leaves }} Days
            </h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border-l-4 border-green-600">
            <p class="text-gray-500 text-sm">Remaining Leaves</p>
            <h3 class="text-2xl font-bold text-green-700">
                {{ $balance->remaining_leaves }} Days
            </h3>
        </div>

    </div>
    @endif


    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">My Leaves</h2>

        <div class="space-x-2">
            <a href="{{ route('leave.history') }}"
               class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded shadow">
                View History
            </a>

            <a href="{{ route('leave.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                Apply Leave
            </a>
        </div>
    </div>


    {{-- ================= SUCCESS MESSAGE ================= --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif


    {{-- ================= LEAVE TABLE ================= --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">From</th>
                    <th class="p-3 text-left">To</th>
                    <th class="p-3 text-left">Duration</th>
                    <th class="p-3 text-left">Days</th>
                    <th class="p-3 text-left">Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse($leaves as $leave)
                <tr class="border-t hover:bg-gray-50 transition">

                    <td class="p-3 capitalize">
                        {{ str_replace('_',' ',$leave->type) }}
                    </td>

                    <td class="p-3">
                        {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                    </td>

                    <td class="p-3">
                        {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                    </td>

                    <td class="p-3 capitalize">
                        {{ str_replace('_',' ',$leave->duration_type ?? 'full_day') }}
                    </td>

                    <td class="p-3 font-semibold">
                        {{ $leave->calculated_days ?? $leave->days }}
                    </td>

                    <td class="p-3">
                        @if($leave->status == 'pending')
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-semibold">
                                Pending
                            </span>
                        @elseif($leave->status == 'approved')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-semibold">
                                Approved
                            </span>
                        @else
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-semibold">
                                Rejected
                            </span>
                        @endif
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center p-6 text-gray-500">
                        No leave records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</div>

</x-app-layout>
