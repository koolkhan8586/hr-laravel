<x-app-layout>
<div class="max-w-xl mx-auto py-8 px-4">
    <h2 class="text-2xl font-bold mb-6">Edit Ledger Entry</h2>

    <div class="bg-white p-6 rounded shadow border">
        <form method="POST" action="{{ route('admin.loan.ledger-entry.update', $entry->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-600">Amount</label>
                <input type="number" name="amount" step="0.01" value="{{ $entry->amount }}" required class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-600">Type</label>
                <select name="type" required class="w-full border rounded px-3 py-2">
                    <option value="deduction" {{ $entry->type == 'deduction' ? 'selected' : '' }}>Salary Deduction (Repayment)</option>
                    <option value="adjustment" {{ $entry->type == 'adjustment' ? 'selected' : '' }}>Manual Repayment / Adjustment</option>
                    <option value="loan" {{ $entry->type == 'loan' ? 'selected' : '' }}>New Loan Amount</option>
                    <option value="opening" {{ $entry->type == 'opening' ? 'selected' : '' }}>Opening Balance</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-600">Date</label>
                <input type="date" name="created_at" value="{{ $entry->created_at->format('Y-m-d') }}" required class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1 text-gray-600">Remarks</label>
                <input type="text" name="remarks" value="{{ $entry->remarks }}" required class="w-full border rounded px-3 py-2">
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded text-sm font-semibold">
                    Update Entry
                </button>
                <a href="{{ route('admin.loan.ledger', $entry->loan_id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm font-semibold text-center border">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>
</x-app-layout>
