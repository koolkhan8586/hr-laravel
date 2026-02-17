<x-app-layout>
<div class="max-w-6xl mx-auto py-8">

<h2 class="text-2xl font-bold mb-6">Loan Dashboard</h2>

@if($loan)

<div class="grid grid-cols-4 gap-6 mb-8">

    <div class="bg-white p-6 rounded shadow">
        <p class="text-gray-500">Total Loan</p>
        <h3 class="text-xl font-bold">
            Rs {{ number_format($loan->amount,2) }}
        </h3>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <p class="text-gray-500">Opening Balance</p>
        <h3 class="text-xl font-bold text-purple-600">
            Rs {{ number_format($loan->opening_balance ?? 0,2) }}
        </h3>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <p class="text-gray-500">Monthly Deduction</p>
        <h3 class="text-xl font-bold text-red-600">
            Rs {{ number_format($loan->monthly_deduction,2) }}
        </h3>
    </div>

    <div class="bg-white p-6 rounded shadow">
        <p class="text-gray-500">Remaining Balance</p>
        <h3 class="text-xl font-bold text-blue-600">
            Rs {{ number_format($loan->remaining_balance,2) }}
        </h3>
    </div>

</div>

<div class="bg-white p-6 rounded shadow mb-6">
    <p class="text-gray-500">Status</p>
    <h3 class="text-xl font-bold text-green-600 capitalize">
        {{ $loan->status }}
    </h3>
</div>

{{-- Ledger Section --}}
<h3 class="text-xl font-bold mb-3">Ledger History</h3>

<table class="w-full border bg-white shadow">
    <thead class="bg-gray-100">
        <tr>
            <th class="p-3 border text-left">Date</th>
            <th class="p-3 border text-left">Type</th>
            <th class="p-3 border text-left">Amount</th>
            <th class="p-3 border text-left">Remarks</th>
        </tr>
    </thead>
    <tbody>
        @forelse($loan->ledgers as $ledger)
        <tr>
            <td class="p-3 border">
                {{ $ledger->created_at->format('d M Y') }}
            </td>
            <td class="p-3 border capitalize">
                {{ $ledger->type }}
            </td>
            <td class="p-3 border">
                Rs {{ number_format($ledger->amount,2) }}
            </td>
            <td class="p-3 border">
                {{ $ledger->remarks ?? '-' }}
            </td>
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

@else

<div class="bg-yellow-100 p-4 rounded">
    No approved loan found.
</div>

@endif

</div>
</x-app-layout>
