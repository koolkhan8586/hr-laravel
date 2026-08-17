<x-app-layout>

@php
    $sort     = $sort ?? 'code';
    $dir      = $dir ?? 'asc';
    $existing = $existing ?? collect();
    $deducted = $deducted ?? [];
    $postedIds = $postedIds ?? [];

    $monthName  = \Carbon\Carbon::create()->month($month)->format('F');
    $sheetLabel = match($category) { 'teacher' => 'Teachers', 'all' => 'All Employees', default => 'Staff' };
    $orgName    = \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)');

    $sortLink = function ($key) use ($month, $year, $category, $sort, $dir) {
        $next = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
        return route('admin.salary.medical', [
            'month' => $month, 'year' => $year, 'category' => $category,
            'sort'  => $key,   'dir'  => $next,
        ]);
    };
    $arrow = fn ($key) => $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';

    $money = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }

        $n = (float) $value;

        return number_format($n, fmod($n, 1) == 0.0 ? 0 : 2);
    };
@endphp

<div class="max-w-full mx-auto py-6 px-4 print-area">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Medical Insurance</h2>
            <p class="text-sm text-gray-500 mt-1">
                Enter the total premium. LSAF and the employee each pay half.
                The employee half appears as a grey hint on the salary sheet until you type it there.
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.salary.sheet', ['month' => $month, 'year' => $year, 'category' => $category]) }}"
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

    <form method="GET" class="flex gap-3 mb-4 items-end flex-wrap bg-white p-4 rounded shadow no-print">

        <div>
            <label class="block text-xs text-gray-500 mb-1">Month</label>
            <select name="month" class="border px-3 py-2 rounded text-sm">
                @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                </option>
                @endfor
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}"
                   class="border px-3 py-2 rounded text-sm w-28">
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Sheet</label>
            <select name="category" class="border px-3 py-2 rounded text-sm">
                <option value="teacher" {{ $category == 'teacher' ? 'selected' : '' }}>Teachers</option>
                <option value="staff" {{ $category == 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="all" {{ $category == 'all' ? 'selected' : '' }}>All</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Sort by</label>
            <select name="sort" class="border px-3 py-2 rounded text-sm">
                <option value="code" {{ $sort === 'code' ? 'selected' : '' }}>Employee code</option>
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name</option>
                <option value="doj" {{ $sort === 'doj' ? 'selected' : '' }}>Joining date</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Order</label>
            <select name="dir" class="border px-3 py-2 rounded text-sm">
                <option value="asc" {{ $dir === 'asc' ? 'selected' : '' }}>Ascending</option>
                <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Descending</option>
            </select>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Load Sheet</button>

    </form>

    @if($users->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
        No employees found for <strong>{{ $sheetLabel }}</strong>.
    </div>

    @else

    <div class="flex gap-2 mb-4 flex-wrap items-center no-print">

        <form method="POST" action="{{ route('admin.salary.medical.copy') }}"
              onsubmit="return confirm('Copy last month\'s premiums into {{ $monthName }} {{ $year }}?');">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="category" value="{{ $category }}">
            <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                Copy Last Month
            </button>
        </form>

        <span class="text-xs text-gray-500 max-w-xl">
            Deducted fills in automatically when that month's salary sheet is posted,
            so you can see how much was actually taken from pay.
        </span>

    </div>

    <div class="sheet-header text-center mb-3">
        <div class="flex items-center justify-center gap-3">
            <img src="{{ asset('uol-logo.png') }}" alt="" style="height:46px" onerror="this.style.display='none'">
            <div>
                <div class="font-bold text-lg">{{ $orgName }}</div>
                <div class="text-sm">Medical Insurance {{ $monthName }} {{ $year }} &mdash; {{ $sheetLabel }}</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.salary.medical.store') }}">
    @csrf

    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="category" value="{{ $category }}">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">

    <div class="overflow-x-auto bg-white rounded shadow">

    <table class="min-w-full text-xs border" id="medicalSheet">

        <thead>
            <tr class="bg-gray-200">
                <th class="border p-2">Sr.</th>
                <th class="border p-2 text-left">
                    <a href="{{ $sortLink('code') }}" class="hover:underline no-print-link">Employee Code{{ $arrow('code') }}</a>
                </th>
                <th class="border p-2 text-left">
                    <a href="{{ $sortLink('name') }}" class="hover:underline no-print-link">Employee Name{{ $arrow('name') }}</a>
                </th>
                <th class="border p-2 bg-blue-50">Total Amount</th>
                <th class="border p-2 bg-green-50">LSAF Portion</th>
                <th class="border p-2 bg-amber-50">Employee Portion</th>
                <th class="border p-2 bg-slate-100">Deducted<br><span class="font-normal text-[10px]">from posted salary</span></th>
            </tr>
        </thead>

        <tbody>

        @foreach($users as $i => $user)
        @php
            $row      = $existing[$user->id] ?? null;
            $taken    = (float) ($deducted[$user->id] ?? 0);
            $isPosted = in_array($user->id, $postedIds);
        @endphp

        <tr class="medical-row">

            <td class="border p-1 text-center sr-cell"></td>

            <td class="border p-1">
                {{ $user->employee_code ?? '-' }}
                <input type="hidden" name="rows[{{ $i }}][user_id]" value="{{ $user->id }}">
            </td>

            <td class="border p-1 whitespace-nowrap">
                {{ $user->name }}
                @if($isPosted)
                <span class="text-[10px] text-white bg-gray-500 px-1 rounded no-print">POSTED</span>
                @endif
            </td>

            <td class="border p-0">
                <input type="text" inputmode="decimal" class="total-amount w-28 p-1 text-right border-0"
                       name="rows[{{ $i }}][total_amount]"
                       value="{{ $money($row && $row->total_amount != 0 ? $row->total_amount : null) }}">
            </td>

            <td class="border p-1 text-right bg-green-50 lsaf-portion">
                {{ $money($row && $row->lsaf_portion != 0 ? $row->lsaf_portion : null) }}
            </td>

            <td class="border p-1 text-right bg-amber-50 employee-portion">
                {{ $money($row && $row->employee_portion != 0 ? $row->employee_portion : null) }}
            </td>

            <td class="border p-1 text-right bg-slate-50 deducted {{ $taken > 0 ? 'font-semibold' : '' }}">
                {{ $taken > 0 ? $money($taken) : '' }}
            </td>

        </tr>
        @endforeach

        </tbody>

        <tfoot class="bg-gray-100 font-bold">
            <tr>
                <td class="border p-2 text-right" colspan="3">TOTAL</td>
                <td class="border p-2 text-right" id="sumTotal">0</td>
                <td class="border p-2 text-right bg-green-100" id="sumLsaf">0</td>
                <td class="border p-2 text-right bg-amber-100" id="sumEmployee">0</td>
                <td class="border p-2 text-right bg-slate-100" id="sumDeducted">0</td>
            </tr>
        </tfoot>

    </table>

    </div>

    <div class="sign-off justify-between mt-10 text-sm">
        <div>Prepared By: _______________</div>
        <div>Checked By: _______________</div>
        <div>Approved By: _______________</div>
    </div>

    <div class="mt-5 no-print">
        <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Save Medical Insurance
        </button>
        <span class="text-xs text-gray-500 ml-3">
            Total is split in half on save. Deducted comes from posted salaries, not from this form.
        </span>
    </div>

    </form>

    @endif

</div>

<style>
    .sheet-header { display: none; }
    .sign-off { display: none; }

    #medicalSheet tbody { counter-reset: medicalRow; }
    #medicalSheet tbody tr.medical-row { counter-increment: medicalRow; }
    #medicalSheet tbody tr.medical-row .sr-cell::before { content: counter(medicalRow); }

@media print {
    .sheet-header { display: block !important; }
    .sign-off { display: flex !important; }

    @page { size: A4 landscape; margin: 8mm; }

    #medicalSheet { font-size: 8px !important; }
    #medicalSheet th, #medicalSheet td { padding: 2px !important; }
    #medicalSheet input { width: auto !important; font-size: 8px !important; text-align: right; }

    .no-print-link { text-decoration: none !important; color: #000 !important; }

    #medicalSheet tbody tr.row-empty { display: none !important; }
}
</style>

<script>
(function () {

    const fmt = n => (Math.round(n * 100) / 100)
        .toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });

    const num = el => {
        const v = parseFloat(String(el.value).replace(/,/g, ''));
        return isNaN(v) ? 0 : v;
    };

    const money = value => {
        const s = String(value).trim();
        if (s === '') return '';

        const n = parseFloat(s.replace(/,/g, ''));
        if (isNaN(n)) return '';

        return n.toLocaleString('en-US', {
            minimumFractionDigits: Number.isInteger(n) ? 0 : 2,
            maximumFractionDigits: 2,
        });
    };

    const put = (cell, v) => {
        cell.textContent = v === 0 ? '' : fmt(v);
    };

    function split(total) {
        const employee = Math.round((total / 2) * 100) / 100;
        const lsaf     = Math.round((total - employee) * 100) / 100;
        return { lsaf, employee };
    }

    function recalc() {

        let tTotal = 0, tLsaf = 0, tEmp = 0, tDed = 0;

        document.querySelectorAll('.medical-row').forEach(row => {

            const total = num(row.querySelector('.total-amount'));
            const parts = split(total);

            put(row.querySelector('.lsaf-portion'), parts.lsaf);
            put(row.querySelector('.employee-portion'), parts.employee);

            const deductedText = row.querySelector('.deducted').textContent.replace(/,/g, '');
            const deducted = parseFloat(deductedText);
            const taken = isNaN(deducted) ? 0 : deducted;

            tTotal += total;
            tLsaf  += parts.lsaf;
            tEmp   += parts.employee;
            tDed   += taken;

            row.classList.toggle('row-empty', total === 0 && taken === 0);
        });

        document.getElementById('sumTotal').textContent    = fmt(tTotal);
        document.getElementById('sumLsaf').textContent     = fmt(tLsaf);
        document.getElementById('sumEmployee').textContent = fmt(tEmp);
        document.getElementById('sumDeducted').textContent = fmt(tDed);
    }

    document.querySelectorAll('#medicalSheet input.total-amount').forEach(el => {
        el.addEventListener('input', recalc);
        el.addEventListener('focus', () => { el.value = String(el.value).replace(/,/g, ''); el.select?.(); });
        el.addEventListener('blur', () => { el.value = money(el.value); recalc(); });
    });

    const form = document.getElementById('medicalSheet')?.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            document.querySelectorAll('#medicalSheet input.total-amount').forEach(el => {
                el.value = String(el.value).replace(/,/g, '');
            });
        });
    }

    document.querySelectorAll('#medicalSheet input.total-amount').forEach(el => {
        el.value = money(el.value);
    });

    recalc();
})();
</script>

</x-app-layout>
