<x-app-layout>

@php
    // Fall back rather than fail if this view is ever rendered by an older
    // controller (for example a deploy where PHP is still serving cached
    // bytecode for the class but the template has already been replaced).
    $sort    = $sort    ?? 'code';
    $dir     = $dir     ?? 'asc';
    $missing = $missing ?? collect();

    $monthName  = \Carbon\Carbon::create()->month($month)->format('F');
    $sheetLabel = match($category) { 'teacher' => 'Teachers', 'all' => 'All Employees', default => 'Staff' };
    $showTPayment = in_array($category, ['teacher', 'all']);
    $orgName    = \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)');

    $earningColumns   = $columns->where('type', 'earning');
    $deductionColumns = $columns->where('type', 'deduction');

    $postedCount = $users->filter(fn($u) => ($existing[$u->id] ?? null)?->isPosted())->count();
    $draftCount  = $users->filter(fn($u) => ($existing[$u->id] ?? null) && !$existing[$u->id]->isPosted())->count();

    $sortLink = function ($key) use ($month, $year, $category, $sort, $dir) {
        $next = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
        return route('admin.salary.sheet', [
            'month' => $month, 'year' => $year, 'category' => $category,
            'sort'  => $key,   'dir'  => $next,
        ]);
    };
    $arrow = fn ($key) => $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';

    // Amounts read as figures, not as raw database decimals: 35,000 rather
    // than 35000.00. Paisa are only shown when there actually are any.
    $money = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }

        $n = (float) $value;

        return number_format($n, fmod($n, 1) == 0.0 ? 0 : 2);
    };
@endphp

