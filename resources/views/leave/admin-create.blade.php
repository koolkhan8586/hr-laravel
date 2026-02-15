<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Add Leave</h2>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- ERROR MESSAGE --}}
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white p-6 rounded shadow">

        <form method="POST" action="{{ route('admin.leave.store') }}">
            @csrf

            {{-- Employee --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">Employee</label>
                <select name="user_id" required class="w-full border rounded px-3 py-2">
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
                <label class="block font-medium mb-1">Leave Type</label>
                <select name="type" required class="w-full border rounded px-3 py-2">
                    <option value="annual">Annual</option>
                    <option value="without_pay">WOP (Without Pay)</option>
                </select>
            </div>

            {{-- From Date --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">From Date</label>
                <input type="date"
                       name="start_date"
                       required
                       class="w-full border rounded px-3 py-2">
            </div>

            {{-- To Date --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">To Date</label>
                <input type="date"
                       name="end_date"
                       required
                       class="w-full border rounded px-3 py-2">
            </div>

            {{-- Duration --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">Leave Duration</label>
                <select name="duration_type"
                        id="duration_type"
                        required
                        class="w-full border rounded px-3 py-2">
                    <option value="full_day">Full Day</option>
                    <option value="half_day">Half Day</option>
                </select>
            </div>

            {{-- Half Day Type --}}
            <div class="mb-4" id="half_day_section" style="display:none;">
                <label class="block font-medium mb-1">Half Day Type</label>
                <select name="half_day_type"
                        class="w-full border rounded px-3 py-2">
                    <option value="">Select</option>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                </select>
            </div>

            {{-- Status --}}
            <div class="mb-4">
                <label class="block font-medium mb-1">Status</label>
                <select name="status" required class="w-full border rounded px-3 py-2">
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
                Save Leave
            </button>

        </form>
    </div>
</div>

{{-- Half Day JS --}}
<script>
document.getElementById('duration_type').addEventListener('change', function() {
    if (this.value === 'half_day') {
        document.getElementById('half_day_section').style.display = 'block';
    } else {
        document.getElementById('half_day_section').style.display = 'none';
    }
});
</script>

</x-app-layout>
