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

    <a href="{{ route('leave.export') }}"
   class="bg-green-600 text-white px-4 py-2 rounded mb-4 inline-block">
    Export to Excel
</a>
  @if($leave->status == 'approved')
    <span class="text-green-600 font-bold">Approved</span>
@elseif($leave->status == 'pending')
    <span class="text-yellow-600 font-bold">Pending</span>
@else
    <span class="text-red-600 font-bold">Rejected</span>
@endif


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
        <form method="GET" class="flex gap-2 mb-4">
    <select name="month" class="border p-2">
        <option value="">All Months</option>
        @for($m=1; $m<=12; $m++)
            <option value="{{ $m }}">{{ date('F', mktime(0,0,0,$m,1)) }}</option>
        @endfor
    </select>

    <select name="year" class="border p-2">
        <option value="">All Years</option>
        @for($y=date('Y'); $y>=2023; $y--)
            <option value="{{ $y }}">{{ $y }}</option>
        @endfor
    </select>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Filter
    </button>
</form>

        <tbody>
            @foreach($leaves as $leave)
            <tr>
                <td class="p-2 border">{{ $leave->user->name }}</td>
                <td class="p-2 border capitalize">{{ str_replace('_',' ',$leave->type) }}</td>
                <td class="p-2 border">{{ $leave->days }}</td>
                <td class="p-2 border">{{ ucfirst($leave->status) }}</td>
                <td class="p-2 border">
                <td class="p-2 border">
    @if($leave->status == 'pending')

        <form method="POST" action="{{ route('leave.approve', $leave->id) }}" class="inline">
            @csrf
            <button class="bg-green-600 text-white px-3 py-1 rounded">
                Approve
            </button>
        </form>

        <form method="POST" action="{{ route('leave.reject', $leave->id) }}" class="inline">
            @csrf
            <button class="bg-red-600 text-white px-3 py-1 rounded">
                Reject
            </button>
        </form>

    @else
        <span class="text-gray-500">No Action</span>
    @endif
</td>

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
