<x-app-layout>
<div class="max-w-5xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-6">
Loan Ledger — {{ $loan->user->name }}
</h2>

<div class="bg-white shadow rounded p-4 mb-6">
    <p><strong>Total Loan:</strong> {{ number_format($loan->amount,2) }}</p>
    <p><strong>Opening Balance:</strong> {{ number_format($loan->opening_balance,2) }}</p>
    <p><strong>Remaining:</strong> {{ number_format($loan->remaining_balance,2) }}</p>
</div>

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
@foreach($loan->ledgers as $entry)
<tr class="border-t">
    <td class="p-2">{{ $entry->created_at->format('Y-m-d') }}</td>
    <td class="p-2 capitalize">{{ $entry->type }}</td>
    <td class="p-2">{{ number_format($entry->amount,2) }}</td>
    <td class="p-2">{{ $entry->remarks }}</td>
</tr>
@endforeach
</tbody>
</table>

</div>
</x-app-layout>
