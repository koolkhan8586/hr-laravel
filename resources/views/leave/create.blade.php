@if(session('error'))
    <div class="bg-red-200 text-red-800 p-3 mb-4 rounded">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-200 text-red-800 p-3 mb-4 rounded">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<x-app-layout>
<div class="max-w-4xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold mb-6">Apply Leave</h2>

    <form method="POST" action="{{ route('leave.store') }}">
        @csrf

        <div class="grid grid-cols-2 gap-4">

            <div>
                <label>Leave Type</label>
                <select name="type" class="w-full border p-2" required>
                    <option value="annual">Annual Leave</option>
                    <option value="without_pay">Without Pay</option>
                </select>
            </div>

            <div class="mb-4 p-3 bg-blue-100 rounded">
    <strong>Your Annual Leave Balance:</strong>
    {{ auth()->user()->annual_leave_balance }} days
</div>

            <div>
                <label>Start Date</label>
                <input type="date" name="start_date" class="w-full border p-2" required>
            </div>

            <div>
                <label>End Date</label>
                <input type="date" name="end_date" class="w-full border p-2" required>
            </div>

        </div>

        <div>
    <label>Duration</label>
    <select name="duration_type" id="duration_type" class="border p-2 w-full">
        <option value="full_day">Full Day</option>
        <option value="half_day">Half Day</option>
    </select>
</div>

<div id="half_day_option" style="display:none;">
    <label>Half Day Type</label>
    <select name="half_day_type" class="border p-2 w-full">
        <option value="morning">Morning</option>
        <option value="afternoon">Afternoon</option>
    </select>
</div>

<script>
document.getElementById('duration_type').addEventListener('change', function () {
    document.getElementById('half_day_option').style.display =
        this.value === 'half_day' ? 'block' : 'none';
});
</script>

        <div class="mt-4">
            <label>Reason</label>
            <textarea name="reason" class="w-full border p-2" required></textarea>
        </div>

        <div class="mt-6">
            <button class="bg-green-600 text-white px-6 py-2 rounded">
                Submit Leave
            </button>
        </div>

    </form>
<script>
document.querySelector('[name="duration_type"]').addEventListener('change', function() {
    let halfDayField = document.querySelector('[name="half_day_type"]').closest('div');
    let endDateField = document.querySelector('[name="end_date"]').closest('div');

    if (this.value === 'half_day') {
        halfDayField.style.display = 'block';
        endDateField.style.display = 'none';
        document.querySelector('[name="end_date"]').value =
            document.querySelector('[name="start_date"]').value;
    } else {
        halfDayField.style.display = 'none';
        endDateField.style.display = 'block';
    }
});
</script>

</div>
</x-app-layout>
