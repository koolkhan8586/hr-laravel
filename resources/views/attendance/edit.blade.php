<x-app-layout>
<div class="max-w-4xl mx-auto py-6 px-6">

    <h2 class="text-2xl font-bold mb-6">
        Edit Attendance
    </h2>

    <div class="bg-white p-6 rounded shadow">

        <form method="POST"
              action="{{ route('admin.attendance.update',$attendance->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block mb-1">Employee</label>
                <select name="user_id"
                        class="w-full border px-3 py-2 rounded"
                        required>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                            {{ $attendance->user_id == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block mb-1">Clock In</label>
                <input type="datetime-local"
                       name="clock_in"
                       value="{{ \Carbon\Carbon::parse($attendance->clock_in)->format('Y-m-d\TH:i') }}"
                       class="w-full border px-3 py-2 rounded"
                       required>
            </div>

            <div class="mb-4">
                <label class="block mb-1">Clock Out</label>
                <input type="datetime-local"
                       name="clock_out"
                       value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('Y-m-d\TH:i') : '' }}"
                       class="w-full border px-3 py-2 rounded">
            </div>

            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                Update Attendance
            </button>

        </form>

    </div>

</div>
</x-app-layout>
