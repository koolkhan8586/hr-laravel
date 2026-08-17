<x-app-layout>

@php
    $orgName = \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)');
    $period  = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
    $money   = fn ($n) => number_format((float) $n, 2);
@endphp

<div class="max-w-5xl mx-auto py-6 px-4 print-area">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tax Deducted — {{ $period }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                Income tax deducted from posted salaries for this month.
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.salary.tax.sheet', ['year' => $year, 'category' => $category]) }}"
               class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded text-sm">
                Back to Tax Sheet
            </a>
            <a href="{{ route('admin.salary.tax.sheet.monthly.pdf', ['year' => $year, 'month' => $month, 'category' => $category, 'sort' => $sort, 'dir' => $dir]) }}"
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
            No posted salary tax deductions found for {{ $period }}.
            Post salaries for this month first, or check the category filter.
        </div>
    @else

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full text-sm border" id="monthlyTaxTable">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border p-2 text-left w-12">#</th>
                    <th class="border p-2 text-left">Employee ID</th>
                    <th class="border p-2 text-left">Employee Name</th>
                    <th class="border p-2 text-left">Category</th>
                    <th class="border p-2 text-right">Income Tax Deducted</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td class="border p-2">{{ $i + 1 }}</td>
                    <td class="border p-2">{{ $row->user->employee_code ?? '-' }}</td>
                    <td class="border p-2">{{ $row->user->name }}</td>
                    <td class="border p-2 capitalize">{{ $row->user->salary_category ?? '-' }}</td>
                    <td class="border p-2 text-right font-semibold">{{ $money($row->income_tax) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-100 font-bold">
                <tr>
                    <td class="border p-2 text-right" colspan="4">TOTAL</td>
                    <td class="border p-2 text-right">{{ $money($total) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="text-xs text-gray-500 mt-3 no-print">
        {{ $rows->count() }} employee(s) · Total deducted: Rs {{ $money($total) }}
    </p>

    @endif

</div>

<style>
    .sheet-header { display: none; }

    @media print {
        .sheet-header { display: block !important; }
        @page { size: A4 portrait; margin: 12mm; }
        .no-print { display: none !important; }
        #monthlyTaxTable { font-size: 10pt; }
        #monthlyTaxTable th, #monthlyTaxTable td { padding: 4px 6px; }
    }
</style>

</x-app-layout>
