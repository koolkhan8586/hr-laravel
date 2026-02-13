<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-4">

    <h2 class="text-2xl font-bold mb-6">Create Salary</h2>

    <form action="{{ route('admin.salary.store') }}" method="POST" class="space-y-4 bg-white p-6 rounded-lg shadow">
        @csrf

        <div>
            <label class="block font-medium">Employee</label>
            <select name="user_id" class="w-full border rounded p-2">
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label>Month</label>
                <input type="number" name="month" class="w-full border p-2 rounded" required>
            </div>
            <div>
                <label>Year</label>
                <input type="number" name="year" class="w-full border p-2 rounded" required>
            </div>
        </div>

        <div>
            <label>Basic Salary</label>
            <input type="number" step="0.01" name="basic_salary" class="w-full border p-2 rounded">
        </div>

        <button type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded">
            Save Salary
        </button>
    </form>

</div>
</x-app-layout>
