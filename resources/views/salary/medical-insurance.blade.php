<x-app-layout>

@php
    $sort     = $sort ?? 'code';
    $dir      = $dir ?? 'asc';
    $orgName  = \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)');
    $sheetLabel = match($category) { 'teacher' => 'Teachers', 'staff' => 'Staff', default => 'All Employees' };

    $sortLink = function ($key) use ($year, $category, $sort, $dir) {
        $next = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
        return route('admin.salary.medical', [
            'year' => $year, 'category' => $category,
            'sort' => $key,  'dir' => $next,
        ]);
    };
    $arrow = fn ($key) => $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    $months = $months ?? \App\Models\MedicalInsurance::monthsForYear((int) $year);
@endphp

<div class="max-w-full mx-auto py-6 px-4 print-area">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Medical Insurance</h2>
            <p class="text-sm text-gray-500 mt-1">
                Yearly working from August 2026 onward. Enter the total; LSAF and the
                employee each take half, then the employee half is divided by 12 for
                the salary sheet. In Total Amount you can type a figure or a sum like
                <code class="bg-gray-100 px-1">+2600+1000</code> or <code class="bg-gray-100 px-1">=2600+1000</code>.
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.salary.sheet', ['year' => $year, 'category' => $category]) }}"
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
            <label class="block text-xs text-gray-500 mb-1">Year</label>
            <input type="number" name="year" value="{{ $year }}" min="2026"
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

        <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Load</button>

    </form>

    @if($rows->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
        No employees to show for this selection.
    </div>

    @else

    <div class="flex gap-2 mb-4 flex-wrap items-center no-print">

        <form method="POST" action="{{ route('admin.salary.medical.copy') }}"
              onsubmit="return confirm('Copy last year\'s premiums into {{ $year }}?');">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="category" value="{{ $category }}">
            <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                Copy Previous Year
            </button>
        </form>

        <span class="text-xs text-gray-500 max-w-xl">
            Save stores every employee on this sheet for the year.
            2026 month columns start at August; later years are January–December.
            Month columns fill from posted salary Insurance figures.
        </span>

    </div>

    <div class="sheet-header text-center mb-3">
        <div class="font-bold text-lg">{{ $orgName }}</div>
        <div class="text-sm">Medical Insurance {{ $year }} &mdash; {{ $sheetLabel }}</div>
    </div>

    <form method="POST" action="{{ route('admin.salary.medical.store') }}">
    @csrf
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="category" value="{{ $category }}">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">

    <div class="overflow-x-auto bg-white rounded shadow">

    <table class="min-w-full text-xs border" id="medicalSheet">

        <thead>
            <tr class="bg-gray-300">
                <th class="border p-1" colspan="6"></th>
                <th class="border p-1 text-center" colspan="{{ count($months) + 2 }}">Deducted (posted salaries)</th>
            </tr>
            <tr class="bg-gray-200">
                <th class="border p-2 text-left">
                    <a href="{{ $sortLink('code') }}" class="hover:underline no-print-link">Employee Code{{ $arrow('code') }}</a>
                </th>
                <th class="border p-2 text-left">
                    <a href="{{ $sortLink('name') }}" class="hover:underline no-print-link">Employee Name{{ $arrow('name') }}</a>
                </th>
                <th class="border p-2 bg-blue-50">Total Amount<br><span class="font-normal text-[10px]">yearly</span></th>
                <th class="border p-2 bg-green-50">LSAF Portion<br><span class="font-normal text-[10px]">&divide; 2</span></th>
                <th class="border p-2 bg-amber-50">Employee Portion<br><span class="font-normal text-[10px]">&divide; 2</span></th>
                <th class="border p-2 bg-red-100 font-bold">Monthly<br><span class="font-normal text-[10px]">employee &divide; 12</span></th>
                @foreach($months as $m)
                <th class="border p-1 bg-slate-50 text-[10px]">
                    {{ \Carbon\Carbon::create()->month($m)->format('M') }}
                </th>
                @endforeach
                <th class="border p-2 bg-slate-100 font-bold">Deducted</th>
                <th class="border p-2 bg-slate-100 font-bold">Balance<br><span class="font-normal text-[10px]">employee &minus; deducted</span></th>
            </tr>
        </thead>

        <tbody>

        @foreach($rows as $i => $row)
        <tr class="medical-row" data-paid="{{ $row['paid'] }}">

            <td class="border p-1">
                {{ $row['user']->employee_code ?? '-' }}
                <input type="hidden" name="rows[{{ $i }}][user_id]" value="{{ $row['user']->id }}">
            </td>

            <td class="border p-1 whitespace-nowrap">{{ $row['user']->name }}</td>

            <td class="border p-0">
                <input type="text" inputmode="decimal"
                       name="rows[{{ $i }}][total_amount]"
                       value="{{ $row['total'] != 0 ? number_format($row['total'], fmod($row['total'], 1) == 0.0 ? 0 : 2) : '' }}"
                       data-formula="{{ $row['total_formula'] ?? '' }}"
                       class="total-amount w-28 p-1 text-right border-0{{ filled($row['total_formula'] ?? '') ? ' has-formula' : '' }}"
                       @if(filled($row['total_formula'] ?? '')) title="{{ $row['total_formula'] }}" @endif>
            </td>

            <td class="border p-1 text-right bg-green-50 lsaf-portion"></td>
            <td class="border p-1 text-right bg-amber-50 employee-portion"></td>
            <td class="border p-1 text-right font-bold bg-red-50 monthly-portion"></td>

            @foreach($months as $m)
            @php $paidThisMonth = $row['paid_by_month'][$m] ?? null; @endphp
            <td class="border p-1 text-right text-[10px] bg-slate-50 month-paid" data-month="{{ $m }}">
                {{ $paidThisMonth ? number_format($paidThisMonth) : '' }}
            </td>
            @endforeach

            <td class="border p-1 text-right font-bold bg-slate-100 deducted">
                {{ $row['paid'] ? number_format($row['paid']) : '' }}
            </td>

            <td class="border p-1 text-right bg-slate-100 balance"></td>

        </tr>
        @endforeach

        </tbody>

        <tfoot class="bg-gray-100 font-bold">
            <tr>
                <td class="border p-2 text-right" colspan="2">TOTAL</td>
                <td class="border p-2 text-right" id="sumTotal">0</td>
                <td class="border p-2 text-right bg-green-100" id="sumLsaf">0</td>
                <td class="border p-2 text-right bg-amber-100" id="sumEmployee">0</td>
                <td class="border p-2 text-right bg-red-100" id="sumMonthly">0</td>
                @foreach($months as $m)
                @php $colTotal = $rows->sum(fn ($r) => $r['paid_by_month'][$m] ?? 0); @endphp
                <td class="border p-1 text-right text-[10px]">
                    {{ $colTotal ? number_format($colTotal) : '' }}
                </td>
                @endforeach
                <td class="border p-2 text-right" id="sumDeducted">{{ number_format($rows->sum('paid')) }}</td>
                <td class="border p-2 text-right" id="sumBalance">0</td>
            </tr>
        </tfoot>

    </table>

    </div>

    <div class="mt-5 no-print">
        <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Save Medical Insurance
        </button>
        <span class="text-xs text-gray-500 ml-3">
            Every employee on this sheet is stored for the year. Monthly is the employee half &divide; 12
            and shows grey on the salary sheet until you type it.
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
    #medicalSheet { font-size: 6.5pt !important; }
    #medicalSheet th, #medicalSheet td { padding: 1px !important; }
    #medicalSheet input { width: auto !important; font-size: 8pt !important; text-align: right; }
    .no-print-link { text-decoration: none !important; color: #000 !important; }
    #medicalSheet tbody tr.row-empty { display: none !important; }
    #medicalSheet input.total-amount.has-formula {
        background-image: linear-gradient(135deg, transparent 92%, #2563eb 92%);
    }
    #medicalSheet input.total-amount.formula-bad {
        background-color: #fef2f2;
        color: #b91c1c;
    }
}
</style>

