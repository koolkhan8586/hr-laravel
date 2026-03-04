<x-app-layout>

<div class="max-w-4xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Create Shift</h2>

<form method="POST" action="{{ route('shifts.store') }}" class="space-y-4">

@csrf

<div>
<label class="block font-semibold">Shift Name</label>
<input type="text" name="name" class="w-full border rounded p-2" required>
</div>

<div>
<label class="block font-semibold">Start Time</label>
<input type="time" name="start_time" class="w-full border rounded p-2" required>
</div>

<div>
<label class="block font-semibold">End Time</label>
<input type="time" name="end_time" class="w-full border rounded p-2" required>
</div>

<div>
<label class="block font-semibold">Grace Minutes</label>
<input type="number" name="grace_minutes" class="w-full border rounded p-2" value="30">
</div>

<button class="bg-blue-500 text-white px-4 py-2 rounded">
Save Shift
</button>

</form>

</div>

</x-app-layout>
