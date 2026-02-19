<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Add Leave</h2>

    <div class="bg-white p-6 rounded shadow">

        <form action="{{ route('leave.store') }}" method="POST">
            @csrf

            {{-- Employee --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    Employee
                </label>
                <select name="user_id" required
                        class="w-full border rounded px-3 py-2">
                    <option value="">Select Employee</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Leave Type --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    Leave Type
                </label>
                <select name="type" required
                        class="w-full border rounded px-3 py-2">
                    <option value="annual">Annual</option>
                    <option value="wop">WOP (Without Pay)</option>
                </select>
            </div>

            {{-- From Date --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    From Date
                </label>
                <input type="date"
                       name="from_date"
                       required
                       class="w-full border rounded px-3 py-2">
            </div>

            {{-- To Date --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    To Date
                </label>
                <input type="date"
                       name="to_date"
                       required
                       class="w-full border rounded px-3 py-2">
            </div>

            {{-- Leave Duration --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    Leave Duration
                </label>
                <select name="duration"
                        class="w-full border rounded px-3 py-2">
                    <option value="full">Full Day</option>
                    <option value="half">Half Day</option>
                </select>
            </div>

            {{-- Status --}}
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">
                    Status
                </label>
                <select name="status"
                        class="w-full border rounded px-3 py-2">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="mt-6">
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                    Save Leave
                </button>
            </div>

        </form>

    </div>

</div>
</x-app-layout>
