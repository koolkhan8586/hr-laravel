<x-app-layout>
<div class="max-w-7xl mx-auto py-8">

    <h2 class="text-2xl font-bold mb-6">Loan Dashboard</h2>

    @if($loan)

        {{-- SUMMARY TILES --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
                <p class="text-gray-500">Total Loan</p>
                <h3 class="text-xl font-bold">
                    Rs {{ number_format($loan->amount,2) }}
                </h3>
            </div>

            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
                <p class="text-gray-500">Opening Balance</p>
                <h3 class="text-xl font-bold text-purple-600">
                    Rs {{ number_format($loan->opening_balance ?? 0,2) }}
                </h3>
            </div>

            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
                <p class="text-gray-500">Monthly Deduction</p>
                <h3 class="text-xl font-bold text-red-600">
                    Rs {{ number_format($loan->monthly_deduction,2) }}
                </h3>
            </div>

            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
                <p class="text-gray-500">Remaining Balance</p>
                <h3 class="text-xl font-bold text-blue-600">
                    Rs {{ number_format($loan->remaining_balance,2) }}
                </h3>
            </div>

        </div>

        {{-- STATUS --}}
        <div class="bg-white p-6 rounded-xl shadow mb-6">
            <p class="text-gray-500">Status</p>
            <h3 class="text-xl font-bold text-green-600 capitalize">
                {{ $loan->status }}
            </h3>
        </div>

        {{-- LOAN PROGRESS --}}
        @php
            $paid = $loan->amount - $loan->remaining_balance;
            $percentage = $loan->amount > 0 
                ? round(($paid / $loan->amount) * 100,2)
                : 0;
        @endphp

        <div class="bg-white p-6 rounded-xl shadow mb-8">
            <p class="text-gray-500 mb-2">Loan Progress</p>

            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-green-500 h-4 rounded-full transition-all duration-500"
                     style="width: {{ $percentage }}%">
                </div>
            </div>

            <p class="mt-2 text-sm text-gray-600">
                {{ $percentage }}% Paid
            </p>
        </div>

        {{-- LEDGER HISTORY --}}
        <h3 class="text-xl font-bold mb-4">Ledger History</h3>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Type</th>
                        <th class="p-3 text-left">Amount</th>
                        <th class="p-3 text-left">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->ledgers as $entry)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3">
                                {{ $entry->created_at->format('d M Y') }}
                            </td>

                            <td class="p-3 capitalize">
                                {{ $entry->type }}
                            </td>

                            <td class="p-3 font-semibold 
                                @if($entry->type == 'deduction') text-red-600 
                                @elseif($entry->type == 'opening') text-purple-600
                                @else text-blue-600
                                @endif">
                                Rs {{ number_format($entry->amount,2) }}
                            </td>

                            <td class="p-3">
                                {{ $entry->remarks ?? '-' }}
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
        </div>

    @else

        <div class="bg-yellow-100 p-4 rounded-lg">
            No approved loan found.
        </div>

    @endif

</div>
</x-app-layout>
