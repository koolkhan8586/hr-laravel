<a href="{{ route('payroll.summary') }}"
   class="bg-purple-600 text-white px-4 py-2 rounded mb-4 inline-block">
    Payroll Summary
</a>

<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold mb-6">All Leave Requests</h2>

    {{-- Filter & Export Section --}}
    <div class="flex items-center gap-3 mb-6">

        {{-- Export Transactions --}}
        <a href="{{ route('leave.export.transactions') }}"
           class="bg-green-600 text-white px-4 py-2 rounded">
            Export Transactions
        </a>

        {{-- Export Excel --}}
        <a href="{{ route('leave.export') }}"
           class="bg-green-700 text-white px-4 py-2 rounded">
            Export to Excel
        </a>

        {{-- Filter Form --}}
        <form method="GET" action="{{ route('leave.admin') }}" class="flex gap-2">

            <select name="month" class="border p-2 rounded">
                <option value="">All Months</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                        {{ date('F', mktime(0,0,0,$m,1)) }}
                    </option>
                @endfor
            </select>

            <select name="year" class="border p-2 rounded">
                <option value="">All Years</option>
                @for($y = date('Y'); $y >= 2024; $y--)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endfor
            </select>

            <button class="bg-blue-600 text-white px-4 py-2 rounded">
                Filter
            </button>
        </form>

    </div>

    {{-- Leave Table --}}
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
                <td class="p-2 border">
                    {{ $leave->user->name ?? 'N/A' }}
                </td>

                <td class="p-2 border capitalize">
                    {{ str_replace('_',' ',$leave->type) }}
                </td>

                <td class="p-2 border">
                    {{ $leave->days }}
                </td>

                {{-- Status Column --}}
                <td class="p-2 border">
                    @if($leave->status == 'approved')
                        <span class="text-green-600 font-bold">
                            Approved
                        </span>
                    @elseif($leave->status == 'pending')
                        <span class="text-yellow-600 font-bold">
                            Pending
                        </span>
                    @else
                        <span class="text-red-600 font-bold">
                            Rejected
                        </span>
                    @endif
                </td>

                {{-- Action Column --}}
                <td class="p-2 border">

                    @if($leave->status == 'pending')

                        <form method="POST"
                              action="{{ route('leave.approve', $leave->id) }}"
                              class="inline">
                            @csrf
                            <button class="bg-green-600 text-white px-3 py-1 rounded">
                                Approve
                            </button>
                        </form>

                        <form method="POST"
                              action="{{ route('leave.reject', $leave->id) }}"
                              class="inline">
                            @csrf
                            <button class="bg-red-600 text-white px-3 py-1 rounded">
                                Reject
                            </button>
                        </form>

                    @else
                        <span class="text-gray-500 italic">
                            No Action
                        </span>
                    @endif

                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>
