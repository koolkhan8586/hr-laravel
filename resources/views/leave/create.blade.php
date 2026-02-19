<x-app-layout>
<div class="max-w-4xl mx-auto py-8">

    <h2 class="text-2xl font-bold mb-6">Apply Leave</h2>

    <form method="POST" action="{{ route('leave.store') }}">
        @csrf

        {{-- Leave Type --}}
        <div class="mb-4">
            <label class="block font-medium mb-1">Leave Type</label>
            <select name="type" required class="w-full border rounded px-3 py-2">
                <option value="annual">Annual</option>
                <option value="without_pay">Without Pay</option>
            </select>
        </div>

        {{-- Start Date --}}
        <div class="mb-4">
            <label class="block font-medium mb-1">From Date</label>
            <input type="date" name="start_date" required
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- End Date --}}
        <div class="mb-4">
            <label class="block font-medium mb-1">To Date</label>
            <input type="date" name="end_date" required
                   class="w-full border rounded px-3 py-2">
        </div>

        {{-- Duration --}}
        <div class="mb-4">
            <label class="block font-medium mb-1">Leave Duration</label>
            <select name="duration_type" required
                    class="w-full border rounded px-3 py-2">
                <option value="full_day">Full Day</option>
                <option value="half_day">Half Day</option>
            </select>
        </div>

        {{-- Reason --}}
        <div class="mb-4">
            <label class="block font-medium mb-1">Reason</label>
            <textarea name="reason" rows="3"
                      class="w-full border rounded px-3 py-2"
                      placeholder="Enter reason for leave"></textarea>
        </div>

        {{-- Hidden status --}}
        <input type="hidden" name="status" value="pending">

        <button type="submit"
                class="bg-green-600 text-white px-6 py-2 rounded">
            Save Leave
        </button>

    </form>

</div>
</x-app-layout>
