<x-app-layout>

<div class="max-w-4xl mx-auto py-6">

<h2 class="text-2xl font-bold mb-4">Assign Schedule</h2>

<form method="POST" action="{{ route('schedules.store') }}">

@csrf

<div class="mb-3">
<label>Employee</label>
<select name="user_id" class="w-full border p-2">

@foreach($users as $user)

<option value="{{ $user->id }}">{{ $user->name }}</option>

@endforeach

</select>
</div>

<div class="mb-3">
<label>Date</label>
<input type="date" name="date" class="w-full border p-2">
</div>

<div class="mb-3">
<label>Shift</label>
<select name="shift_id" class="w-full border p-2">

<option value="">OFF</option>

@foreach($shifts as $shift)

<option value="{{ $shift->id }}">{{ $shift->name }}</option>

@endforeach

</select>
</div>

<button class="bg-green-500 text-white px-4 py-2 rounded">
Save Schedule
</button>

</form>

</div>

</x-app-layout>
