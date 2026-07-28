<x-app-layout>

<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Apply Leave</h2>

    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('leave.store') }}">
        @csrf

        {{-- ✅ EMPLOYEE DROPDOWN (ONLY FOR ADMIN) --}}
        @if(auth()->user()->role === 'admin')
            <div class="mb-4">
                <label class="block font-semibold mb-2">Select Employee</label>
                <select name="employee_id"
                        class="w-full border rounded px-3 py-2"
                        required>
                    <option value="">-- Select Employee --</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif


        {{-- LEAVE TYPE --}}
        <div class="mb-4">
            <label class="block font-semibold mb-2">Leave Type</label>
            <select name="type"
                    class="w-full border rounded px-3 py-2"
                    required>
                <option value="annual">Annual</option>
                <option value="without_pay">Without Pay</option>
            </select>
        </div>


        {{-- FROM DATE --}}
        <div class="mb-4">
            <label class="block font-semibold mb-2">From Date</label>
            <input type="date"
                   name="start_date"
                   class="w-full border rounded px-3 py-2"
                   required>
        </div>


        {{-- TO DATE --}}
        <div class="mb-4">
            <label class="block font-semibold mb-2">To Date</label>
            <input type="date"
                   name="end_date"
                   class="w-full border rounded px-3 py-2"
                   required>
        </div>


        {{-- DURATION --}}
        <div class="mb-4">
            <label class="block font-semibold mb-2">Leave Duration</label>
            <select name="duration_type"
                    id="duration_type"
                    class="w-full border rounded px-3 py-2"
                    required>
                <option value="full_day" {{ old('duration_type') == 'full_day' ? 'selected' : '' }}>Full Day</option>
                <option value="half_day" {{ old('duration_type') == 'half_day' ? 'selected' : '' }}>Half Day</option>
            </select>
        </div>


        {{-- HALF DAY TYPE --}}
        <div class="mb-4" id="half_day_section" style="display:none;">
            <label class="block font-semibold mb-2">Half Day Type</label>
            <select name="half_day_type"
                    id="half_day_type"
                    class="w-full border rounded px-3 py-2">
                <option value="">Select</option>
                <option value="morning" {{ old('half_day_type') == 'morning' ? 'selected' : '' }}>Morning</option>
                <option value="afternoon" {{ old('half_day_type') == 'afternoon' ? 'selected' : '' }}>Afternoon</option>
            </select>
        </div>


        {{-- REASON --}}
        <div class="mb-6">
            <label class="block font-semibold mb-2">Reason</label>
            <textarea name="reason"
                      rows="4"
                      class="w-full border rounded px-3 py-2"
                      placeholder="Enter reason for leave"></textarea>
        </div>


        {{-- SUBMIT --}}
        <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Save Leave
        </button>

    </form>

</div>

{{-- Half Day JS --}}
<script>
(function () {
    const duration = document.getElementById('duration_type');
    const section  = document.getElementById('half_day_section');
    const type     = document.getElementById('half_day_type');

    function toggleHalfDay() {
        const isHalfDay = duration.value === 'half_day';
        section.style.display = isHalfDay ? 'block' : 'none';
        type.required = isHalfDay;

        if (!isHalfDay) {
            type.value = '';
        }
    }

    duration.addEventListener('change', toggleHalfDay);
    toggleHalfDay();
})();
</script>

</x-app-layout>
