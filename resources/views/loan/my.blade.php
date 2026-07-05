<x-app-layout>
<style>
/* CSS fallback for servers without compiled Tailwind */
.my-summary-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 640px) {
    .my-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (min-width: 1024px) {
    .my-summary-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.my-table-wrapper {
    width: 100%;
    overflow-x: auto;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}
</style>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6">

    <h2 class="text-2xl font-bold mb-6 text-gray-800">Loan Dashboard</h2>

    @if($loan)

        {{-- ================= SUMMARY CARDS ================= --}}
        <div class="my-summary-grid mb-8">

            <div class="bg-white p-6 rounded shadow border-l-4 border-gray-400">
                <p class="text-gray-500 text-sm">Total Loan</p>
                <h3 class="text-xl font-bold text-gray-800">
                    Rs {{ number_format($loan->amount,2) }}
                </h3>
            </div>

            <div class="bg-white p-6 rounded shadow border-l-4 border-purple-500">
                <p class="text-gray-500 text-sm">Opening Balance</p>
                <h3 class="text-xl font-bold text-purple-600">
                    Rs {{ number_format($loan->opening_balance ?? 0,2) }}
                </h3>
            </div>

            <div class="bg-white p-6 rounded shadow border-l-4 border-red-500">
                <p class="text-gray-500 text-sm">Monthly Deduction</p>
                <h3 class="text-xl font-bold text-red-600">
                    Rs {{ number_format($loan->monthly_deduction,2) }}
                </h3>
            </div>

            <div class="bg-white p-6 rounded shadow border-l-4 border-red-600">
                <p class="text-gray-500 text-sm">Remaining Balance</p>
                <h3 class="text-xl font-bold 
                    @if($loan->remaining_balance > 0) text-red-600 
                    @else text-green-600 
                    @endif">
                    Rs {{ number_format($loan->remaining_balance,2) }}
                </h3>
            </div>

        </div>


        {{-- ================= STATUS ================= --}}
        <div class="bg-white p-6 rounded shadow mb-6">
            <p class="text-gray-500 text-sm">Status</p>
            <h3 class="text-xl font-bold 
                @if($loan->status == 'approved') text-green-600
                @elseif($loan->status == 'rejected') text-red-600
                @else text-yellow-600
                @endif capitalize">
                {{ $loan->status }}
            </h3>
        </div>


        {{-- ================= LOAN PROGRESS ================= --}}
        @php
            $total = ($loan->opening_balance ?? 0) + $loan->amount;
            $paid = $total - $loan->remaining_balance;
            $percentage = $total > 0 ? ($paid / $total) * 100 : 0;
        @endphp

        <div class="bg-white p-6 rounded shadow mb-8">
            <p class="mb-2 font-semibold text-gray-700">Loan Progress</p>

            <div class="w-full bg-gray-200 rounded h-4 overflow-hidden">
                <div class="bg-green-500 h-4 rounded"
                     style="width: {{ $percentage }}%">
                </div>
            </div>

            <p class="mt-2 text-sm text-gray-600">
                {{ number_format($percentage,2) }}% Paid
            </p>
        </div>


        {{-- ================= LEDGER HISTORY ================= --}}
        <h3 class="text-xl font-bold mb-4 text-gray-800">Ledger History</h3>

        <div class="my-table-wrapper">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Type</th>
                        <th class="p-3 text-left">Amount</th>
                        <th class="p-3 text-left">Remarks</th>
                    </tr>
                </thead>
                <tbody>

                    @forelse($loan->ledgers as $ledger)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 whitespace-nowrap">
                                {{ $ledger->created_at->format('d M Y') }}
                            </td>

                            <td class="p-3">
                                @if($ledger->type == 'opening')
                                    <span class="text-purple-600 font-semibold">
                                        Opening Balance
                                    </span>
                                @elseif($ledger->type == 'deduction')
                                    <span class="text-red-600 font-semibold">
                                        Salary Deduction
                                    </span>
                                @elseif($ledger->type == 'adjustment')
                                    <span class="text-blue-600 font-semibold">
                                        Adjustment
                                    </span>
                                @elseif($ledger->type == 'loan')
                                    <span class="text-green-600 font-semibold">
                                        New Loan
                                    </span>
                                @endif
                            </td>

                            <td class="p-3 font-semibold whitespace-nowrap 
                                @if(in_array($ledger->type, ['deduction', 'adjustment'])) text-red-600
                                @else text-green-600
                                @endif">
                                Rs {{ number_format($ledger->amount,2) }}
                            </td>

                            <td class="p-3 text-gray-600">
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
        </div>

    @else

        <div class="bg-yellow-100 p-4 rounded text-yellow-800">
            No approved loan found.
        </div>

    @endif

</div>
</x-app-layout>
