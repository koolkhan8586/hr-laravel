<x-app-layout>
<div class="max-w-7xl mx-auto py-8 px-6">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Edit Salary
        </h2>

        <a href="{{ route('admin.salary.index') }}"
           class="text-sm text-blue-600 hover:underline">
            ← Back to Salary List
        </a>
    </div>

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @php
        $isPosted = $salary->is_posted ?? false;
    @endphp

    <form method="POST" action="{{ route('admin.salary.update', $salary->id) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow p-6 space-y-6">

            {{-- ================= BASIC INFO ================= --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div>
                    <label class="block text-sm font-medium mb-1">Employee</label>
                    <select name="user_id"
                            class="w-full border rounded px-3 py-2"
                            {{ $isPosted ? 'disabled' : '' }}>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ $salary->user_id == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Month</label>
                    <input type="number"
                           name="month"
                           value="{{ $salary->month }}"
                           class="w-full border rounded px-3 py-2"
                           {{ $isPosted ? 'readonly' : '' }}>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Year</label>
                    <input type="number"
                           name="year"
                           value="{{ $salary->year }}"
                           class="w-full border rounded px-3 py-2"
                           {{ $isPosted ? 'readonly' : '' }}>
                </div>

            </div>

            {{-- ================= EARNINGS ================= --}}
            <div>
                <h3 class="text-lg font-semibold text-green-600 mb-3">
                    Earnings
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="number" step="0.01" name="basic_salary" id="basic_salary"
                        value="{{ $salary->basic_salary }}"
                        placeholder="Basic Salary"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="invigilation" id="invigilation"
                        value="{{ $salary->invigilation }}"
                        placeholder="Invigilation"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="t_payment" id="t_payment"
                        value="{{ $salary->t_payment }}"
                        placeholder="T Payment"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="eidi" id="eidi"
                        value="{{ $salary->eidi }}"
                        placeholder="Eidi"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="increment" id="increment"
                        value="{{ $salary->increment }}"
                        placeholder="Increment"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="other_earnings" id="other_earnings"
                        value="{{ $salary->other_earnings }}"
                        placeholder="Other Earnings"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>
                </div>
            </div>

            {{-- ================= DEDUCTIONS ================= --}}
            <div>
                <h3 class="text-lg font-semibold text-red-600 mb-3">
                    Deductions
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="number" step="0.01" name="extra_leaves" id="extra_leaves"
                        value="{{ $salary->extra_leaves }}"
                        placeholder="Extra Leaves"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="income_tax" id="income_tax"
                        value="{{ $salary->income_tax }}"
                        placeholder="Income Tax"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="loan_deduction" id="loan_deduction"
                        value="{{ $salary->loan_deduction }}"
                        placeholder="Loan Deduction"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="insurance" id="insurance"
                        value="{{ $salary->insurance }}"
                        placeholder="Insurance"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>

                    <input type="number" step="0.01" name="other_deductions" id="other_deductions"
                        value="{{ $salary->other_deductions }}"
                        placeholder="Other Deductions"
                        class="border rounded px-3 py-2"
                        {{ $isPosted ? 'readonly' : '' }}>
                </div>
            </div>

            {{-- ================= SUMMARY ================= --}}
            <div class="bg-gray-50 rounded p-4 flex justify-between items-center">

                <div>
                    <p class="text-sm text-gray-600">Gross Total</p>
                    <p id="grossTotal" class="text-xl font-bold">
                        Rs {{ number_format($salary->gross_total,2) }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-600">Total Deductions</p>
                    <p id="deductionsTotal" class="text-xl font-bold text-red-600">
                        Rs {{ number_format($salary->total_deductions,2) }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-600">Net Salary</p>
                    <p id="netTotal" class="text-xl font-bold text-green-600">
                        Rs {{ number_format($salary->net_salary,2) }}
                    </p>
                </div>

                <div>
                    @if($isPosted)
                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded">
                            Posted
                        </span>
                    @else
                        <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded">
                            Draft
                        </span>
                    @endif
                </div>

            </div>

            {{-- ================= ACTIONS ================= --}}
            <div class="flex justify-end space-x-4">

                @if(!$isPosted)
                    <form method="POST" action="{{ route('admin.salary.post', $salary->id) }}">
                        @csrf
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                            Post & Email
                        </button>
                    </form>
                @endif

                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow"
                        {{ $isPosted ? 'disabled' : '' }}>
                    Update Salary
                </button>

            </div>

        </div>
    </form>
</div>

{{-- ================= LIVE CALCULATION SCRIPT ================= --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const earnings = ['basic_salary','invigilation','t_payment','eidi','increment','other_earnings'];
    const deductions = ['extra_leaves','income_tax','loan_deduction','insurance','other_deductions'];

    function val(id){
        let v = parseFloat(document.getElementById(id)?.value);
        return isNaN(v) ? 0 : v;
    }

    function calculate(){
        let gross = 0;
        earnings.forEach(id => gross += val(id));

        let totalDed = 0;
        deductions.forEach(id => totalDed += val(id));

        let net = gross - totalDed;

        document.getElementById('grossTotal').innerText = 
            'Rs ' + gross.toLocaleString(undefined,{minimumFractionDigits:2});

        document.getElementById('deductionsTotal').innerText = 
            'Rs ' + totalDed.toLocaleString(undefined,{minimumFractionDigits:2});

        document.getElementById('netTotal').innerText = 
            'Rs ' + net.toLocaleString(undefined,{minimumFractionDigits:2});
    }

    [...earnings,...deductions].forEach(id=>{
        document.getElementById(id)?.addEventListener('input', calculate);
    });

});
</script>

</x-app-layout>
