<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Edit Staff</h2>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <ul class="list-disc ml-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.staff.update', $staff->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-6">

            <div>
                <label class="block font-semibold mb-1">Name</label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $staff->user->name) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email', $staff->user->email) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
    <label class="block font-semibold mb-1">Employee Code</label>

    <!-- SHOW (readonly) -->
    <input type="text"
           value="{{ old('employee_code', $staff->user->employee_code) }}"
           class="w-full border p-2 rounded uppercase"
           readonly>

    <!-- SEND TO BACKEND -->
    <input type="hidden"
           name="employee_code"
           value="{{ old('employee_code', $staff->user->employee_code) }}">
</div>

            <div>
                <label class="block font-semibold mb-1">Department</label>
                <input type="text"
                       name="department"
                       value="{{ old('department', $staff->department) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Designation</label>
                <input type="text"
                       name="designation"
                       value="{{ old('designation', $staff->designation) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Salary</label>
                <input type="number"
                       step="0.01"
                       name="salary"
                       value="{{ old('salary', $staff->salary) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Joining Date</label>
                <input type="date"
                       name="joining_date"
                       value="{{ old('joining_date', $staff->joining_date) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div class="mt-3">
    <label class="block font-semibold mb-1">Office Location</label>

    <select name="office_location_id" class="w-full border p-2 rounded">
        <option value="">Select Office</option>

        @foreach($locations as $location)
            <option value="{{ $location->id }}"
                {{ $staff->user->office_location_id == $location->id ? 'selected' : '' }}>
                {{ $location->name }}
            </option>
        @endforeach
    </select>
</div>

            <div class="mt-4">

    <!-- 🔓 Allow Anywhere -->
    <label class="flex items-center gap-2">
        <input type="checkbox" name="allow_anywhere_attendance"
        {{ $staff->allow_anywhere_attendance ? 'checked' : '' }}>
        
        <span class="font-semibold text-green-700">
            Allow Attendance Anywhere
        </span>
    </label>

</div>

<div class="mt-3">

    <!-- ⏱ Temporary Override -->
    <label class="block mb-1 font-semibold">
        Allow Until (Optional)
    </label>

    <input type="datetime-local"
           name="attendance_override_until"
           value="{{ $staff->attendance_override_until }}"
           class="border p-2 w-full rounded">

</div>

        </div>

        <div class="mt-8">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                Update Staff
            </button>

            <a href="{{ route('admin.staff.index') }}"
               class="ml-3 bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                Cancel
            </a>
        </div>

    </form>

</div>
</x-app-layout>
