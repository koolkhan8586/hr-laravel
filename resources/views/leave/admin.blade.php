<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">

        <h2 class="text-2xl font-bold">Leave Management</h2>

        <div class="flex gap-3">

            {{-- ADD LEAVE --}}
            <a href="{{ route('admin.leave.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
                Add Leave
            </a>

            {{-- EXPORT TRANSACTIONS --}}
            <a href="{{ route('admin.leave.transactions.export') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                Export Transactions
            </a>

            {{-- EXPORT EXCEL --}}
            <a href="{{ route('admin.leave.export') }}"
               class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded shadow text-sm">
                Export Leave Excel
            </a>

        </div>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded shadow mb-6 flex gap-3 items-center">

        <form method="GET"
              action="{{ route('admin.leave.index') }}"
              class="flex gap-2 items-center">

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

            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                Filter
            </button>

        </form>

    </div>

    {{-- LEAVE TABLE --}}
    <div class="bg-white rounded shadow overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Days</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>

            @forelse($leaves as $leave)
                <tr class="border-t hover:bg-gray-50">

                    <td class="p-3">
                        {{ $leave->user->name ?? 'N/A' }}
                    </td>

                    <td class="p-3 capitalize">
                        {{ str_replace('_',' ',$leave->type) }}
                    </td>

                    <td class="p-3">
                        {{ $leave->days }}
                    </td>

                    <td class="p-3">

                        @if($leave->status == 'approved')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                                Approved
                            </span>
                        @elseif($leave->status == 'pending')
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">
                                Pending
                            </span>
                        @else
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">
                                Rejected
                            </span>
                        @endif

                    </td>

                    <td class="p-3 space-x-2">

                        {{-- APPROVE --}}
                        @if($leave->status == 'pending')
                            <form method="POST"
                                  action="{{ route('admin.leave.approve', $leave->id) }}"
                                  class="inline">
                                @csrf
                                <button class="bg-green-600 text-white px-3 py-1 rounded text-xs">
                                    Approve
                                </button>
                            </form>

                            <form method="POST"
                                  action="{{ route('admin.leave.reject', $leave->id) }}"
                                  class="inline">
                                @csrf
                                <button class="bg-red-600 text-white px-3 py-1 rounded text-xs">
                                    Reject
                                </button>
                            </form>
                        @endif

                        {{-- REVERT --}}
                        @if($leave->status == 'approved')
                            <form method="POST"
                                  action="{{ route('admin.leave.revert', $leave->id) }}"
                                  class="inline">
                                @csrf
                                <button class="bg-orange-500 text-white px-3 py-1 rounded text-xs">
                                    Revert
                                </button>
                            </form>
                        @endif

                        {{-- EDIT --}}
                        <a href="{{ route('admin.leave.edit', $leave->id) }}"
                           class="bg-yellow-500 text-white px-3 py-1 rounded text-xs">
                            Edit
                        </a>

                        {{-- DELETE --}}
                       
                        <form method="POST" action="{{ route('admin.leave.assign') }}">
    @csrf
    <select name="user_id">
        @foreach($employees as $emp)
            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
        @endforeach
    </select>

    <input type="number" name="opening_balance" placeholder="Enter Leave Days">

    <button type="submit">Assign Leave</button>
</form>

                        
                        <form method="POST"
                              action="{{ route('admin.leave.delete', $leave->id) }}"
                              class="inline"
                              onsubmit="return confirm('Delete this leave?')">
                            @csrf
                            @method('DELETE')
                            <button class="bg-gray-700 text-white px-3 py-1 rounded text-xs">
                                Delete
                            </button>
                        </form>

                    </td>

                </tr>

            @empty
                <tr>
                    <td colspan="5" class="text-center p-6 text-gray-500">
                        No leave requests found.
                    </td>
                </tr>
            @endforelse

            </tbody>

        </table>

    </div>

</div>

</x-app-layout>
