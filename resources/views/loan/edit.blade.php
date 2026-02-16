<x-app-layout>
<div class="max-w-4xl mx-auto py-8">

    <h2 class="text-2xl font-bold mb-6">Edit Loan</h2>

    <div class="bg-white p-6 rounded shadow">

        <form method="POST"
              action="{{ route('admin.loan.update', $loan->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    Employee
                </label>
                <input type="text"
                       value="{{ $loan->user->name }}"
                       disabled
                       class="w-full border rounded px-3 py-2 bg-gray-100">
            </div>

            <div class="mb-3">
    <label>Opening Balance</label>
    <input type="number"
           name="opening_balance"
           value="{{ $loan->opening_balance }}"
           class="form-control">
</div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    Loan Amount
                </label>
                <input type="number"
                       name="amount"
                       step="0.01"
                       value="{{ $loan->amount }}"
                       class="w-full border rounded px-3 py-2"
                       required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    Installments
                </label>
                <input type="number"
                       name="installments"
                       value="{{ $loan->installments }}"
                       class="w-full border rounded px-3 py-2"
                       required>
            </div>

            <div class="mt-6">
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                    Update Loan
                </button>
            </div>

        </form>
    </div>
</div>
</x-app-layout>