<script>
(function () {

    const fmt = n => (Math.round(n * 100) / 100)
        .toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });

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
        if (!cell) return;
        cell.textContent = v === 0 ? '' : fmt(v);
    };

    const looksLikeFormula = value => {
        const s = String(value).trim().replace(/,/g, '');
        if (s === '') return false;
        if (s.startsWith('=')) return true;
        if (/^[+\-*\/(]/.test(s)) return true;
        return /[+\-*\/]/.test(s);
    };

    const normalizeFormula = value => {
        const s = String(value).trim();
        if (s === '') return '';
        return s.startsWith('=') ? s : '=' + s.replace(/^=+/, '');
    };

    // Safe arithmetic reader for + - * / and brackets — not eval().
    function evaluate(src) {
        let i = 0;
        const ws = () => { while (i < src.length && /\s/.test(src[i])) i++; };

        function expression() {
            let value = term();
            ws();
            while (src[i] === '+' || src[i] === '-') {
                const op = src[i++];
                const rhs = term();
                value = op === '+' ? value + rhs : value - rhs;
                ws();
            }
            return value;
        }

        function term() {
            let value = factor();
            ws();
            while (src[i] === '*' || src[i] === '/') {
                const op = src[i++];
                const rhs = factor();
                if (op === '/' && rhs === 0) throw new Error('divide by zero');
                value = op === '*' ? value * rhs : value / rhs;
                ws();
            }
            return value;
        }

        function factor() {
            ws();
            if (src[i] === '+') { i++; return factor(); }
            if (src[i] === '-') { i++; return -factor(); }
            if (src[i] === '(') {
                i++;
                const value = expression();
                ws();
                if (src[i] !== ')') throw new Error('missing )');
                i++;
                return value;
            }
            const number = /^\d*\.?\d+/.exec(src.slice(i));
            if (number) { i += number[0].length; return parseFloat(number[0]); }
            throw new Error('cannot read "' + src.slice(i) + '"');
        }

        const value = expression();
        ws();
        if (i !== src.length) throw new Error('cannot read "' + src.slice(i) + '"');
        return value;
    }

    function readAmount(el) {
        const formula = el.dataset.formula || '';
        if (formula.startsWith('=')) {
            try {
                return Math.round(evaluate(formula.slice(1)) * 100) / 100;
            } catch {
                return 0;
            }
        }

        const v = parseFloat(String(el.value).replace(/,/g, ''));
        return isNaN(v) ? 0 : v;
    }

    function applyFormula(el, typed) {
        const formula = normalizeFormula(typed);

        try {
            const value = Math.round(evaluate(formula.slice(1)) * 100) / 100;
            el.dataset.formula = formula;
            el.value = money(String(value));
            el.classList.remove('formula-bad');
            el.classList.add('has-formula');
            el.title = formula;
            return value;
        } catch (err) {
            el.classList.add('formula-bad');
            el.title = formula + ' — ' + err.message;
            return 0;
        }
    }

    function split(total) {
        const employee = Math.round((total / 2) * 100) / 100;
        const lsaf     = Math.round((total - employee) * 100) / 100;
        const monthly  = Math.round((employee / 12) * 100) / 100;
        return { lsaf, employee, monthly };
    }

    function recalc() {

        let tTotal = 0, tLsaf = 0, tEmp = 0, tMonthly = 0, tBalance = 0;

        document.querySelectorAll('.medical-row').forEach(row => {

            const totalInput = row.querySelector('.total-amount');
            const total = readAmount(totalInput);
            const parts = split(total);
            const paid  = parseFloat(row.dataset.paid || 0) || 0;
            const balance = Math.round((parts.employee - paid) * 100) / 100;

            put(row.querySelector('.lsaf-portion'), parts.lsaf);
            put(row.querySelector('.employee-portion'), parts.employee);
            put(row.querySelector('.monthly-portion'), parts.monthly);

            const balCell = row.querySelector('.balance');
            if (balCell) {
                balCell.textContent = balance !== 0 ? fmt(balance) : '';
                balCell.classList.toggle('text-red-600', balance < 0);
            }

            tTotal   += total;
            tLsaf    += parts.lsaf;
            tEmp     += parts.employee;
            tMonthly += parts.monthly;
            tBalance += balance;

            row.classList.toggle('row-empty', total === 0 && paid === 0);
        });

        document.getElementById('sumTotal').textContent    = fmt(tTotal);
        document.getElementById('sumLsaf').textContent     = fmt(tLsaf);
        document.getElementById('sumEmployee').textContent = fmt(tEmp);
        document.getElementById('sumMonthly').textContent  = fmt(tMonthly);
        document.getElementById('sumBalance').textContent  = fmt(tBalance);
    }

    document.querySelectorAll('#medicalSheet input.total-amount').forEach(el => {
        el.addEventListener('input', () => {
            if (el.dataset.formula) {
                delete el.dataset.formula;
                el.classList.remove('has-formula', 'formula-bad');
                el.removeAttribute('title');
            }
            recalc();
        });

        el.addEventListener('focus', () => {
            const formula = el.dataset.formula || '';
            el.value = formula.startsWith('=')
                ? formula
                : String(el.value).replace(/,/g, '');
            el.select?.();
        });

        el.addEventListener('blur', () => {
            const typed = String(el.value).trim();

            if (looksLikeFormula(typed)) {
                applyFormula(el, typed);
            } else {
                delete el.dataset.formula;
                el.classList.remove('has-formula', 'formula-bad');
                el.removeAttribute('title');
                el.value = money(typed);
            }

            recalc();
        });
    });

    const form = document.getElementById('medicalSheet')?.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            form.querySelectorAll('.medical-formula-field').forEach(el => el.remove());

            document.querySelectorAll('#medicalSheet input.total-amount').forEach((el, index) => {
                const formula = el.dataset.formula || '';

                if (formula.startsWith('=')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.className = 'medical-formula-field';
                    hidden.name = 'rows[' + index + '][total_formula]';
                    hidden.value = formula;
                    form.appendChild(hidden);
                }

                el.value = String(readAmount(el)).replace(/,/g, '');
            });
        });
    }

    document.querySelectorAll('#medicalSheet input.total-amount').forEach(el => {
        if (!(el.dataset.formula || '').startsWith('=')) {
            el.value = money(el.value);
        }
    });

    recalc();
})();
</script>

</x-app-layout>
