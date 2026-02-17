<x-app-layout>
<div class="max-w-7xl mx-auto py-8">

    <h2 class="text-2xl font-bold mb-6">Loan Dashboard</h2>

    @if($loan)

        {{-- ================= SUMMARY CARDS ================= --}}
        <div class="grid grid-cols-6 md:grid-cols-4 gap-6 mb-8">

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
            <p class="text-gray-500">Status</p>
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
            <p class="mb-2 font-semibold">Loan Progress</p>

            <div class="w-full bg-gray-200 rounded h-4">
                <div class="bg-green-500 h-4 rounded"
                     style="width: {{ $percentage }}%">
                </div>
            </div>

            <p class="mt-2 text-sm text-gray-600">
                {{ number_format($percentage,2) }}% Paid
            </p>
        </div>


        {{-- ================= LEDGER HISTORY ================= --}}
        <h3 class="text-xl font-bold mb-3">Ledger History</h3>

        <table class="w-full border bg-white shadow rounded">
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

                        <td class="p-3 border">
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
                            @endif
                        </td>

                        <td class="p-3 border 
                            @if($ledger->type == 'deduction') text-red-600
                            @else text-green-600
                            @endif font-semibold">
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
