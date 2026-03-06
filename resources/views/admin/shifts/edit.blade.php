<x-app-layout>

<div class="max-w-4xl mx-auto py-6">

    <h2 class="text-2xl font-bold mb-6">Edit Shift</h2>

    <form method="POST" action="{{ route('shifts.update', $shift->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block font-semibold">Shift Name</label>
            <input type="text" name="name" value="{{ $shift->name }}" class="border p-2 w-full rounded">
        </div>

        <div class="mb-4">
            <label class="block font-semibold">Start Time</label>
            <input type="time" name="start_time" value="{{ $shift->start_time }}" class="border p-2 w-full rounded">
        </div>

        <div class="mb-4">
            <label class="block font-semibold">End Time</label>
            <input type="time" name="end_time" value="{{ $shift->end_time }}" class="border p-2 w-full rounded">
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Update Shift
        </button>

    </form>

</div>

</x-app-layout>
