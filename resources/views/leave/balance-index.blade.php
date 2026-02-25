<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">
        Leave Allocation Management
    </h2>

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
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
            @foreach($employees as $employee)
                <tr class="border-t">

                    <td class="p-3">
                        {{ $employee->name }}
                    </td>

                    <td class="p-3">
                        <form method="POST"
                              action="{{ route('admin.leave.allocation.update', $employee->id) }}"
                              class="flex gap-2">

                            @csrf

                            <input type="number"
                                   name="annual_leave_balance"
                                   value="{{ $employee->annual_leave_balance ?? 0 }}"
                                   min="0"
                                   class="border px-3 py-1 rounded w-24">

                            <button type="submit"
                                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                Update
                            </button>
                        </form>
                    </td>

                    <td class="p-3">
                        Current Balance: 
                        <strong>{{ $employee->annual_leave_balance ?? 0 }}</strong>
                    </td>

                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

</div>

</x-app-layout>