<div class="max-w-full mx-auto py-6 px-4 print-area">

    {{-- ================= SCREEN TOOLBAR ================= --}}
    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Salary Sheet</h2>
            <p class="text-sm text-gray-500 mt-1">
                Enter the whole month in one go, then save. Totals update as you type.
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">

            <a href="{{ route('admin.salary.columns') }}"
               class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded text-sm">
                Columns
            </a>

            <a href="{{ route('admin.salary.tax') }}"
               class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded text-sm">
                Tax Rules
            </a>

            <a href="{{ route('admin.salary.bank.sheet', ['month' => $month, 'year' => $year]) }}"
               class="bg-gray-700 text-white px-3 py-2 rounded text-sm">
                Bank Sheet
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

    {{-- ================= PERIOD SELECTOR ================= --}}
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
        Set each employee's Salary Sheet field from
        <a href="{{ route('admin.staff.index') }}" class="underline font-semibold">Staff Management</a>.
    </div>

    @else

    {{-- ================= SHEET ACTIONS (outside the main form) ================= --}}
    <div class="flex gap-2 mb-4 flex-wrap items-center no-print">

        <form method="POST" action="{{ route('admin.salary.sheet.copy') }}"
              onsubmit="return confirm('Copy last month\'s figures into {{ $monthName }} {{ $year }}? Rows already posted will be left alone.');">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="category" value="{{ $category }}">
            <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm">
                Copy Last Month
            </button>
        </form>

        <form method="POST" action="{{ route('admin.salary.sheet.pull.tax') }}"
              onsubmit="return confirm('Replace the Tax column with the figures worked out on the {{ $year }} tax sheet? Posted rows are left alone, and you can still edit any value afterwards.');">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="category" value="{{ $category }}">
            <button class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded text-sm">
                Update Tax from Tax Sheet
            </button>
        </form>

        <a href="{{ route('admin.salary.tax.sheet', ['year' => $year, 'category' => $category, 'source_month' => $month]) }}"
           class="bg-white border border-gray-300 text-gray-700 px-3 py-2 rounded text-sm">
            Open Tax Sheet
        </a>

        <form method="POST" action="{{ route('admin.salary.sheet.post') }}"
              onsubmit="return confirm('Post {{ $draftCount }} draft salary row(s)? This deducts loan instalments and emails each employee.');">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="category" value="{{ $category }}">
            <button class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded text-sm"
                    {{ $draftCount === 0 ? 'disabled' : '' }}
                    style="{{ $draftCount === 0 ? 'opacity:.5;cursor:not-allowed' : '' }}">
                Post Sheet ({{ $draftCount }})
            </button>
        </form>

        <label class="flex items-center gap-2 text-sm bg-white border border-gray-300 px-3 py-2 rounded cursor-pointer">
            <input type="checkbox" id="hideEmptyCols">
            Hide empty columns
        </label>

        <label class="flex items-center gap-2 text-sm bg-white border border-gray-300 px-3 py-2 rounded cursor-pointer">
            <input type="checkbox" id="hideZeros" checked>
            Hide zeros
        </label>

        <span class="text-xs text-gray-500">
            {{ $postedCount }} row(s) already posted.
        </span>

    </div>

    {{-- ================= PRINT LETTERHEAD ================= --}}
    <div class="sheet-header text-center mb-3">
        <div class="flex items-center justify-center gap-3">
            <img src="{{ asset('uol-logo.png') }}" alt="" style="height:46px" onerror="this.style.display='none'">
            <div>
                <div class="font-bold text-lg">{{ $orgName }}</div>
                <div class="text-sm">Salary Sheet {{ $monthName }} {{ $year }} &mdash; {{ $sheetLabel }}</div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.salary.sheet.store') }}">
    @csrf

    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="category" value="{{ $category }}">

    <div class="overflow-x-auto bg-white rounded shadow">

    <table class="min-w-full text-xs border" id="salarySheet">

        <thead>
            <tr class="bg-gray-200">
                <th class="border p-2">Sr.</th>
                <th class="border p-2 text-left">
                    <a href="{{ $sortLink('code') }}" class="hover:underline no-print-link">Code{{ $arrow('code') }}</a>
                </th>
                <th class="border p-2 text-left">
                    <a href="{{ $sortLink('name') }}" class="hover:underline no-print-link">Name{{ $arrow('name') }}</a>
                </th>
                <th class="border p-2">
                    <a href="{{ $sortLink('doj') }}" class="hover:underline no-print-link">DOJ{{ $arrow('doj') }}</a>
                </th>

                <th class="border p-2 bg-green-50">Salary &amp; Wages</th>
                <th class="border p-2 bg-green-50">Extra Load</th>
                <th class="border p-2 bg-green-50">Invigilation</th>
                @if($showTPayment)
                <th class="border p-2 bg-green-50">T.Payment</th>
                @endif
                <th class="border p-2 bg-green-50">Eidi</th>
                <th class="border p-2 bg-green-50">Increment</th>
                @foreach($earningColumns as $col)
                <th class="border p-2 bg-green-50">{{ $col->name }}</th>
                @endforeach
                <th class="border p-2 bg-green-100 font-bold">Total Addition</th>

                <th class="border p-2 bg-red-50">Extra Leaves</th>
                <th class="border p-2 bg-red-50">Tax</th>
                <th class="border p-2 bg-red-50">Loan</th>
                <th class="border p-2 bg-red-50">Insurance</th>
                <th class="border p-2 bg-red-50">Others</th>
                @foreach($deductionColumns as $col)
                <th class="border p-2 bg-red-50">{{ $col->name }}</th>
                @endforeach
                <th class="border p-2 bg-red-100 font-bold">Total Deduction</th>

                <th class="border p-2 bg-blue-100 font-bold">Total Salary Paid</th>
                <th class="border p-2 bg-yellow-50">Amount</th>
                <th class="border p-2">Sign</th>
            </tr>
        </thead>

        <tbody>

        @foreach($users as $i => $user)
        @php
            $row    = $existing[$user->id] ?? null;
            $posted = $row && $row->isPosted();
            $ro     = $posted ? 'readonly' : '';
            $roCls  = $posted ? 'bg-gray-100' : '';
        @endphp

        <tr class="salary-row {{ $posted ? 'bg-gray-50' : '' }}">

            <td class="border p-1 text-center sr-cell"></td>

            <td class="border p-1">
                {{ $user->employee_code ?? '-' }}
                <input type="hidden" name="rows[{{ $i }}][user_id]" value="{{ $user->id }}">
            </td>

            <td class="border p-1 whitespace-nowrap">
                {{ $user->name }}
                @if($posted)
                <span class="text-[10px] text-white bg-gray-500 px-1 rounded no-print">POSTED</span>
                @endif
            </td>

            <td class="border p-1 text-center whitespace-nowrap">
                {{ $user->staff?->joining_date ? \Carbon\Carbon::parse($user->staff->joining_date)->format('d-M-y') : '-' }}
            </td>

            {{-- EARNINGS --}}
            @foreach(['basic_salary','extra_load','invigilation'] as $field)
            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="{{ $field }}"
                       name="rows[{{ $i }}][{{ $field }}]" value="{{ $money($row->$field ?? null) }}" {{ $ro }}
                       class="earning w-24 p-1 text-right border-0 {{ $roCls }}">
            </td>
            @endforeach

            @if($showTPayment)
            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="t_payment"
                       name="rows[{{ $i }}][t_payment]" value="{{ $money($row->t_payment ?? null) }}" {{ $ro }}
                       class="earning w-24 p-1 text-right border-0 {{ $roCls }}">
            </td>
            @else
            <input type="hidden" name="rows[{{ $i }}][t_payment]" value="{{ $row->t_payment ?? 0 }}">
            @endif

            @foreach(['eidi','increment'] as $field)
            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="{{ $field }}"
                       name="rows[{{ $i }}][{{ $field }}]" value="{{ $money($row->$field ?? null) }}" {{ $ro }}
                       class="earning w-24 p-1 text-right border-0 {{ $roCls }}">
            </td>
            @endforeach

            @foreach($earningColumns as $col)
            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="custom_{{ $col->id }}"
                       name="rows[{{ $i }}][custom][{{ $col->id }}]"
                       value="{{ $row && $row->customValue($col->id) != 0 ? $money($row->customValue($col->id)) : '' }}" {{ $ro }}
                       class="earning w-24 p-1 text-right border-0 {{ $roCls }}">
            </td>
            @endforeach

            <td class="border p-1 text-right font-bold bg-green-50 total-addition">0</td>

            {{-- DEDUCTIONS --}}
            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="extra_leaves"
                       name="rows[{{ $i }}][extra_leaves]" value="{{ $money($row->extra_leaves ?? null) }}" {{ $ro }}
                       class="deduction w-24 p-1 text-right border-0 {{ $roCls }}">
            </td>

            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="income_tax"
                       name="rows[{{ $i }}][income_tax]" value="{{ $money($row->income_tax ?? null) }}" {{ $ro }}
                       class="deduction tax-input w-24 p-1 text-right border-0 {{ $roCls }}">
            </td>

            @foreach(['loan_deduction','insurance','other_deductions'] as $field)
            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="{{ $field }}"
                       name="rows[{{ $i }}][{{ $field }}]" value="{{ $money($row->$field ?? null) }}" {{ $ro }}
                       class="deduction w-24 p-1 text-right border-0 {{ $roCls }}">
            </td>
            @endforeach

            @foreach($deductionColumns as $col)
            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="custom_{{ $col->id }}"
                       name="rows[{{ $i }}][custom][{{ $col->id }}]"
                       value="{{ $row && $row->customValue($col->id) != 0 ? $money($row->customValue($col->id)) : '' }}" {{ $ro }}
                       class="deduction w-24 p-1 text-right border-0 {{ $roCls }}">
            </td>
            @endforeach

            <td class="border p-1 text-right font-bold bg-red-50 total-deduction">0</td>

            <td class="border p-1 text-right font-bold bg-blue-50 net-salary">0</td>

            <td class="border p-0">
                <input type="text" inputmode="decimal" data-col="cheque_amount"
                       name="rows[{{ $i }}][cheque_amount]" value="{{ $money($row->cheque_amount ?? null) }}" {{ $ro }}
                       class="cheque w-24 p-1 text-right border-0 bg-yellow-50"
                       title="Leave blank if paid through the bank">
            </td>

            <td class="border p-1"></td>

        </tr>
        @endforeach

        </tbody>

        <tfoot class="bg-gray-100 font-bold">
            <tr>
                <td class="border p-2 text-right" colspan="4">TOTAL</td>
                <td class="border p-2 text-right" data-sum="basic_salary">0</td>
                <td class="border p-2 text-right" data-sum="extra_load">0</td>
                <td class="border p-2 text-right" data-sum="invigilation">0</td>
                @if($showTPayment)
                <td class="border p-2 text-right" data-sum="t_payment">0</td>
                @endif
                <td class="border p-2 text-right" data-sum="eidi">0</td>
                <td class="border p-2 text-right" data-sum="increment">0</td>
                @foreach($earningColumns as $col)
                <td class="border p-2 text-right" data-sum="custom_{{ $col->id }}">0</td>
                @endforeach
                <td class="border p-2 text-right bg-green-100" id="sumAddition">0</td>

                <td class="border p-2 text-right" data-sum="extra_leaves">0</td>
                <td class="border p-2 text-right" data-sum="income_tax">0</td>
                <td class="border p-2 text-right" data-sum="loan_deduction">0</td>
                <td class="border p-2 text-right" data-sum="insurance">0</td>
                <td class="border p-2 text-right" data-sum="other_deductions">0</td>
                @foreach($deductionColumns as $col)
                <td class="border p-2 text-right" data-sum="custom_{{ $col->id }}">0</td>
                @endforeach
                <td class="border p-2 text-right bg-red-100" id="sumDeduction">0</td>

                <td class="border p-2 text-right bg-blue-100" id="sumNet">0</td>
                <td class="border p-2 text-right bg-yellow-100" data-sum="cheque_amount">0</td>
                <td class="border p-2"></td>
            </tr>
        </tfoot>

    </table>

    </div>

    {{-- RECONCILIATION --}}
    <div class="mt-4 flex justify-end">
        <table class="text-sm bg-white rounded shadow border">
            <tr class="border-b">
                <td class="px-4 py-2 text-gray-600">Total Salary</td>
                <td class="px-4 py-2 text-right font-bold" id="recTotal">0</td>
            </tr>
            <tr class="border-b">
                <td class="px-4 py-2 text-gray-600">Cheque Amount</td>
                <td class="px-4 py-2 text-right font-bold" id="recCheque">0</td>
            </tr>
            <tr>
                <td class="px-4 py-2 text-gray-600">Salary to bank sheet</td>
                <td class="px-4 py-2 text-right font-bold text-blue-700" id="recBank">0</td>
            </tr>
        </table>
    </div>

    {{-- SIGN OFF (print) --}}
    <div class="sign-off justify-between mt-10 text-sm">
        <div>Prepared By: _______________</div>
        <div>Checked By: _______________</div>
        <div>Approved By: _______________</div>
    </div>

    <div class="mt-5 no-print">
        <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Save Salary Sheet
        </button>
        <span class="text-xs text-gray-500 ml-3">
            Saves as draft. Use <strong>Post Sheet</strong> once the figures are final.
        </span>
    </div>

    </form>

    @if($missing->isNotEmpty())
    <div class="mt-6 bg-amber-50 border border-amber-200 rounded p-4 no-print">
        <p class="font-semibold text-amber-800 text-sm mb-2">
            {{ $missing->count() }} employee(s) are not on this sheet
        </p>
        <table class="text-xs w-full">
            <tr class="text-left text-amber-800">
                <th class="py-1 pr-4">Code</th>
                <th class="py-1 pr-4">Name</th>
                <th class="py-1">Why</th>
            </tr>
            @foreach($missing as $row)
            <tr class="text-amber-900 border-t border-amber-100">
                <td class="py-1 pr-4">{{ $row['user']->employee_code ?: '-' }}</td>
                <td class="py-1 pr-4">{{ $row['user']->name }}</td>
                <td class="py-1">{{ $row['reason'] }}</td>
            </tr>
            @endforeach
        </table>
        <p class="text-xs text-amber-700 mt-2">
            Change their Salary Sheet or Role on the
            <a href="{{ route('admin.staff.index') }}" class="underline font-semibold">staff record</a>,
            or pick <strong>All</strong> above to see everyone at once.
        </p>
    </div>
    @endif

    @endif

