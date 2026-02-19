<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-4">

    <h2 class="text-2xl font-bold mb-6">Leave Management</h2>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- ============================= --}}
    {{-- FILTER SECTION --}}
    {{-- ============================= --}}
    <div class="bg-white shadow rounded p-4 mb-6">
        <form method="GET" class="grid grid-cols-4 gap-4">

            {{-- Employee Filter --}}
            <select name="employee" class="border rounded px-3 py-2">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}"
                        {{ request('employee') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }}
                    </option>
                @endforeach
            </select>

            {{-- Status Filter --}}
            <select name="status" class="border rounded px-3 py-2">
                <option value="">All Status</option>
                <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option>
            </select>

            {{-- Month Filter --}}
            <input type="month"
                   name="month"
                   value="{{ request('month') }}"
                   class="border rounded px-3 py-2">

            <button class="bg-blue-600 text-white px-4 py-2 rounded">
                Filter
            </button>

        </form>
    </div>

    {{-- ============================= --}}
    {{-- LEAVE TABLE --}}
    {{-- ============================= --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Leave Dates</th>
                    <th class="p-3 text-left">Days</th>
                    <th class="p-3 text-left">Applied On</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($leaves as $leave)
                <tr class="border-t">

                    {{-- Employee --}}
                    <td class="p-3">
                        {{ $leave->user->name }}
                    </td>

                    {{-- Type --}}
                    <td class="p-3 capitalize">
                        {{ str_replace('_',' ', $leave->type) }}
                    </td>

                    {{-- Leave Dates --}}
                    <td class="p-3">
                        {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                        -
                        {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                    </td>

                    {{-- Days --}}
                    <td class="p-3">
                        {{ $leave->calculated_days }}
                    </td>

                    {{-- Applied On --}}
                    <td class="p-3">
                        {{ $leave->created_at->format('d M Y') }}
                    </td>

                    {{-- Status --}}
                    <td class="p-3">
                        @if($leave->status == 'pending')
                            <span class="text-yellow-600 font-semibold">Pending</span>
                        @elseif($leave->status == 'approved')
                            <span class="text-green-600 font-semibold">Approved</span>
                        @else
                            <span class="text-red-600 font-semibold">Rejected</span>
                        @endif
                    </td>

                    {{-- ACTIONS --}}
                    <td class="p-3 space-x-2">

                        @if($leave->status == 'pending')

                            {{-- APPROVE --}}
                            <form method="POST"
                                  action="{{ route('admin.leave.approve', $leave->id) }}"
                                  class="inline">
                                @csrf
                                <button class="bg-green-600 text-white px-3 py-1 rounded text-xs">
                                    Approve
                                </button>
                            </form>

                            {{-- REJECT --}}
                            <form method="POST"
                                  action="{{ route('admin.leave.reject', $leave->id) }}"
                                  class="inline">
                                @csrf
                                <button class="bg-red-600 text-white px-3 py-1 rounded text-xs">
                                    Reject
                                </button>
                            </form>

                        @elseif($leave->status == 'approved')

                            {{-- REVERT --}}
                            <form method="POST"
                                  action="{{ route('admin.leave.revert', $leave->id) }}"
                                  class="inline">
                                @csrf
                                <button class="bg-orange-500 text-white px-3 py-1 rounded text-xs">
                                    Revert
                                </button>
                            </form>

                        @endif

                        {{-- DELETE --}}
                        <form method="POST"
                              action="{{ route('admin.leave.delete', $leave->id) }}"
                              class="inline"
                              onsubmit="return confirm('Are you sure?')">
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
                    <td colspan="7" class="p-4 text-center text-gray-500">
                        No Leave Records Found
                    </td>
                </tr>
                @endforelse

            </tbody>
        </table>

    </div>

</div>

</x-app-layout>
