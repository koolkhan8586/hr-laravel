<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">
        Leave Allocation Management
    </h2>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Opening Balance</th>
                    <th class="p-3 text-left">Used</th>
                    <th class="p-3 text-left">Remaining</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
            @foreach($employees as $employee)

                @php
                    $balance = \App\Models\LeaveBalance::firstOrCreate(
                        ['user_id' => $employee->id],
                        [
                            'opening_balance' => 0,
                            'used_leaves' => 0,
                            'remaining_leaves' => 0
                        ]
                    );
                @endphp

                <tr class="border-t">

                    {{-- EMPLOYEE NAME --}}
                    <td class="p-3">
                        {{ $employee->name }}
                    </td>

                    <form method="POST" action="{{ route('admin.leave.recalculate') }}">
    @csrf
    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded mb-4">
        🔄 Recalculate All Balances
    </button>
</form>

                    <form method="POST" action="{{ route('admin.leave.reset.year') }}">
    @csrf
    <button class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded mb-4">
        🔁 Reset Yearly Leave
    </button>
</form>
                   
                    {{-- OPENING BALANCE EDIT --}}
                    <td class="p-3">
                        <form method="POST"
                              action="{{ route('admin.leave.allocation.update', $employee->id) }}"
                              class="flex gap-2 items-center">

                            @csrf

                            <input type="number"
                                   name="annual_leave_balance"
                                   value="{{ $balance->opening_balance }}"
                                   min="0"
                                   class="border px-3 py-1 rounded w-24">

                            <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                Update
                            </button>
                        </form>
                    </td>

                    {{-- USED LEAVES --}}
                    <td class="p-3 font-semibold text-red-600">
                        {{ $balance->used_leaves }}
                    </td>

                    {{-- REMAINING --}}
                    <td class="p-3 font-semibold text-green-600">
                        {{ $balance->remaining_leaves }}
                    </td>

                    {{-- INFO --}}
                    <td class="p-3 text-gray-500">
                        Current Balance: 
                        <strong>{{ $balance->remaining_leaves }}</strong>
                    </td>

                </tr>
            @endforeach
            </tbody>

        </table>

    </div>

</div>

</x-app-layout>
