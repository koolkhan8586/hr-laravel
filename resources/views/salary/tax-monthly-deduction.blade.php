<x-app-layout>

@php
    $orgName = \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)');
    $period  = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
    $money   = fn ($n) => ((float) $n) != 0
        ? number_format((float) $n, 2)
        : '';
    $divisor = $medicalDivisor ?: 1.1;
@endphp

<div class="max-w-full mx-auto py-6 px-4 print-area">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tax Deducted — {{ $period }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                Same columns as the Tax Sheet. Monthly Tax is the amount deducted in {{ $period }}.
                Employees with 0 tax are hidden.
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.salary.tax.sheet', ['year' => $year, 'category' => $category]) }}"
               class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded text-sm">
                Back to Tax Sheet
            </a>
            <a href="{{ route('admin.salary.tax.sheet.monthly.pdf', [
                    'year' => $year,
                    'month' => $month,
                    'category' => $category,
                    'sort' => $sort,
                    'dir' => $dir,
                ]) }}"
               class="bg-green-700 hover:bg-green-800 text-white px-3 py-2 rounded text-sm">
                Download PDF
            </a>
            <button type="button" onclick="window.print()"
                    class="bg-blue-600 text-white px-3 py-2 rounded text-sm">
                Print
            </button>
        </div>
    </div>

    <div class="sheet-header text-center mb-4">
        <div class="font-bold text-lg">{{ $orgName }}</div>
        <div class="text-sm">Income Tax Deducted — {{ $period }}</div>
        @if($category !== 'all')
            <div class="text-xs text-gray-600 capitalize">{{ $category }} sheet</div>
        @endif
    </div>

    @if($rows->isEmpty())
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
            No employees with tax deducted for {{ $period }}.
            Only posted salaries with income tax greater than zero are listed.
        </div>
    @else

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full text-xs border" id="monthlyTaxTable">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border p-2 text-left">Employee ID</th>
                    <th class="border p-2 text-left">Employee Name</th>
                    <th class="border p-2 text-left">CNIC</th>
                    <th class="border p-2 bg-green-50">Salary &amp; Wages<br><span class="font-normal text-[10px]">yearly</span></th>
                    <th class="border p-2 bg-green-50">Additional<br>Income<br><span class="font-normal text-[10px]">yearly</span></th>
                    <th class="border p-2 bg-blue-50">Taxable Income<br><span class="font-normal text-[10px]">&divide; {{ $divisor }} (less medical)</span></th>
                    <th class="border p-2 bg-amber-50">Payable Tax<br><span class="font-normal text-[10px]">yearly</span></th>
                    <th class="border p-2 bg-yellow-50">Tax<br>Adjustment</th>
                    <th class="border p-2 bg-amber-100 font-bold">Net Payable Tax</th>
                    <th class="border p-2 bg-red-100 font-bold">Monthly Tax<br><span class="font-normal text-[10px]">{{ $period }}</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td class="border p-1">{{ $row->user->employee_code ?? '-' }}</td>
                    <td class="border p-1 whitespace-nowrap">{{ $row->user->name }}</td>
                    <td class="border p-1 whitespace-nowrap">{{ $row->user->cnic ?: '—' }}</td>
                    <td class="border p-1 text-right bg-green-50">{{ $money($row->annual) }}</td>
                    <td class="border p-1 text-right bg-green-50">{{ $money($row->additional) }}</td>
                    <td class="border p-1 text-right bg-blue-50">{{ $money($row->taxable) }}</td>
                    <td class="border p-1 text-right bg-amber-50">{{ $money($row->payable) }}</td>
                    <td class="border p-1 text-right bg-yellow-50">{{ $money($row->adjustment) }}</td>
                    <td class="border p-1 text-right font-semibold bg-amber-50">{{ $money($row->net) }}</td>
                    <td class="border p-1 text-right font-bold bg-red-50">{{ number_format($row->monthly) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-100 font-bold">
                <tr>
                    <td class="border p-2 text-right" colspan="3">TOTAL</td>
                    <td class="border p-2 text-right">{{ $money($rows->sum('annual')) }}</td>
                    <td class="border p-2 text-right">{{ $money($rows->sum('additional')) }}</td>
                    <td class="border p-2 text-right">{{ $money($rows->sum('taxable')) }}</td>
                    <td class="border p-2 text-right">{{ $money($rows->sum('payable')) }}</td>
                    <td class="border p-2 text-right">{{ $money($rows->sum('adjustment')) }}</td>
                    <td class="border p-2 text-right">{{ $money($rows->sum('net')) }}</td>
                    <td class="border p-2 text-right">{{ number_format($total) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="text-xs text-gray-500 mt-3 no-print">
        {{ $rows->count() }} employee(s) with tax deducted · Total for {{ $period }}: Rs {{ number_format($total) }}
    </p>

    @endif

</div>

<style>
    .sheet-header { display: none; }

    @media print {
        .sheet-header { display: block !important; }
        @page { size: A4 landscape; margin: 8mm; }
        .no-print { display: none !important; }
        #monthlyTaxTable { font-size: 8pt !important; }
        #monthlyTaxTable th, #monthlyTaxTable td { padding: 2px 4px !important; }
    }
</style>

</x-app-layout>
