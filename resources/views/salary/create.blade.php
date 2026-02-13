<x-app-layout>
<div class="max-w-5xl mx-auto py-8">

<h2 class="text-2xl font-bold mb-6">Add Salary</h2>

<form method="POST" action="{{ route('admin.salary.store') }}"
      class="bg-white p-6 rounded shadow space-y-6">
@csrf

<div class="grid grid-cols-3 gap-4">

<select name="user_id" class="border p-2 rounded">
    @foreach($users as $user)
        <option value="{{ $user->id }}">
            {{ $user->name }}
        </option>
    @endforeach
</select>

<input type="number" name="month" placeholder="Month"
       class="border p-2 rounded">

<input type="number" name="year" placeholder="Year"
       class="border p-2 rounded">

</div>

<h3 class="font-semibold text-lg mt-4">Earnings</h3>

<div class="grid grid-cols-3 gap-4">

<input type="number" name="basic_salary" placeholder="Basic Salary" class="border p-2 rounded">
<input type="number" name="invigilation" placeholder="Invigilation" class="border p-2 rounded">
<input type="number" name="t_payment" placeholder="T Payment" class="border p-2 rounded">
<input type="number" name="eidi" placeholder="Eidi" class="border p-2 rounded">
<input type="number" name="increment" placeholder="Increment" class="border p-2 rounded">
<input type="number" name="other_earnings" placeholder="Other Earnings" class="border p-2 rounded">

</div>

<h3 class="font-semibold text-lg mt-4">Deductions</h3>

<div class="grid grid-cols-3 gap-4">

<input type="number" name="extra_leaves" placeholder="Extra Leaves" class="border p-2 rounded">
<input type="number" name="income_tax" placeholder="Income Tax" class="border p-2 rounded">
<input type="number" name="insurance" placeholder="Insurance" class="border p-2 rounded">
<input type="number" name="other_deductions" placeholder="Other Deductions" class="border p-2 rounded">

</div>

<button class="bg-green-600 text-white px-6 py-2 rounded">
    Save Salary
</button>

</form>
</div>
</x-app-layout>
