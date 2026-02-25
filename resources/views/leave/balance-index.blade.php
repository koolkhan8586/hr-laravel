<x-app-layout>

<div class="max-w-6xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Leave Balance Management</h2>

    <div class="bg-white shadow rounded p-6">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Annual Leave Balance</th>
                </tr>
            </thead>

            <tbody>
                @foreach($employees as $emp)
                    <tr class="border-t">
                        <td class="p-3">{{ $emp->name }}</td>
                        <td class="p-3">{{ $emp->annual_leave_balance }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</div>

</x-app-layout>
