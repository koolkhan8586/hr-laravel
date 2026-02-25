<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold">Leave Management</h2>

        <a href="{{ route('leave.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
            + Add Leave
        </a>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- FILTER SECTION --}}
    <div class="bg-white shadow rounded p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <select name="employee" class="border rounded px-3 py-2">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}"
                        {{ request('employee') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="border rounded px-3 py-2">
                <option value="">All Status</option>
                <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option>
            </select>

            <input type="month"
                   name="month"
                   value="{{ request('month') }}"
                   class="border rounded px-3 py-2">

            <button class="bg-blue-600 text-white px-4 py-2 rounded">
                Filter
            </button>

        </form>
    </div>

    {{-- LEAVE TABLE --}}
    <div class="bg-white shadow rounded overflow-x-auto">

        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-gray-100 text-gray-700">
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
                <tr class="border-t hover:bg-gray-50">

                    {{-- Employee --}}
                    <td class="p-3">
                        {{ $leave->user->name }}
                    </td>

                    {{-- Type --}}
                    <td class="p-3 capitalize">
                        {{ str_replace('_',' ', $leave->type) }}
                    </td>

                    {{-- Dates --}}
                    <td class="p-3">
                        {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                        -
                        {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                    </td>

                    {{-- Days --}}
                    <td class="p-3 font-semibold">
                        {{ $leave->calculated_days }}
                    </td>

                    {{-- Applied On --}}
                    <td class="p-3">
                        {{ $leave->created_at->format('d M Y') }}
                    </td>

                    {{-- Status --}}
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

                    {{-- ACTIONS --}}
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">

                            {{-- APPROVE / REJECT --}}
                            @if($leave->status == 'pending')

                                <form method="POST"
                                      action="{{ route('admin.leave.approve', $leave->id) }}">
                                    @csrf
                                    <button class="bg-green-600 text-white px-3 py-1 rounded text-xs">
                                        Approve
                                    </button>
                                </form>

                                <form method="POST"
                                      action="{{ route('admin.leave.reject', $leave->id) }}">
                                    @csrf
                                    <button class="bg-red-600 text-white px-3 py-1 rounded text-xs">
                                        Reject
                                    </button>
                                </form>

                            @endif

                            {{-- REVERT --}}
                            @if($leave->status == 'approved')
                                <form method="POST"
                                      action="{{ route('admin.leave.revert', $leave->id) }}">
                                    @csrf
                                    <button class="bg-orange-500 text-white px-3 py-1 rounded text-xs">
                                        Revert
                                    </button>
                                </form>
                            @endif

                            {{-- EDIT --}}
                            <a href="{{ route('admin.leave.edit', $leave->id) }}"
                               class="bg-blue-600 text-white px-3 py-1 rounded text-xs">
                                Edit
                            </a>

                            {{-- DELETE --}}
                            <form method="POST"
                                  action="{{ route('admin.leave.delete', $leave->id) }}"
                                  onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button class="bg-gray-700 text-white px-3 py-1 rounded text-xs">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </td>

                </tr>

                @empty
                <tr>
                    <td colspan="7" class="p-6 text-center text-gray-500">
                        No Leave Records Found
                    </td>
                </tr>
                @endforelse

            </tbody>
        </table>

    </div>

</div>

</x-app-layout>
