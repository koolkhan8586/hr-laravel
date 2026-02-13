<x-app-layout>
<div class="max-w-5xl mx-auto px-6 py-8">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Salary Slip - {{ $salary->user->name }}
        </h2>

        <a href="{{ route('admin.salary.index') }}"
           class="bg-gray-600 text-white px-4 py-2 rounded">
            Back
        </a>
    </div>

    <div class="bg-white shadow rounded p-6">

        <div class="mb-6">
            <p><strong>Employee:</strong> {{ $salary->user->name }}</p>
            <p><strong>Month:</strong> {{ $salary->month }}</p>
            <p><strong>Year:</strong> {{ $salary->year }}</p>
            <p><strong>Status:</strong>
                @if($salary->is_posted)
                    <span class="text-green-600 font-semibold">Posted</span>
                @else
                    <span class="text-yellow-600 font-semibold">Draft</span>
                @endif
            </p>
        </div>

        <hr class="my-6">

        <div class="grid grid-cols-2 gap-10">

            {{-- Earnings --}}
            <div>
                <h3 class="font-semibold text-lg mb-3 text-green-700">
                    Earnings
                </h3>

                <p>Basic Salary: Rs {{ number_format($salary->basic_salary,2) }}</p>
                <p>Invigilation: Rs {{ number_format($salary->invigilation,2) }}</p>
                <p>T Payment: Rs {{ number_format($salary->t_payment,2) }}</p>
                <p>Eidi: Rs {{ number_format($salary->eidi,2) }}</p>
                <p>Increment: Rs {{ number_format($salary->increment,2) }}</p>
                <p>Other Earnings: Rs {{ number_format($salary->other_earnings,2) }}</p>

                <hr class="my-3">
                <p class="font-bold">
                    Gross Total: Rs {{ number_format($salary->gross_total,2) }}
                </p>
            </div>

            {{-- Deductions --}}
            <div>
                <h3 class="font-semibold text-lg mb-3 text-red-700">
                    Deductions
                </h3>

                <p>Extra Leaves: Rs {{ number_format($salary->extra_leaves,2) }}</p>
                <p>Income Tax: Rs {{ number_format($salary->income_tax,2) }}</p>
                <p>Loan Deduction: Rs {{ number_format($salary->loan_deduction,2) }}</p>
                <p>Insurance: Rs {{ number_format($salary->insurance,2) }}</p>
                <p>Other Deductions: Rs {{ number_format($salary->other_deductions,2) }}</p>

                <hr class="my-3">
                <p class="font-bold">
                    Total Deductions: Rs {{ number_format($salary->total_deductions,2) }}
                </p>
            </div>

        </div>

        <hr class="my-6">

        <div class="text-right text-xl font-bold text-green-700">
            Net Salary: Rs {{ number_format($salary->net_salary,2) }}
        </div>

    </div>

</div>
</x-app-layout>
