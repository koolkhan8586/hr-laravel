<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Edit Leave</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow rounded p-6">

        <form method="POST"
              action="{{ route('admin.leave.update', $leave->id) }}">
            @csrf
            @method('PUT')

            {{-- Employee --}}
            <div class="mb-4">
                <label class="block font-semibold mb-1">Employee</label>
                <select name="user_id"
                        class="w-full border rounded px-3 py-2"
                        required>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                            {{ $leave->user_id == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Leave Type --}}
            <div class="mb-4">
                <label class="block font-semibold mb-1">Leave Type</label>
                <select name="type"
                        class="w-full border rounded px-3 py-2"
                        required>
                    <option value="annual"
                        {{ $leave->type == 'annual' ? 'selected' : '' }}>
                        Annual Leave
                    </option>
                    <option value="without_pay"
                        {{ $leave->type == 'without_pay' ? 'selected' : '' }}>
                        Leave Without Pay (WOP)
                    </option>
                </select>
            </div>

            {{-- Start Date --}}
            <div class="mb-4">
                <label class="block font-semibold mb-1">Start Date</label>
                <input type="date"
                       name="start_date"
                       value="{{ $leave->start_date }}"
                       class="w-full border rounded px-3 py-2"
                       required>
            </div>

            {{-- End Date --}}
            <div class="mb-4">
                <label class="block font-semibold mb-1">End Date</label>
                <input type="date"
                       name="end_date"
                       value="{{ $leave->end_date }}"
                       class="w-full border rounded px-3 py-2"
                       required>
            </div>

            {{-- Duration --}}
            <div class="mb-4">
                <label class="block font-semibold mb-1">Duration</label>
                <select name="duration_type"
                        class="w-full border rounded px-3 py-2"
                        required>
                    <option value="full_day"
                        {{ $leave->duration_type == 'full_day' ? 'selected' : '' }}>
                        Full Day
                    </option>
                    <option value="half_day"
                        {{ $leave->duration_type == 'half_day' ? 'selected' : '' }}>
                        Half Day
                    </option>
                </select>
            </div>

            {{-- Status --}}
            <div class="mb-6">
                <label class="block font-semibold mb-1">Status</label>
                <select name="status"
                        class="w-full border rounded px-3 py-2"
                        required>
                    <option value="pending"
                        {{ $leave->status == 'pending' ? 'selected' : '' }}>
                        Pending
                    </option>
                    <option value="approved"
                        {{ $leave->status == 'approved' ? 'selected' : '' }}>
                        Approved
                    </option>
                    <option value="rejected"
                        {{ $leave->status == 'rejected' ? 'selected' : '' }}>
                        Rejected
                    </option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end">
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded shadow">
                    Update Leave
                </button>
            </div>

        </form>

    </div>

</div>
</x-app-layout>
