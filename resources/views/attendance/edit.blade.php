<x-app-layout>
<div class="max-w-4xl mx-auto py-6 px-6">

<h2 class="text-2xl font-bold mb-6">Edit Attendance</h2>

<form action="{{ route('admin.attendance.update',$attendance->id) }}"
      method="POST"
      class="bg-white shadow rounded p-6 space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block font-medium">Employee</label>
        <input type="text"
               value="{{ $attendance->user->name }}"
               class="border p-2 w-full bg-gray-100"
               readonly>
    </div>

    <div>
        <label class="block font-medium">Clock In</label>
        <input type="datetime-local"
               name="clock_in"
               value="{{ \Carbon\Carbon::parse($attendance->clock_in)->format('Y-m-d\TH:i') }}"
               class="border p-2 w-full"
               required>
    </div>

    <div>
        <label class="block font-medium">Clock Out</label>
        <input type="datetime-local"
               name="clock_out"
               value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('Y-m-d\TH:i') : '' }}"
               class="border p-2 w-full">
    </div>

    <div class="flex justify-end">
        <button type="submit"
                class="bg-green-600 text-white px-6 py-2 rounded">
            Update
        </button>
    </div>

</form>

</div>
</x-app-layout>
