<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Create Staff</h2>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <ul class="list-disc ml-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.staff.store') }}">
        @csrf

        <div class="grid grid-cols-2 gap-6">

            <div>
                <label class="block font-semibold mb-1">Name</label>
                <input type="text"
                       name="name"
                       value="{{ old('name') }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Employee Code</label>
                <input type="text"
                       name="employee_code"
                       value="{{ old('employee_code') }}"
                       placeholder="EMP001"
                       class="w-full border p-2 rounded uppercase"
                       required>
                <small class="text-gray-500 text-xs">
                    Example: EMP001, EMP002
                </small>
            </div>

            <div>
                <label class="block font-semibold mb-1">Department</label>
                <input type="text"
                       name="department"
                       value="{{ old('department') }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Designation</label>
                <input type="text"
                       name="designation"
                       value="{{ old('designation') }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Salary</label>
                <input type="number"
                       step="0.01"
                       name="salary"
                       value="{{ old('salary') }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Joining Date</label>
                <input type="date"
                       name="joining_date"
                       value="{{ old('joining_date') }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

        </div>

        <div class="mt-8">
            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                Save Staff
            </button>

            <a href="{{ route('admin.staff.index') }}"
               class="ml-3 bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                Cancel
            </a>
        </div>

    </form>

</div>
</x-app-layout>
