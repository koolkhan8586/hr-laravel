<x-app-layout>
<div class="max-w-7xl mx-auto py-6">

    <h2 class="text-2xl font-bold mb-6">Loan Management</h2>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Employee</th>
                <th class="p-2 border">Amount</th>
                <th class="p-2 border">Installments</th>
                <th class="p-2 border">Monthly</th>
                <th class="p-2 border">Remaining</th>
                <th class="p-2 border">Status</th>
                <th class="p-2 border">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loans as $loan)
            <tr>
                <td class="p-2 border">{{ $loan->user->name }}</td>
                <td class="p-2 border">{{ $loan->amount }}</td>
                <td class="p-2 border">{{ $loan->installments }}</td>
                <td class="p-2 border">{{ $loan->monthly_deduction }}</td>
                <td class="p-2 border">{{ $loan->remaining_balance }}</td>
                <td class="p-2 border capitalize">{{ $loan->status }}</td>
                <td class="p-2 border">

                    @if($loan->status == 'pending')
                        <form method="POST" action="{{ route('admin.loan.approve', $loan->id) }}" class="inline">
                            @csrf
                            <button class="bg-green-600 text-white px-3 py-1 rounded">
                                Approve
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.loan.reject', $loan->id) }}" class="inline">
                            @csrf
                            <button class="bg-red-600 text-white px-3 py-1 rounded">
                                Reject
                            </button>
                        </form>
                    @endif

                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>
