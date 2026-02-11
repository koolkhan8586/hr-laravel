<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold mb-6">All Leave Requests</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif
     
    <a href="{{ route('leave.transactions.export') }}"
   class="bg-green-600 text-white px-4 py-2 rounded">
    Export Transactions
</a>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Employee</th>
                <th class="p-2 border">Type</th>
                <th class="p-2 border">Days</th>
                <th class="p-2 border">Status</th>
                <th class="p-2 border">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leaves as $leave)
            <tr>
                <td class="p-2 border">{{ $leave->user->name }}</td>
                <td class="p-2 border capitalize">{{ str_replace('_',' ',$leave->type) }}</td>
                <td class="p-2 border">{{ $leave->days }}</td>
                <td class="p-2 border">{{ ucfirst($leave->status) }}</td>
                <td class="p-2 border">
                    @if($leave->status == 'pending')
                        <form method="POST" action="{{ route('leave.approve', $leave->id) }}" class="inline">
                            @csrf
                            <button class="bg-green-600 text-white px-3 py-1 rounded">Approve</button>
                        </form>

                        <form method="POST" action="{{ route('leave.reject', $leave->id) }}" class="inline ml-2">
                            @csrf
                            <button class="bg-red-600 text-white px-3 py-1 rounded">Reject</button>
                        </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>
