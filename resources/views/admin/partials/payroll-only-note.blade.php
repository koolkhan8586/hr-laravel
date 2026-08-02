{{--
    Says how many payroll-only employees the screen is leaving out, and lets
    them be pulled back in for one look without changing anyone's record.

    Expects: $includePayrollOnly, $payrollOnlyCount
--}}
@php
    $includePayrollOnly = $includePayrollOnly ?? false;
    $payrollOnlyCount   = $payrollOnlyCount ?? 0;
@endphp

@if($payrollOnlyCount > 0)
<div class="bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded p-3 mb-4 flex flex-wrap items-center justify-between gap-2 no-print">

    @if($includePayrollOnly)
        <span>
            Showing {{ $payrollOnlyCount }} payroll-only employee(s), who are paid
            but never mark attendance.
        </span>
        <a href="{{ request()->fullUrlWithQuery(['include_payroll_only' => null]) }}"
           class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1 rounded">
            Hide them
        </a>
    @else
        <span>
            {{ $payrollOnlyCount }} payroll-only employee(s) are not counted here.
            They are paid each month but never mark attendance.
        </span>
        <a href="{{ request()->fullUrlWithQuery(['include_payroll_only' => 1]) }}"
           class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1 rounded">
            Show them
        </a>
    @endif

</div>
@endif
