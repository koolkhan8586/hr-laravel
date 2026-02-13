<x-app-layout>
<div class="max-w-6xl mx-auto py-6">

    <h2 class="text-2xl font-bold mb-6">My Loan Details</h2>

    @if($loan)

        <div class="grid grid-cols-3 gap-6 mb-8">

            <div class="bg-blue-100 p-6 rounded shadow">
                <h3 class="font-bold">Total Loan</h3>
                <p class="text-xl">Rs {{ number_format($loan->amount, 2) }}</p>
            </div>

            <div class="bg-yellow-100 p-6 rounded shadow">
                <h3 class="font-bold">Monthly Deduction</h3>
                <p class="text-xl">Rs {{ number_format($loan->monthly_deduction, 2) }}</p>
            </div>

            <div class="bg-green-100 p-6 rounded shadow">
                <h3 class="font-bold">Remaining Balance</h3>
                <p class="text-xl">Rs {{ number_format($loan->remaining_amount, 2) }}</p>
            </div>

        </div>

    @else

        <div class="bg-gray-100 p-6 rounded shadow">
            No loan assigned yet.
        </div>

    @endif

</div>
</x-app-layout>
