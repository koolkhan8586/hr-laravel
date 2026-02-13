<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold mb-6">My Leave History</h2>

    {{-- Leave Requests --}}
    <div class="mb-10">
        <h3 class="text-xl font-bold mb-3">Leave Requests</h3>

        <table class="w-full border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 border">Type</th>
                    <th class="p-2 border">Start</th>
                    <th class="p-2 border">End</th>
                    <th class="p-2 border">Days</th>
                    <th class="p-2 border">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $leave)
                <tr>
                    <td class="p-2 border capitalize">{{ $leave->type }}</td>
                    <td class="p-2 border">{{ $leave->start_date }}</td>
                    <td class="p-2 border">{{ $leave->end_date }}</td>
                    <td class="p-2 border">{{ $leave->calculated_days }}</td>
                    <td class="p-2 border">
                        @if($leave->status == 'approved')
                            <span class="text-green-600 font-bold">Approved</span>
                        @elseif($leave->status == 'pending')
                            <span class="text-yellow-600 font-bold">Pending</span>
                        @else
                            <span class="text-red-600 font-bold">Rejected</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-500">
                        No leave records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Leave Transactions --}}
    <div>
        <h3 class="text-xl font-bold mb-3">Balance Transactions</h3>

        <table class="w-full border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 border">Leave ID</th>
                    <th class="p-2 border">Days</th>
                    <th class="p-2 border">Before</th>
                    <th class="p-2 border">After</th>
                    <th class="p-2 border">Action</th>
                    <th class="p-2 border">Processed By</th>
                    <th class="p-2 border">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr>
                    <td class="p-2 border">{{ $t->leave_id }}</td>
                    <td class="p-2 border">{{ $t->days }}</td>
                    <td class="p-2 border">{{ $t->balance_before }}</td>
                    <td class="p-2 border">{{ $t->balance_after }}</td>
                    <td class="p-2 border capitalize">{{ $t->action }}</td>
                    <td class="p-2 border">
                        {{ $t->processed_by }}
                    </td>
                    <td class="p-2 border">
                        {{ $t->created_at->format('Y-m-d') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-4 text-center text-gray-500">
                        No transaction history found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-app-layout>
