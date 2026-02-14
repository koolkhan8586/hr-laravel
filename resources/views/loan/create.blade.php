<x-app-layout>
<div class="max-w-4xl mx-auto py-6 px-6">

<h2 class="text-2xl font-bold mb-6">Assign Loan</h2>

<form action="{{ route('loan.store') }}" method="POST" class="space-y-4">
@csrf

<div class="mt-4">
    <label class="block text-sm font-medium text-gray-700">
        Opening Remaining Balance (Optional)
    </label>
    <input type="number"
           name="remaining_balance"
           step="0.01"
           placeholder="Leave empty for full amount"
           class="w-full border rounded px-3 py-2 mt-1">
</div>

<select name="user_id" class="w-full border p-2 rounded">
    <option value="">Select Employee</option>
    @foreach($employees as $emp)
        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
    @endforeach
</select>

<input type="number" name="amount" placeholder="Loan Amount"
       class="w-full border p-2 rounded">

<input type="number" name="installments" placeholder="Installments"
       class="w-full border p-2 rounded">

<input type="date" name="start_date"
       class="w-full border p-2 rounded">

<button class="bg-blue-600 text-white px-4 py-2 rounded">
    Assign Loan
</button>

</form>

</div>
</x-app-layout>
