<x-app-layout>
<div class="max-w-4xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Add Loan Opening Balance</h2>

<form method="POST" action="{{ route('admin.loan.store') }}">
@csrf

<div class="mb-3">
<label>Employee</label>
<select name="user_id" class="border w-full p-2">
@foreach($employees as $emp)
<option value="{{ $emp->id }}">{{ $emp->name }}</option>
@endforeach
</select>
</div>

<div class="mb-3">
<label>Opening Balance</label>
<input type="number" name="opening_balance" class="border w-full p-2">
</div>

<div class="mb-3">
<label>Installments</label>
<input type="number" name="installments" class="border w-full p-2">
</div>

<div class="mb-3">
<label>Monthly Deduction</label>
<input type="number" name="monthly_deduction" class="border w-full p-2">
</div>

<button class="bg-blue-600 text-white px-4 py-2 rounded">
Save Loan
</button>

</form>

</div>
</x-app-layout>
