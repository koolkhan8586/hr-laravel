<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold">
            Leave Allocation Management
        </h2>

        <div class="flex flex-wrap gap-2">

            {{-- Recalculate All --}}
            <form method="POST" action="{{ route('admin.leave.recalculate.all') }}">
                @csrf
                <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm shadow">
                    🔄 Recalculate All Balances
                </button>
            </form>

            {{-- Reset Year --}}
            <form method="POST" action="{{ route('admin.leave.reset.year') }}"
                  onsubmit="return confirm('Reset all balances for new year?')">
                @csrf
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded text-sm shadow">
                    ♻ Reset Yearly Balance
                </button>
            </form>

        </div>
    </div>


    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif


    {{-- ================= BULK ALLOCATION ================= --}}
    <div class="bg-white shadow rounded p-4 mb-6">

        <h3 class="font-semibold mb-3 text-gray-700">
            Bulk Allocation
        </h3>

        <form method="POST" action="{{ route('admin.leave.bulk.allocate') }}"
              class="flex flex-col md:flex-row gap-4 items-center">

            @csrf

            <input type="number"
                   name="bulk_balance"
                   placeholder="Enter leave days"
                   min="0"
                   required
                   class="border rounded px-3 py-2 w-full md:w-60">

            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                Apply to All Employees
            </button>

        </form>

    </div>


    {{-- ================= TABLE ================= --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">

                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3 text-left">Employee</th>
                        <th class="p-3 text-left">Opening Balance</th>
                        <th class="p-3 text-center">Used</th>
                        <th class="p-3 text-center">Remaining</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($employees as $employee)

                    @php
                        $balance = \App\Models\LeaveBalance::firstOrCreate(
                            ['user_id' => $employee->id],
                            [
                                'opening_balance' => $employee->annual_leave_balance ?? 0,
                                'used_leaves' => 0,
                                'remaining_leaves' => $employee->annual_leave_balance ?? 0
                            ]
                        );
                    @endphp

                    <tr class="border-t hover:bg-gray-50 transition">

                        {{-- Employee --}}
                        <td class="p-3 font-medium">
                            {{ $employee->name }}
                        </td>

                        {{-- Opening Balance --}}
                        <td class="p-3">
                            <form method="POST"
                                  action="{{ route('admin.leave.allocation.update', $employee->id) }}"
                                  class="flex flex-wrap gap-2 items-center">

                                @csrf

                                <input type="number"
                                       name="annual_leave_balance"
                                       value="{{ $balance->opening_balance }}"
                                       min="0"
                                       class="border px-3 py-1 rounded w-24">

                                <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                                    Update
                                </button>
                            </form>
                        </td>

                        {{-- Used --}}
                        <td class="p-3 text-center text-red-600 font-semibold">
                            {{ $balance->used_leaves }}
                        </td>

                        {{-- Remaining --}}
                        <td class="p-3 text-center text-green-600 font-semibold">
                            {{ $balance->remaining_leaves }}
                        </td>

                        {{-- Current --}}
                        <td class="p-3 text-center text-gray-500">
                            Current Balance:
                            <strong>{{ $balance->remaining_leaves }}</strong>
                        </td>

                    </tr>

                @endforeach
                </tbody>

            </table>
        </div>

    </div>

</div>

</x-app-layout>
