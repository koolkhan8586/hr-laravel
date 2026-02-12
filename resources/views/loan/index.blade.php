<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-6">

    <div class="flex justify-between mb-6">
        <h2 class="text-2xl font-bold">Loan Management</h2>
        <a href="{{ route('loan.create') }}"
           class="bg-green-600 text-white px-4 py-2 rounded">
            Assign Loan
        </a>
    </div>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Employee</th>
                <th class="p-2 border">Amount</th>
                <th class="p-2 border">Monthly Deduction</th>
                <th class="p-2 border">Remaining</th>
                <th class="p-2 border">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loans as $loan)
            <tr>
                <td class="p-2 border">{{ $loan->user->name }}</td>
                <td class="p-2 border">{{ $loan->amount }}</td>
                <td class="p-2 border">{{ $loan->monthly_deduction }}</td>
                <td class="p-2 border">{{ $loan->remaining_amount }}</td>
                <td class="p-2 border">
                    @if($loan->status == 'active')
                        <span class="text-yellow-600 font-bold">Active</span>
                    @else
                        <span class="text-green-600 font-bold">Completed</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>
