<x-app-layout>
    <div class="max-w-4xl mx-auto py-6 px-4">

        <h2 class="text-2xl font-bold mb-6">Create Staff</h2>

        <form method="POST" action="{{ route('admin.staff.store') }}">
            @csrf

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label>Name</label>
                    <input type="text" name="name" class="w-full border p-2" required>
                </div>

                <div>
                    <label>Email</label>
                    <input type="email" name="email" class="w-full border p-2" required>
                </div>

                <div>
                    <label>Employee ID</label>
                    <input type="text" name="employee_id" class="w-full border p-2" required>
                </div>

                <div>
                    <label>Department</label>
                    <input type="text" name="department" class="w-full border p-2" required>
                </div>

                <div>
                    <label>Designation</label>
                    <input type="text" name="designation" class="w-full border p-2" required>
                </div>

                <div>
                    <label>Salary</label>
                    <input type="number" step="0.01" name="salary" class="w-full border p-2" required>
                </div>

                <div>
                    <label>Joining Date</label>
                    <input type="date" name="joining_date" class="w-full border p-2" required>
                </div>

            </div>

            <div class="mt-6">
                <button class="bg-green-600 text-white px-6 py-2 rounded">
                    Save Staff
                </button>
            </div>
        </form>

    </div>
</x-app-layout>
