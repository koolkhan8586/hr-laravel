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

            <div class="bg-white p-6 rounded shadow">
                <p class="text-gray-500">Status</p>
                <h3 class="text-xl font-bold text-green-600 capitalize">
                    {{ $loan->status }}
                </h3>
            </div>

        </div>

        {{-- Payment History --}}
        <h3 class="text-xl font-bold mb-3">Payment History</h3>

        <table class="w-full border bg-white shadow">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border">Month</th>
                    <th class="p-2 border">Amount Paid</th>
                    <th class="p-2 border">Remaining</th>
                </tr>
            </thead>
            <tbody>
                @foreach($loan->payments as $payment)
                <tr>
                    <td class="p-2 border">
                        {{ $payment->created_at->format('F Y') }}
                    </td>
                    <td class="p-2 border text-red-600">
                        Rs {{ number_format($payment->amount_paid,2) }}
                    </td>
                    <td class="p-2 border text-blue-600">
                        Rs {{ number_format($payment->remaining_balance,2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @else

        <div class="bg-yellow-100 p-4 rounded">
            No approved loan found.
        </div>

    @endif

</div>
</x-app-layout>
