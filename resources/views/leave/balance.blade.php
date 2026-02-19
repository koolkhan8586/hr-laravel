<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-4">

    <h2 class="text-2xl font-bold mb-6">Leave Balance Management</h2>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif


    {{-- ============================= --}}
    {{-- ASSIGN LEAVE SECTION --}}
    {{-- ============================= --}}
    <div class="bg-white shadow rounded p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Assign / Add Leave Balance</h3>

        <form method="POST" action="{{ route('admin.leave.balance.assign') }}">
            @csrf

            <div class="grid grid-cols-3 gap-4">

                {{-- Employee --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Select Employee
                    </label>
                    <select name="user_id"
                            required
                            class="w-full border rounded px-3 py-2">
                        <option value="">Choose Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Leave Days --}}
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Leave Days
                    </label>
                    <input type="number"
                           name="opening_balance"
                           min="0"
                           step="0.5"
                           required
                           class="w-full border rounded px-3 py-2"
                           placeholder="Enter Leave Days">
                </div>

                {{-- Button --}}
                <div class="flex items-end">
                    <button type="submit"
                            class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700">
                        Assign Leave
                    </button>
                </div>

            </div>
        </form>
    </div>


    {{-- ============================= --}}
    {{-- BALANCE TABLE --}}
    {{-- ============================= --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Opening Balance</th>
                    <th class="p-3 text-left">Used Leaves</th>
                    <th class="p-3 text-left">Remaining Leaves</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>

                @forelse($balances as $balance)
                <tr class="border-t">

                    <td class="p-3">
                        {{ $balance->user->name }}
                    </td>

                    <td class="p-3">
                        {{ $balance->opening_balance }}
                    </td>

                    <td class="p-3 text-red-600">
                        {{ $balance->used_leaves }}
                    </td>

                    <td class="p-3 text-green-600 font-semibold">
                        {{ $balance->remaining_leaves }}
                    </td>

                    <td class="p-3 space-x-2">

                        {{-- EDIT FORM --}}
                        <form method="POST"
                              action="{{ route('admin.leave.balance.update', $balance->id) }}"
                              class="inline">
                            @csrf

                            <input type="number"
                                   name="opening_balance"
                                   value="{{ $balance->opening_balance }}"
                                   step="0.5"
                                   class="border px-2 py-1 rounded w-24">

                            <button type="submit"
                                    class="bg-yellow-500 text-white px-3 py-1 rounded text-xs">
                                Update
                            </button>
                        </form>

                        {{-- DELETE --}}
                        <form method="POST"
                              action="{{ route('admin.leave.balance.delete', $balance->id) }}"
                              class="inline"
                              onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')

                            <button class="bg-red-600 text-white px-3 py-1 rounded text-xs">
                                Delete
                            </button>
                        </form>

                    </td>
                </tr>

                @empty
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-500">
                        No Leave Balances Found
                    </td>
                </tr>
                @endforelse

            </tbody>
        </table>

    </div>

</div>

</x-app-layout>
