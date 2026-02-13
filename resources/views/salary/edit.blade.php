<x-app-layout>

<div class="max-w-5xl mx-auto py-8 px-6">

    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Edit Salary
        </h2>

        <a href="{{ route('admin.salary.index') }}"
           class="text-blue-600 hover:underline text-sm">
            ← Back to Salary List
        </a>
    </div>


    {{-- Error Messages --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-100 text-red-700 p-4 rounded">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form method="POST" action="{{ route('admin.salary.update', $salary->id) }}"
          class="bg-white shadow rounded-lg p-6 space-y-8">

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
                <input type="number" name="month"
                       value="{{ $salary->month }}"
                       class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Year</label>
                <input type="number" name="year"
                       value="{{ $salary->year }}"
                       class="w-full border rounded px-3 py-2">
            </div>

        </div>


        {{-- ================= EARNINGS ================= --}}
        <div>
            <h3 class="text-lg font-semibold text-green-600 border-b pb-2 mb-4">
                Earnings
            </h3>

            <div class="grid grid-cols-2 gap-6">

                <div>
                    <label class="block text-sm mb-1">Basic Salary</label>
                    <input type="number" step="0.01" name="basic_salary"
                        value="{{ $salary->basic_salary }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">Invigilation</label>
                    <input type="number" step="0.01" name="invigilation"
                        value="{{ $salary->invigilation }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">T Payment</label>
                    <input type="number" step="0.01" name="t_payment"
                        value="{{ $salary->t_payment }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">Eidi</label>
                    <input type="number" step="0.01" name="eidi"
                        value="{{ $salary->eidi }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">Increment</label>
                    <input type="number" step="0.01" name="increment"
                        value="{{ $salary->increment }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">Other Earnings</label>
                    <input type="number" step="0.01" name="other_earnings"
                        value="{{ $salary->other_earnings }}"
                        class="w-full border rounded px-3 py-2">
                </div>

            </div>
        </div>


        {{-- ================= DEDUCTIONS ================= --}}
        <div>
            <h3 class="text-lg font-semibold text-red-600 border-b pb-2 mb-4">
                Deductions
            </h3>

            <div class="grid grid-cols-2 gap-6">

                <div>
                    <label class="block text-sm mb-1">Extra Leaves</label>
                    <input type="number" step="0.01" name="extra_leaves"
                        value="{{ $salary->extra_leaves }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">Income Tax</label>
                    <input type="number" step="0.01" name="income_tax"
                        value="{{ $salary->income_tax }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">Loan Deduction</label>
                    <input type="number" step="0.01" name="loan_deduction"
                        value="{{ $salary->loan_deduction }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">Insurance</label>
                    <input type="number" step="0.01" name="insurance"
                        value="{{ $salary->insurance }}"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm mb-1">Other Deductions</label>
                    <input type="number" step="0.01" name="other_deductions"
                        value="{{ $salary->other_deductions }}"
                        class="w-full border rounded px-3 py-2">
                </div>

            </div>
        </div>


        {{-- ================= SUMMARY DISPLAY ================= --}}
        <div class="bg-gray-50 p-4 rounded text-sm">
            <p><strong>Gross Total:</strong> Rs {{ number_format($salary->gross_total,2) }}</p>
            <p><strong>Total Deductions:</strong> Rs {{ number_format($salary->total_deductions,2) }}</p>
            <p class="text-green-600 font-semibold">
                Net Salary: Rs {{ number_format($salary->net_salary,2) }}
            </p>
        </div>


        {{-- ================= SUBMIT ================= --}}
        <div class="flex justify-end">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow">
                Update Salary
            </button>
        </div>

    </form>

</div>

</x-app-layout>
