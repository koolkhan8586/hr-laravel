<x-app-layout>
<div class="max-w-4xl mx-auto py-6 px-6">

    <h2 class="text-2xl font-bold mb-6">
        Add Attendance
    </h2>

    <div class="bg-white p-6 rounded shadow">

        <form method="POST"
              action="{{ route('admin.attendance.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block mb-1">Employee</label>
                <select name="user_id"
                        class="w-full border px-3 py-2 rounded"
                        required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block mb-1">Clock In</label>
                <input type="datetime-local"
                       name="clock_in"
                       class="w-full border px-3 py-2 rounded"
                       required>
            </div>

            <div class="mb-4">
                <label class="block mb-1">Clock Out</label>
                <input type="datetime-local"
                       name="clock_out"
                       class="w-full border px-3 py-2 rounded">
            </div>

            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                Save Attendance
            </button>

        </form>

    </div>

</div>
</x-app-layout>
