<x-app-layout>

@php
    $sort           = $sort ?? 'code';
    $dir            = $dir ?? 'asc';
    $medicalDivisor = $medicalDivisor ?: 1.1;
    $orgName        = \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)');

    $sortLink = function ($key) use ($year, $category, $sourceMonth, $sort, $dir) {
        $next = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
        return route('admin.salary.tax.sheet', [
            'year' => $year, 'category' => $category, 'source_month' => $sourceMonth,
            'sort' => $key,  'dir' => $next,
        ]);
    };
    $arrow = fn ($key) => $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
@endphp

<div class="max-w-full mx-auto py-6 px-4 print-area">

    {{-- ================= TOOLBAR ================= --}}
    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tax Sheet</h2>
            <p class="text-sm text-gray-500 mt-1">
                Yearly working that produces each employee's monthly tax deduction.
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.salary.tax') }}"
               class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded text-sm">
                Tax Rules
            </a>
            <a href="{{ route('admin.salary.sheet', ['year' => $year]) }}"
               class="bg-gray-700 text-white px-3 py-2 rounded text-sm">
                Salary Sheet
            </a>
            <button type="button" onclick="window.print()"
                    class="bg-blue-600 text-white px-3 py-2 rounded text-sm">
                Print
            </button>
        </div>

    </div>

    @if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4 no-print">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 no-print">{{ session('error') }}</div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 no-print">{{ $errors->first() }}</div>
    @endif

    @if($taxSlabs->isEmpty())
    <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded mb-4 no-print">
        No tax slabs are configured, so every payable tax below will be zero.
        Set them up under <a href="{{ route('admin.salary.tax') }}" class="underline font-semibold">Tax Rules</a>.
    </div>
    @endif

    {{-- ================= FILTERS ================= --}}
    <form method="GET" class="flex gap-3 mb-4 items-end flex-wrap bg-white p-4 rounded shadow no-print">

        <div>
            <label class="block text-xs text-gray-500 mb-1">Tax year</label>
            <input type="number" name="year" value="{{ $year }}"
                   class="border px-3 py-2 rounded text-sm w-28">
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Sheet</label>
            <select name="category" class="border px-3 py-2 rounded text-sm">
                <option value="all" {{ $category == 'all' ? 'selected' : '' }}>All</option>
                <option value="teacher" {{ $category == 'teacher' ? 'selected' : '' }}>Teachers</option>
                <option value="staff" {{ $category == 'staff' ? 'selected' : '' }}>Staff</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Seed yearly figure from</label>
            <select name="source_month" class="border px-3 py-2 rounded text-sm">
                @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $sourceMonth == $m ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($m)->format('F') }} &times; 12
                </option>
                @endfor
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Sort by</label>
            <select name="sort" class="border px-3 py-2 rounded text-sm">
                <option value="code" {{ $sort === 'code' ? 'selected' : '' }}>Employee code</option>
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name</option>
            </select>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Load</button>

    </form>

    @if($rows->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
        No employees to show for this selection.
    </div>

    @else

    {{-- ================= ACTIONS ================= --}}
    <div class="flex gap-2 mb-4 flex-wrap items-center no-print">

        <form method="POST" action="{{ route('admin.salary.tax.sheet.apply') }}"
              onsubmit="return confirm('Write the monthly tax below onto the selected month\'s salary sheet? Posted rows are left alone.');"
              class="flex gap-2 items-end">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Apply monthly tax to</label>
                <select name="month" class="border px-3 py-2 rounded text-sm">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $sourceMonth == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->format('F') }} {{ $year }}
                    </option>
                    @endfor
                </select>
            </div>
            <button class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded text-sm">
                Apply to Salary Sheet
            </button>
        </form>

        <span class="text-xs text-gray-500 max-w-md">
            Save first &mdash; Apply uses the saved figures, not what is on screen.
        </span>

    </div>

    {{-- ================= PRINT LETTERHEAD ================= --}}
    <div class="sheet-header text-center mb-3">
        <div class="font-bold text-lg">{{ $orgName }}</div>
        <div class="text-sm">Tax Sheet {{ $year }}</div>
    </div>

    <form method="POST" action="{{ route('admin.salary.tax.sheet.store') }}">
    @csrf
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="category" value="{{ $category }}">
    <input type="hidden" name="source_month" value="{{ $sourceMonth }}">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">

    <div class="overflow-x-auto bg-white rounded shadow">

    <table class="min-w-full text-xs border" id="taxSheet">

        <thead>
            <tr class="bg-gray-300">
                <th class="border p-1" colspan="9"></th>
                <th class="border p-1 text-center" colspan="14">Tax Deducted (posted salaries)</th>
            </tr>
            <tr class="bg-gray-200">
                <th class="border p-2 text-left">
                    <a href="{{ $sortLink('code') }}" class="hover:underline no-print-link">Employee ID{{ $arrow('code') }}</a>
                </th>
                <th class="border p-2 text-left">
                    <a href="{{ $sortLink('name') }}" class="hover:underline no-print-link">Employee Name{{ $arrow('name') }}</a>
                </th>
                <th class="border p-2 bg-green-50">Salary &amp; Wages<br><span class="font-normal text-[10px]">yearly</span></th>
                <th class="border p-2 bg-green-50 w-28">Additional<br>Income<br><span class="font-normal text-[10px]">yearly</span></th>
                <th class="border p-2 bg-blue-50">Taxable Income<br><span class="font-normal text-[10px]">&divide; {{ $medicalDivisor }} (less medical)</span></th>
                <th class="border p-2 bg-amber-50">Payable Tax<br><span class="font-normal text-[10px]">yearly</span></th>
                <th class="border p-2 bg-yellow-50 w-24">Tax<br>Adjustment</th>
                <th class="border p-2 bg-amber-100 font-bold">Net Payable Tax</th>
                <th class="border p-2 bg-red-100 font-bold">Monthly Tax<br><span class="font-normal text-[10px]">&divide; 12</span></th>
                @for($m = 1; $m <= 12; $m++)
                <th class="border p-1 bg-slate-50 text-[10px]">
                    {{ \Carbon\Carbon::create()->month($m)->format('M') }}
                </th>
                @endfor
                <th class="border p-2 bg-slate-100 font-bold">Tax Paid</th>
                <th class="border p-2 bg-slate-100 font-bold">Balance</th>
            </tr>
        </thead>

        <tbody>

        @foreach($rows as $i => $row)
        <tr class="tax-row">

            <td class="border p-1">
                {{ $row['user']->employee_code ?? '-' }}
                <input type="hidden" name="rows[{{ $i }}][user_id]" value="{{ $row['user']->id }}">
            </td>

            <td class="border p-1 whitespace-nowrap">{{ $row['user']->name }}</td>

            <td class="border p-0">
                <input type="number" step="0.01" min="0"
                       name="rows[{{ $i }}][annual_salary]"
                       value="{{ $row['annual'] != 0 ? $row['annual'] : '' }}"
                       class="annual w-32 p-1 text-right border-0">
            </td>

            <td class="border p-0">
                <input type="number" step="0.01" min="0"
                       name="rows[{{ $i }}][additional_income]"
                       value="{{ $row['additional'] != 0 ? $row['additional'] : '' }}"
                       class="additional w-24 p-1 text-right border-0 bg-green-50"
                       title="Extra taxable income for the year">
            </td>

            <td class="border p-1 text-right taxable bg-blue-50">0</td>

            <td class="border p-1 text-right payable bg-amber-50">0</td>

            <td class="border p-0">
                <input type="number" step="0.01"
                       name="rows[{{ $i }}][tax_adjustment]"
                       value="{{ $row['adjustment'] != 0 ? $row['adjustment'] : '' }}"
                       class="adjustment w-20 p-1 text-right border-0 bg-yellow-50">
            </td>

            <td class="border p-1 text-right net font-bold bg-amber-50">0</td>

            <td class="border p-1 text-right monthly font-bold bg-red-50">0</td>

            @for($m = 1; $m <= 12; $m++)
            @php $paidThisMonth = $row['paid_by_month'][$m] ?? null; @endphp
            <td class="border p-1 text-right text-[10px] bg-slate-50">
                {{ $paidThisMonth ? number_format($paidThisMonth) : '' }}
            </td>
            @endfor

            <td class="border p-1 text-right font-bold bg-slate-100">
                {{ $row['paid'] ? number_format($row['paid']) : '' }}
            </td>

            <td class="border p-1 text-right font-bold bg-slate-100 {{ $row['balance'] < 0 ? 'text-red-600' : '' }}">
                {{ $row['balance'] != 0 ? number_format($row['balance']) : '' }}
            </td>

        </tr>
        @endforeach

        </tbody>

        <tfoot class="bg-gray-100 font-bold">
            <tr>
                <td class="border p-2 text-right" colspan="2">TOTAL</td>
                <td class="border p-2 text-right" id="sumAnnual">0</td>
                <td class="border p-2 text-right" id="sumAdditional">0</td>
                <td class="border p-2 text-right" id="sumTaxable">0</td>
                <td class="border p-2 text-right" id="sumPayable">0</td>
                <td class="border p-2 text-right" id="sumAdjust">0</td>
                <td class="border p-2 text-right" id="sumNet">0</td>
                <td class="border p-2 text-right" id="sumMonthly">0</td>
                @for($m = 1; $m <= 12; $m++)
                @php $colTotal = $rows->sum(fn($r) => $r['paid_by_month'][$m] ?? 0); @endphp
                <td class="border p-1 text-right text-[10px]">
                    {{ $colTotal ? number_format($colTotal) : '' }}
                </td>
                @endfor
                <td class="border p-2 text-right">{{ number_format($rows->sum('paid')) }}</td>
                <td class="border p-2 text-right">{{ number_format($rows->sum('balance')) }}</td>
            </tr>
        </tfoot>

    </table>

    </div>

    <div class="mt-5 no-print">
        <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Save Tax Sheet
        </button>
        <span class="text-xs text-gray-500 ml-3">
            Only Salary &amp; Wages and Tax Adjustment are stored; the rest is calculated.
        </span>
    </div>

    </form>

    @endif

