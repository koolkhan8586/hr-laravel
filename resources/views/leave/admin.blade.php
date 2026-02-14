<x-app-layout>
<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">
            Leave Management
        </h2>

        <a href="{{ route('admin.payroll.summary') }}"
           class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded shadow text-sm">
            Payroll Summary
        </a>
    </div>

    {{-- ================= FILTER & EXPORT SECTION ================= --}}
    <div class="bg-white shadow rounded p-5 mb-8">

        <div class="flex flex-wrap items-center justify-between gap-4">

            {{-- LEFT SIDE --}}
            <div class="flex flex-wrap items-center gap-3">

                <a href="{{ route('admin.leave.transactions.export') }}"
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm shadow">
                    Export Transactions
                </a>

                <a href="{{ route('admin.leave.export') }}"
                   class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded text-sm shadow">
                    Export Leave Excel
                </a>

            </div>

            {{-- RIGHT SIDE (FILTER) --}}
            <form method="GET"
                  action="{{ route('admin.leave.index') }}"
                  class="flex flex-wrap gap-2 items-center">

                <select name="month"
                        class="border rounded px-3 py-2 text-sm">
                    <option value="">All Months</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}"
                            {{ request('month') == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$m,1)) }}
                        </option>
                    @endfor
                </select>

                <select name="year"
                        class="border rounded px-3 py-2 text-sm">
                    <option value="">All Years</option>
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}"
                            {{ request('year') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>

                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm shadow">
                    Filter
                </button>

            </form>

        </div>
    </div>

    {{-- ================= LEAVE TABLE ================= --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
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

                    {{-- STATUS BADGE --}}
                    <td class="p-3">
                        @if($leave->status == 'approved')
                            <span class="px-3 py-1 text-xs font-semibold rounded bg-green-100 text-green-700">
                                Approved
                            </span>
                        @elseif($leave->status == 'pending')
                            <span class="px-3 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-700">
                                Pending
                            </span>
                        @else
                            <span class="px-3 py-1 text-xs font-semibold rounded bg-red-100 text-red-700">
                                Rejected
                            </span>
                        @endif
                    </td>

                    {{-- ACTIONS --}}
                    <td class="p-3">

                        @if($leave->status == 'pending')

                            <div class="flex gap-2">

                                <form method="POST"
                                      action="{{ route('admin.leave.approve', $leave->id) }}">
                                    @csrf
                                    <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs shadow">
                                        Approve
                                    </button>
                                </form>

                                <form method="POST"
                                      action="{{ route('admin.leave.reject', $leave->id) }}">
                                    @csrf
                                    <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs shadow">
                                        Reject
                                    </button>
                                </form>

                            </div>

                        @else
                            <span class="text-gray-400 italic text-xs">
                                No Action
                            </span>
                        @endif

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center p-6 text-gray-500">
                        No leave records found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

    </div>

</div>
</x-app-layout>
