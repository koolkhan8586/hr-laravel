<x-app-layout>
<div class="max-w-3xl mx-auto py-8">

    <h2 class="text-2xl font-bold mb-6">Apply for Loan</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('loan.store.request') }}" class="bg-white p-6 rounded shadow space-y-4">
        @csrf

        <div>
            <label class="block font-medium">Loan Amount</label>
            <input type="number" name="amount"
                   class="w-full border rounded px-3 py-2"
                   required>
        </div>

        <div>
            <label class="block font-medium">Installments (Months)</label>
            <input type="number" name="installments"
                   class="w-full border rounded px-3 py-2"
                   required>
        </div>

        <button class="bg-green-600 text-white px-6 py-2 rounded">
            Submit Request
        </button>
    </form>

</div>
</x-app-layout>
