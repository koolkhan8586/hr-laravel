<x-app-layout>

<div class="max-w-full mx-auto py-6 px-4">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Salary Sheet</h2>
            <p class="text-sm text-gray-500 mt-1">
                Enter the whole month in one go, then save. Totals update as you type.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.salary.bank.sheet', ['month' => $month, 'year' => $year]) }}"
               class="bg-gray-700 text-white px-4 py-2 rounded text-sm">
                Bank Sheet
            </a>
            <button type="button" onclick="window.print()"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
                Print
            </button>
        </div>

    </div>

    @if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- PERIOD / CATEGORY SELECTOR --}}
    <form method="GET" class="flex gap-3 mb-5 items-end flex-wrap bg-white p-4 rounded shadow">

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
            </select>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Load Sheet</button>

    </form>

    @if($users->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
        No employees are marked as <strong>{{ ucfirst($category) }}</strong> yet.
        Set each employee's Salary Sheet field from
        <a href="{{ route('admin.staff.index') }}" class="underline font-semibold">Staff Management</a>.
    </div>

    @else

    <form method="POST" action="{{ route('admin.salary.sheet.store') }}">
    @csrf

    <input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="category" value="{{ $category }}">

    <div class="overflow-x-auto bg-white rounded shadow">

    <table class="min-w-full text-xs border" id="salarySheet">

        <thead>
            <tr class="bg-gray-100 text-center">
                <th class="border p-2" colspan="20">
                    Salary Sheet {{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}
                    &mdash; {{ $category === 'teacher' ? 'Teachers' : 'Staff' }}
                </th>
            </tr>
            <tr class="bg-gray-200">
                <th class="border p-2">Sr.</th>
                <th class="border p-2 text-left">Code</th>
                <th class="border p-2 text-left">Name</th>
                <th class="border p-2">DOJ</th>

                <th class="border p-2 bg-green-50">Salary &amp; Wages</th>
                <th class="border p-2 bg-green-50">Extra Load</th>
                <th class="border p-2 bg-green-50">Invigilation</th>
                @if($category === 'teacher')
                <th class="border p-2 bg-green-50">T.Payment</th>
                @endif
                <th class="border p-2 bg-green-50">Eidi</th>
                <th class="border p-2 bg-green-50">Increment</th>
                <th class="border p-2 bg-green-100 font-bold">Total Addition</th>

                <th class="border p-2 bg-red-50">Extra Leaves</th>
                <th class="border p-2 bg-red-50">Tax</th>
                <th class="border p-2 bg-red-50">Loan</th>
                <th class="border p-2 bg-red-50">Insurance</th>
                <th class="border p-2 bg-red-50">Others</th>
                <th class="border p-2 bg-red-100 font-bold">Total Deduction</th>

                <th class="border p-2 bg-blue-100 font-bold">Total Salary Paid</th>
                <th class="border p-2 bg-yellow-50">Amount (Cheque)</th>
                <th class="border p-2">Sign</th>
            </tr>
        </thead>

        <tbody>

        @foreach($users as $i => $user)
        @php
            $row = $existing[$user->id] ?? null;
            $posted = $row && $row->isPosted();
        @endphp

        <tr class="salary-row {{ $posted ? 'bg-gray-100' : '' }}">

            <td class="border p-1 text-center">{{ $i + 1 }}</td>

            <td class="border p-1">
                {{ $user->employee_code ?? '-' }}
                <input type="hidden" name="rows[{{ $i }}][user_id]" value="{{ $user->id }}">
            </td>

            <td class="border p-1 whitespace-nowrap">
                {{ $user->name }}
                @if($posted)
                <span class="text-[10px] text-white bg-gray-500 px-1 rounded">POSTED</span>
                @endif
            </td>

            <td class="border p-1 text-center whitespace-nowrap">
                {{ $user->staff?->joining_date ? \Carbon\Carbon::parse($user->staff->joining_date)->format('d-M-y') : '-' }}
            </td>

            {{-- EARNINGS --}}
            @foreach(['basic_salary','extra_load','invigilation'] as $field)
            <td class="border p-0">
                <input type="number" step="0.01" min="0"
                       name="rows[{{ $i }}][{{ $field }}]"
                       value="{{ $row->$field ?? '' }}"
                       {{ $posted ? 'readonly' : '' }}
                       class="earning w-24 p-1 text-right border-0 focus:ring-1 focus:ring-blue-400 {{ $posted ? 'bg-gray-100' : '' }}">
            </td>
            @endforeach

            @if($category === 'teacher')
            <td class="border p-0">
                <input type="number" step="0.01" min="0"
                       name="rows[{{ $i }}][t_payment]"
                       value="{{ $row->t_payment ?? '' }}"
                       {{ $posted ? 'readonly' : '' }}
                       class="earning w-24 p-1 text-right border-0 focus:ring-1 focus:ring-blue-400 {{ $posted ? 'bg-gray-100' : '' }}">
            </td>
            @else
            <input type="hidden" name="rows[{{ $i }}][t_payment]" value="{{ $row->t_payment ?? 0 }}">
            @endif

            @foreach(['eidi','increment'] as $field)
            <td class="border p-0">
                <input type="number" step="0.01" min="0"
                       name="rows[{{ $i }}][{{ $field }}]"
                       value="{{ $row->$field ?? '' }}"
                       {{ $posted ? 'readonly' : '' }}
                       class="earning w-24 p-1 text-right border-0 focus:ring-1 focus:ring-blue-400 {{ $posted ? 'bg-gray-100' : '' }}">
            </td>
            @endforeach

            <td class="border p-1 text-right font-bold bg-green-50 total-addition">0</td>

            {{-- DEDUCTIONS --}}
            @foreach(['extra_leaves','income_tax','loan_deduction','insurance','other_deductions'] as $field)
            <td class="border p-0">
                <input type="number" step="0.01" min="0"
                       name="rows[{{ $i }}][{{ $field }}]"
                       value="{{ $row->$field ?? '' }}"
                       {{ $posted ? 'readonly' : '' }}
                       class="deduction w-24 p-1 text-right border-0 focus:ring-1 focus:ring-blue-400 {{ $posted ? 'bg-gray-100' : '' }}">
            </td>
            @endforeach

            <td class="border p-1 text-right font-bold bg-red-50 total-deduction">0</td>

            <td class="border p-1 text-right font-bold bg-blue-50 net-salary">0</td>

            <td class="border p-0">
                <input type="number" step="0.01" min="0"
                       name="rows[{{ $i }}][cheque_amount]"
                       value="{{ $row->cheque_amount ?? '' }}"
                       {{ $posted ? 'readonly' : '' }}
                       class="cheque w-24 p-1 text-right border-0 bg-yellow-50 focus:ring-1 focus:ring-blue-400"
                       title="Leave blank if paid through the bank">
            </td>

            <td class="border p-1"></td>

        </tr>
        @endforeach

        </tbody>

        <tfoot class="bg-gray-100 font-bold">
            <tr>
                <td class="border p-2 text-right" colspan="4">TOTAL</td>
                <td class="border p-2 text-right" id="sumBasic">0</td>
                <td class="border p-2 text-right" id="sumExtraLoad">0</td>
                <td class="border p-2 text-right" id="sumInvigilation">0</td>
                @if($category === 'teacher')
                <td class="border p-2 text-right" id="sumTPayment">0</td>
                @endif
                <td class="border p-2 text-right" id="sumEidi">0</td>
                <td class="border p-2 text-right" id="sumIncrement">0</td>
                <td class="border p-2 text-right bg-green-100" id="sumAddition">0</td>
                <td class="border p-2 text-right" id="sumExtraLeaves">0</td>
                <td class="border p-2 text-right" id="sumTax">0</td>
                <td class="border p-2 text-right" id="sumLoan">0</td>
                <td class="border p-2 text-right" id="sumInsurance">0</td>
                <td class="border p-2 text-right" id="sumOthers">0</td>
                <td class="border p-2 text-right bg-red-100" id="sumDeduction">0</td>
                <td class="border p-2 text-right bg-blue-100" id="sumNet">0</td>
                <td class="border p-2 text-right bg-yellow-100" id="sumCheque">0</td>
                <td class="border p-2"></td>
            </tr>
        </tfoot>

    </table>

    </div>

    {{-- RECONCILIATION --}}
    <div class="mt-4 flex justify-end">
        <table class="text-sm bg-white rounded shadow">
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

    <div class="mt-5">
        <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded">
            Save Salary Sheet
        </button>
        <span class="text-xs text-gray-500 ml-3">
            Saves as draft. Post them from Salary Management when finalised.
        </span>
    </div>

    </form>

    @endif

</div>

<script>
(function () {

    const fmt = n => n.toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });

    const num = el => {
        const v = parseFloat(el.value);
        return isNaN(v) ? 0 : v;
    };

    function recalc() {

        let grandNet = 0, grandCheque = 0;
        const colSums = {};

        document.querySelectorAll('.salary-row').forEach(row => {

            let addition = 0;
            row.querySelectorAll('.earning').forEach(el => addition += num(el));

            let deduction = 0;
            row.querySelectorAll('.deduction').forEach(el => deduction += num(el));

            const net = addition - deduction;

            row.querySelector('.total-addition').textContent  = fmt(addition);
            row.querySelector('.total-deduction').textContent = fmt(deduction);
            row.querySelector('.net-salary').textContent      = fmt(net);

            grandNet += net;

            const chq = row.querySelector('.cheque');
            if (chq) grandCheque += num(chq);

            // per-column totals
            row.querySelectorAll('input[type=number]').forEach(el => {
                const m = el.name.match(/\[(\w+)\]$/);
                if (!m) return;
                colSums[m[1]] = (colSums[m[1]] || 0) + num(el);
            });
        });

        const put = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = fmt(val || 0);
        };

        put('sumBasic',        colSums.basic_salary);
        put('sumExtraLoad',    colSums.extra_load);
        put('sumInvigilation', colSums.invigilation);
        put('sumTPayment',     colSums.t_payment);
        put('sumEidi',         colSums.eidi);
        put('sumIncrement',    colSums.increment);
        put('sumExtraLeaves',  colSums.extra_leaves);
        put('sumTax',          colSums.income_tax);
        put('sumLoan',         colSums.loan_deduction);
        put('sumInsurance',    colSums.insurance);
        put('sumOthers',       colSums.other_deductions);
        put('sumCheque',       colSums.cheque_amount);

        const totalAddition = (colSums.basic_salary || 0) + (colSums.extra_load || 0)
            + (colSums.invigilation || 0) + (colSums.t_payment || 0)
            + (colSums.eidi || 0) + (colSums.increment || 0);

        const totalDeduction = (colSums.extra_leaves || 0) + (colSums.income_tax || 0)
            + (colSums.loan_deduction || 0) + (colSums.insurance || 0)
            + (colSums.other_deductions || 0);

        put('sumAddition',  totalAddition);
        put('sumDeduction', totalDeduction);
        put('sumNet',       grandNet);

        put('recTotal',  grandNet);
        put('recCheque', grandCheque);
        put('recBank',   grandNet - grandCheque);
    }

    document.querySelectorAll('#salarySheet input[type=number]')
        .forEach(el => el.addEventListener('input', recalc));

    recalc();
})();
</script>

</x-app-layout>
