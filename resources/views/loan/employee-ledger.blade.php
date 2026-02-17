<x-app-layout>
<div class="max-w-6xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-6">
    My Loan Ledger
</h2>

<div class="bg-white shadow rounded p-6 mb-6 grid grid-cols-4 gap-4">

    <div>
        <p class="text-gray-500">Loan Amount</p>
        <p class="font-bold text-lg">Rs {{ number_format($loan->amount,2) }}</p>
    </div>

    <div>
        <p class="text-gray-500">Opening Balance</p>
        <p class="font-bold text-lg">Rs {{ number_format($loan->opening_balance,2) }}</p>
    </div>

    <div>
        <p class="text-gray-500">Remaining Balance</p>
        <p class="font-bold text-lg text-red-600">
            Rs {{ number_format($loan->remaining_balance,2) }}
        </p>
    </div>

    <div>
        <p class="text-gray-500">Status</p>
        <p class="font-bold">{{ ucfirst($loan->status) }}</p>
    </div>

</div>

<h3 class="text-xl font-semibold mb-4">Ledger History</h3>

<table class="w-full bg-white shadow rounded">
<thead class="bg-gray-200">
<tr>
    <th class="p-2">Date</th>
    <th class="p-2">Type</th>
    <th class="p-2">Amount</th>
    <th class="p-2">Remarks</th>
</tr>
</thead>
<tbody>
@forelse($loan->ledgers as $entry)
<tr class="border-t">
    <td class="p-2">{{ $entry->created_at->format('Y-m-d') }}</td>
    <td class="p-2">{{ ucfirst($entry->type) }}</td>
    <td class="p-2">Rs {{ number_format($entry->amount,2) }}</td>
    <td class="p-2">{{ $entry->remarks }}</td>
</tr>
@empty
<tr>
    <td colspan="4" class="p-4 text-center text-gray-500">
        No ledger entries found.
    </td>
</tr>
@endforelse
</tbody>
</table>

</div>
</x-app-layout>
