<x-app-layout>
<div class="mb-4 p-4 bg-blue-100 rounded">
    <strong>Annual Leave Balance:</strong>
    {{ auth()->user()->annual_leave_balance }} days
</div>

<div class="max-w-7xl mx-auto py-6 px-4">

    <div class="flex justify-between mb-4">
    <h2 class="text-2xl font-bold">My Leaves</h2>

    <div class="space-x-2">
        <a href="{{ route('leave.history') }}"
           class="bg-gray-700 text-white px-4 py-2 rounded">
            View History
        </a>

        <a href="{{ route('leave.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded">
            Apply Leave
        </a>
    </div>
</div>
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

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
            @foreach($leaves as $leave)
            <tr>
                <td class="p-2 border capitalize">{{ str_replace('_',' ',$leave->type) }}</td>
                <td class="p-2 border">{{ $leave->start_date }}</td>
                <td class="p-2 border">{{ $leave->end_date }}</td>
                <td class="p-2 border">{{ $leave->days }}</td>
                <td class="p-2 border">
                    @if($leave->status == 'pending')
                        <span class="text-yellow-600 font-bold">Pending</span>
                    @elseif($leave->status == 'approved')
                        <span class="text-green-600 font-bold">Approved</span>
                    @else
                        <span class="text-red-600 font-bold">Rejected</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>