</div>

<style>
    .sheet-header { display: none; }

@media print {
    .sheet-header { display: block !important; }
    @page { size: A4 landscape; margin: 6mm; }
    #taxSheet { font-size: 6.5pt !important; }
    #taxSheet th, #taxSheet td { padding: 1px !important; }
    #taxSheet input { width: auto !important; font-size: 8pt !important; text-align: right; }
    .no-print-link { text-decoration: none !important; color: #000 !important; }
}
</style>

<script>
(function () {

    const SLABS   = @json($taxSlabs);
    const BASIS   = @json($taxBasis);
    const DIVISOR = {{ $medicalDivisor ?: 1.1 }};

    const fmt = n => (Math.round(n * 100) / 100)
        .toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });

    const num = el => {
        const v = parseFloat(el.value);
        return isNaN(v) ? 0 : v;
    };

    /* Tax on an amount expressed in the same basis as the slabs */
    function taxFor(income) {
        if (income <= 0) return 0;
        for (const s of SLABS) {
            const from = parseFloat(s.from_amount);
            const to   = s.to_amount === null ? null : parseFloat(s.to_amount);
            if (income > from && (to === null || income <= to)) {
                return Math.max(0, parseFloat(s.fixed_amount)
                    + ((income - from) * parseFloat(s.percentage) / 100));
            }
        }
        return 0;
    }

    /* Tax on a full year of income */
    function annualTax(annual) {
        if (annual <= 0) return 0;
        return BASIS === 'monthly' ? taxFor(annual / 12) * 12 : taxFor(annual);
    }

    function recalc() {

        let tAnnual = 0, tAdditional = 0, tTaxable = 0, tPayable = 0, tAdjust = 0, tNet = 0, tMonthly = 0;

        document.querySelectorAll('.tax-row').forEach(row => {

            const annual = num(row.querySelector('.annual'));

            // Additional income is a yearly figure carrying no medical
            // component, so it is taxed in full.
            const additional = num(row.querySelector('.additional'));

            const taxable = (DIVISOR > 0 ? annual / DIVISOR : annual) + additional;
            const payable = annualTax(taxable);
            const adjust  = num(row.querySelector('.adjustment'));
            const net = payable - adjust;

            // Deducted as whole rupees, matching what is written to the sheet.
            const monthly = Math.round(Math.max(0, net) / 12);

            row.querySelector('.taxable').textContent = fmt(taxable);
            row.querySelector('.payable').textContent = fmt(payable);
            const netCell = row.querySelector('.net');
            const monCell = row.querySelector('.monthly');

            netCell.textContent = fmt(net);
            monCell.textContent = fmt(monthly);

            // An adjustment bigger than the tax due is worth flagging: it is
            // written to the salary sheet as zero, not as a refund.
            const over = net < 0;
            netCell.classList.toggle('text-red-600', over);
            monCell.classList.toggle('text-red-600', over);
            monCell.title = over ? 'Adjustment exceeds the tax due; applied as 0' : '';

            tAnnual     += annual;
            tAdditional += additional;
            tTaxable += taxable;
            tPayable += payable;
            tAdjust  += adjust;
            tNet     += net;
            tMonthly += monthly;
        });

        const put = (id, v) => document.getElementById(id).textContent = fmt(v);

        put('sumAnnual',     tAnnual);
        put('sumAdditional', tAdditional);
        put('sumTaxable', tTaxable);
        put('sumPayable', tPayable);
        put('sumAdjust',  tAdjust);
        put('sumNet',     tNet);
        put('sumMonthly', tMonthly);
    }

    document.querySelectorAll('#taxSheet input[type=number]')
        .forEach(el => el.addEventListener('input', recalc));

    recalc();
})();
</script>

</x-app-layout>
