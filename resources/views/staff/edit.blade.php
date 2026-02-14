<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-4">

    <h2 class="text-2xl font-bold mb-6">Edit Staff</h2>

    @if(session('success'))
        <div class="bg-green-200 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.staff.update', $staff->id) }}" method="POST">

        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-6">

            <div>
                <label class="block font-semibold mb-1">Name</label>
                <input type="text" name="name"
                       value="{{ $staff->user->name }}"
                       class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Email</label>
                <input type="email" name="email"
                       value="{{ $staff->user->email }}"
                       class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Employee ID</label>
                <input type="text" name="employee_id"
                       value="{{ $staff->employee_id }}"
                       class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Department</label>
                <input type="text" name="department"
                       value="{{ $staff->department }}"
                       class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Designation</label>
                <input type="text" name="designation"
                       value="{{ $staff->designation }}"
                       class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Salary</label>
                <input type="number" name="salary"
                       value="{{ $staff->salary }}"
                       class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Joining Date</label>
                <input type="date" name="joining_date"
                       value="{{ $staff->joining_date }}"
                       class="w-full border p-2 rounded" required>
            </div>

        </div>

        <div class="mt-6">
            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded">
                Update Staff
            </button>

            <a href="{{ route('admin.staff.index') }}"
               class="ml-3 bg-gray-500 text-white px-6 py-2 rounded">
                Cancel
            </a>
        </div>

    </form>

</div>
</x-app-layout>
