<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Edit Salary</h2>

        <a href="{{ route('admin.salary.index') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded shadow">
            Back
        </a>
    </div>

    <form method="POST"
          action="{{ route('admin.salary.update', $salary->id) }}"
          class="bg-white p-6 rounded shadow space-y-6">

        @csrf
        @method('PUT')

        {{-- ================= BASIC INFO ================= --}}
        <div class="grid grid-cols-3 gap-6">

            <div>
                <label class="block text-sm font-medium mb-1">Employee</label>
                <select name="user_id" class="w-full border rounded px-3 py-2">
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
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Year</label>
                <input type="number"
                       name="year"
                       value="{{ $salary->year }}"
                       class="w-full border rounded px-3 py-2">
            </div>

        </div>

        {{-- ================= EARNINGS ================= --}}
        <div>
            <h3 class="text-lg font-semibold mb-3 text-green-600">Earnings</h3>

            <div class="grid grid-cols-3 gap-6">

                <input type="number" step="0.01" name="basic_salary"
                       value="{{ $salary->basic_salary }}"
                       placeholder="Basic Salary"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="invigilation"
                       value="{{ $salary->invigilation }}"
                       placeholder="Invigilation"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="t_payment"
                       value="{{ $salary->t_payment }}"
                       placeholder="T Payment"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="eidi"
                       value="{{ $salary->eidi }}"
                       placeholder="Eidi"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="increment"
                       value="{{ $salary->increment }}"
                       placeholder="Increment"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="other_earnings"
                       value="{{ $salary->other_earnings }}"
                       placeholder="Other Earnings"
                       class="border rounded px-3 py-2">

            </div>
        </div>

        {{-- ================= DEDUCTIONS ================= --}}
        <div>
            <h3 class="text-lg font-semibold mb-3 text-red-600">Deductions</h3>

            <div class="grid grid-cols-3 gap-6">

                <input type="number" step="0.01" name="extra_leaves"
                       value="{{ $salary->extra_leaves }}"
                       placeholder="Extra Leaves"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="income_tax"
                       value="{{ $salary->income_tax }}"
                       placeholder="Income Tax"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="loan_deduction"
                       value="{{ $salary->loan_deduction }}"
                       placeholder="Loan Deduction"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="insurance"
                       value="{{ $salary->insurance }}"
                       placeholder="Insurance"
                       class="border rounded px-3 py-2">

                <input type="number" step="0.01" name="other_deductions"
                       value="{{ $salary->other_deductions }}"
                       placeholder="Other Deductions"
                       class="border rounded px-3 py-2">

            </div>
        </div>

        {{-- ================= ACTION ================= --}}
        <div class="flex justify-end">
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow">
                Update Salary
            </button>
        </div>

    </form>

</div>

</x-app-layout>
