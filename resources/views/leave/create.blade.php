<x-app-layout>

<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Apply Leave</h2>

    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="p-3 rounded mb-4" style="background:#fef9c3; color:#a16207;">
            {{ session('warning') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-3 rounded mb-4" style="background:#fee2e2; color:#b91c1c;">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        // Annual leave left, per employee, so the form can say up front that
        // a request bigger than the balance is taken without pay.
        $balanceMap = ($balances ?? collect())->map(fn ($d) => (float) $d);
    @endphp

    <div id="balanceNote" class="p-3 rounded mb-4" style="background:#eff6ff; color:#1d4ed8;"></div>

    <form method="POST" action="{{ route('leave.store') }}">
        @csrf

        {{-- ✅ EMPLOYEE DROPDOWN (ONLY FOR ADMIN) --}}
        @if(auth()->user()->role === 'admin')
            <div class="mb-4">
                <label class="block font-semibold mb-2">Select Employee</label>
                <select name="employee_id"
                        id="employee_id"
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
                    id="leave_type"
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
                   id="start_date"
                   class="w-full border rounded px-3 py-2"
                   required>
        </div>


        {{-- TO DATE --}}
        <div class="mb-4">
            <label class="block font-semibold mb-2">To Date</label>
            <input type="date"
                   name="end_date"
                   id="end_date"
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

{{-- Says, before the form is sent, whether this request fits the balance --}}
<script>
(function () {

    const balances = @json($balanceMap);
    const ownId    = {{ auth()->id() }};

    const note      = document.getElementById('balanceNote');
    const employee  = document.getElementById('employee_id');
    const typeField = document.getElementById('leave_type');
    const from      = document.getElementById('start_date');
    const to        = document.getElementById('end_date');
    const duration  = document.getElementById('duration_type');

    const nice = (n) => String(Math.round(n * 10) / 10).replace(/\.0$/, '');

    function daysAsked() {
        if (duration && duration.value === 'half_day') return 0.5;
        if (!from.value || !to.value) return null;

        const start = new Date(from.value), end = new Date(to.value);
        if (end < start) return null;

        return Math.round((end - start) / 86400000) + 1;
    }

    function update() {
        const id = employee ? employee.value : ownId;

        if (!id || !(id in balances)) {
            note.style.display = 'none';
            return;
        }

        const left = balances[id];

        if (typeField.value !== 'annual') {
            note.style.display = '';
            note.style.background = '#eff6ff';
            note.style.color = '#1d4ed8';
            note.textContent = 'Leave without pay is deducted from the salary. '
                + nice(left) + ' day(s) of annual leave are left.';
            return;
        }

        const asked = daysAsked();

        note.style.display = '';

        if (asked !== null && asked > left) {
            note.style.background = '#fef9c3';
            note.style.color = '#a16207';
            note.textContent = 'Only ' + nice(left) + ' day(s) of annual leave are left and '
                + nice(asked) + ' have been asked for, so this leave will be recorded as '
                + 'Without Pay and deducted from the salary.';
        } else {
            note.style.background = '#eff6ff';
            note.style.color = '#1d4ed8';
            note.textContent = nice(left) + ' day(s) of annual leave are left'
                + (asked !== null ? ', and ' + nice(asked) + ' are being asked for.' : '.');
        }
    }

    [employee, typeField, from, to, duration].forEach(function (field) {
        if (field) {
            field.addEventListener('change', update);
            field.addEventListener('input', update);
        }
    });

    update();
})();
</script>

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
