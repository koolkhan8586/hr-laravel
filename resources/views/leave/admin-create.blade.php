<x-app-layout>
<div class="max-w-4xl mx-auto py-8">

<h2 class="text-2xl font-bold mb-6">Add Leave</h2>

<form method="POST" action="{{ route('admin.leave.store') }}">
@csrf

<select name="user_id" class="w-full border p-2 rounded mb-4">
    @foreach($employees as $emp)
        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
    @endforeach
</select>

<select name="type" class="w-full border p-2 rounded mb-4">
    <option value="annual">Annual</option>
    <option value="casual">Casual</option>
    <option value="sick">Sick</option>
</select>

<input type="number" step="0.5" name="days"
       class="w-full border p-2 rounded mb-4"
       placeholder="Days">

<select name="status" class="w-full border p-2 rounded mb-4">
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
</select>

<button class="bg-green-600 text-white px-6 py-2 rounded">
    Save Leave
</button>

</form>

</div>
</x-app-layout>