</div>

<style>
    .sheet-header { display: none; }
    .sign-off { display: none; }

    /* Sr numbering comes from a counter rather than a fixed number, so rows
       hidden from the printout do not leave gaps in the sequence. */
    #salarySheet tbody { counter-reset: salaryRow; }
    #salarySheet tbody tr.salary-row { counter-increment: salaryRow; }
    #salarySheet tbody tr.salary-row .sr-cell::before { content: counter(salaryRow); }

@media print {
    .sheet-header { display: block !important; }
    .sign-off { display: flex !important; }

    @page { size: A4 landscape; margin: 8mm; }

    #salarySheet { font-size: 8px !important; }
    #salarySheet th, #salarySheet td { padding: 2px !important; }
    #salarySheet input { width: auto !important; font-size: 8px !important; text-align: right; }

    /* Sortable header links print as plain text */
    .no-print-link { text-decoration: none !important; color: #000 !important; }

    /* Nobody with a blank Salary & Wages belongs on the printed sheet */
    #salarySheet tbody tr.row-no-salary { display: none !important; }
}
</style>

<script>
(function () {

    const fmt = n => (Math.round(n * 100) / 100)
        .toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });

    /* ---------- Amounts are typed and shown as figures ----------
       The boxes are text, not number, so they can carry thousands
       separators. Everything that reads a box goes through num(), and the
       commas come off again before the sheet is submitted. */

    const num = el => {
        const v = parseFloat(String(el.value).replace(/,/g, ''));
        return isNaN(v) ? 0 : v;
    };

    // 35000 -> "35,000", 1234.5 -> "1,234.50", blank stays blank
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

    const moneyBoxes = () => document.querySelectorAll('#salarySheet input[data-col]');

    function formatBoxes() {
        moneyBoxes().forEach(el => { el.value = money(el.value); });
    }

    // Editing is easier without the commas in the way, so they come off while
    // the box has focus and go back on when it is left.
    document.addEventListener('focusin', e => {
        const el = e.target;
        if (el.matches('#salarySheet input[data-col]')) {
            el.value = String(el.value).replace(/,/g, '');
            el.select?.();
        }
    });

    document.addEventListener('focusout', e => {
        const el = e.target;
        if (el.matches('#salarySheet input[data-col]')) {
            el.value = money(el.value);
        }
    });

    function recalc() {

        let grandNet = 0, grandCheque = 0;
        const colSums = {};

        document.querySelectorAll('.salary-row').forEach(row => {

            let addition = 0;
            row.querySelectorAll('.earning').forEach(el => addition += num(el));

            let deduction = 0;
            row.querySelectorAll('.deduction').forEach(el => deduction += num(el));

            const net = addition - deduction;

            const blankZero = zeroBox && zeroBox.checked;
            const put = (sel, v) => {
                const cell = row.querySelector(sel);
                cell.dataset.raw = fmt(v);
                cell.textContent = (blankZero && v === 0) ? '' : fmt(v);
            };

            put('.total-addition',  addition);
            put('.total-deduction', deduction);
            put('.net-salary',      net);

            grandNet += net;

            const chq = row.querySelector('.cheque');
            if (chq) grandCheque += num(chq);

            row.querySelectorAll('input[data-col]').forEach(el => {
                const k = el.dataset.col;
                colSums[k] = (colSums[k] || 0) + num(el);
            });
        });

        document.querySelectorAll('[data-sum]').forEach(cell => {
            cell.textContent = fmt(colSums[cell.dataset.sum] || 0);
        });

        let totalAddition = 0, totalDeduction = 0;
        document.querySelectorAll('.salary-row').forEach(row => {
            row.querySelectorAll('.earning').forEach(el => totalAddition += num(el));
            row.querySelectorAll('.deduction').forEach(el => totalDeduction += num(el));
        });

        document.getElementById('sumAddition').textContent  = fmt(totalAddition);
        document.getElementById('sumDeduction').textContent = fmt(totalDeduction);
        document.getElementById('sumNet').textContent       = fmt(grandNet);

        document.getElementById('recTotal').textContent  = fmt(grandNet);
        document.getElementById('recCheque').textContent = fmt(grandCheque);
        document.getElementById('recBank').textContent   = fmt(grandNet - grandCheque);
    }

    /* ---------- Rows with no salary are left off the printout ---------- */

    function markEmptyRows() {
        document.querySelectorAll('.salary-row').forEach(row => {
            const wage = row.querySelector('input[data-col="basic_salary"]');
            row.classList.toggle('row-no-salary', !wage || num(wage) === 0);
        });
    }

    /* ---------- Blank out zero cells ---------- */

    const zeroBox = document.getElementById('hideZeros');

    function applyHideZeros() {
        if (!zeroBox) return;

        const on = zeroBox.checked;

        document.querySelectorAll('#salarySheet input[data-col]').forEach(el => {

            if (on) {
                // Remember which cells we blanked so they can come back.
                if (el.value !== '' && num(el) === 0) {
                    el.dataset.zeroed = '1';
                    el.value = '';
                }
            } else if (el.dataset.zeroed === '1') {
                el.value = '0';
                delete el.dataset.zeroed;
            }
        });

        // Computed cells follow the same rule.
        document.querySelectorAll('.total-addition, .total-deduction, .net-salary')
            .forEach(cell => {
                const raw = cell.dataset.raw ?? cell.textContent;
                cell.dataset.raw = raw;
                cell.textContent = (on && parseFloat(raw.replace(/,/g, '')) === 0) ? '' : raw;
            });
    }

    if (zeroBox) {
        zeroBox.addEventListener('change', () => { applyHideZeros(); recalc(); applyHideEmpty(); });
    }

    /* ---------- Hide columns that are entirely empty ---------- */

    const hideBox = document.getElementById('hideEmptyCols');

    function applyHideEmpty() {
        const table = document.getElementById('salarySheet');
        if (!table || !hideBox) return;

        const on = hideBox.checked;

        // Which data columns carry nothing at all?
        const empty = {};
        document.querySelectorAll('#salarySheet input[data-col]').forEach(el => {
            const k = el.dataset.col;
            if (!(k in empty)) empty[k] = true;
            if (num(el) !== 0) empty[k] = false;
        });

        const firstRow = table.tBodies[0].rows[0];
        if (!firstRow) return;

        Object.keys(empty).forEach(key => {

            const probe = firstRow.querySelector(`input[data-col="${key}"]`);
            if (!probe) return;

            const idx  = probe.closest('td').cellIndex;
            const hide = on && empty[key] === true;
            const val  = hide ? 'none' : '';

            // Header row carrying the column labels
            const headRow = table.tHead.rows[table.tHead.rows.length - 1];
            if (headRow.cells[idx]) headRow.cells[idx].style.display = val;

            for (const row of table.tBodies[0].rows) {
                if (row.cells[idx]) row.cells[idx].style.display = val;
            }

            // The footer's first cell spans four columns, so match on the
            // key rather than trusting cell positions to line up.
            const footCell = table.tFoot.querySelector(`[data-sum="${key}"]`);
            if (footCell) footCell.style.display = val;
        });
    }

    if (hideBox) {
        hideBox.addEventListener('change', applyHideEmpty);
    }

    moneyBoxes().forEach(el => el.addEventListener('input', () => {
        recalc();
        applyHideEmpty();
        markEmptyRows();
    }));

    // Send plain numbers, whatever is on screen.
    const sheetForm = document.getElementById('salarySheet')?.closest('form');
    if (sheetForm) {
        sheetForm.addEventListener('submit', () => {
            moneyBoxes().forEach(el => {
                el.value = String(el.value).replace(/,/g, '');
            });
        });
    }

    formatBoxes();
    applyHideZeros();
    recalc();
    applyHideEmpty();
    markEmptyRows();
})();
</script>

</x-app-layout>
