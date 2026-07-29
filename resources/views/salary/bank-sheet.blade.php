<x-app-layout>

@php
    $sortLink = function ($key) use ($month, $year, $sort, $dir) {
        $next = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
        return route('admin.salary.bank.sheet', [
            'month' => $month, 'year' => $year, 'sort' => $key, 'dir' => $next,
            'show'  => $show,
        ]);
    };
    $arrow = fn ($key) => $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
@endphp

<div class="max-w-6xl mx-auto py-6 px-4 print-area">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Bank Sheet</h2>
            <p class="text-sm text-gray-500 mt-1">
                Transfers to send to the bank. Merged salaries appear on the receiving account.
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.salary.sheet', ['month' => $month, 'year' => $year]) }}"
               class="bg-gray-700 text-white px-4 py-2 rounded text-sm">
                Salary Sheet
            </a>

            <a href="{{ route('admin.salary.bank.sheet.export', ['month' => $month, 'year' => $year]) }}"
               class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded text-sm">
                Export CSV
            </a>

            <button type="button" onclick="window.print()"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
                Print
            </button>
        </div>

    </div>

    {{-- PERIOD SELECTOR --}}
    <form method="GET" class="flex gap-3 mb-5 items-end flex-wrap bg-white p-4 rounded shadow no-print">

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
            <label class="block text-xs text-gray-500 mb-1">Sort by</label>
            <select name="sort" class="border px-3 py-2 rounded text-sm">
                <option value="code" {{ $sort === 'code' ? 'selected' : '' }}>Employee code</option>
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Name</option>
                <option value="amount" {{ $sort === 'amount' ? 'selected' : '' }}>Amount</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Order</label>
            <select name="dir" class="border px-3 py-2 rounded text-sm">
                <option value="asc" {{ $dir === 'asc' ? 'selected' : '' }}>Ascending</option>
                <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Descending</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Show</label>
            <select name="show" class="border px-3 py-2 rounded text-sm">
                <option value="payable" {{ $show === 'payable' ? 'selected' : '' }}>Payable only</option>
                <option value="all" {{ $show === 'all' ? 'selected' : '' }}>Everyone on the sheet</option>
            </select>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Load</button>

    </form>

    @if($salaries->isEmpty())

    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded">
        Nothing to credit for
        {{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}.
        Create the salary sheet for this month first.
    </div>

    @else

    <div id="bankSheetArea" class="bg-white rounded shadow p-6">

        {{-- HEADER --}}
        <div class="text-center mb-4 bank-head">
            <div class="flex items-center justify-center gap-3 mb-2">
                <img src="{{ asset('uol-logo.png') }}" alt="" style="height:40px"
                     onerror="this.style.display='none'">
                <div class="font-bold text-lg">
                    {{ \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)') }}
                </div>
            </div>
            <h3 class="font-bold underline">ANNEXURE-A</h3>
            <p class="font-semibold mt-1">Salaries to be Credited</p>
            <p class="text-sm">
                {{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}
            </p>
        </div>

        <table class="w-full text-sm border" id="bankTable">

            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 w-16">SR NO.</th>
                    <th class="border p-2 w-28">
                        <a href="{{ $sortLink('code') }}" class="hover:underline no-print-link">
                            Employee ID{{ $arrow('code') }}
                        </a>
                    </th>
                    <th class="border p-2 text-left">
                        <a href="{{ $sortLink('name') }}" class="hover:underline no-print-link">
                            Name of Employee{{ $arrow('name') }}
                        </a>
                    </th>
                    <th class="border p-2">Account No.</th>
                    <th class="border p-2 text-right w-32">
                        <a href="{{ $sortLink('amount') }}" class="hover:underline no-print-link">
                            Amount{{ $arrow('amount') }}
                        </a>
                    </th>
                </tr>
            </thead>

            <tbody>

            @foreach($salaries as $i => $row)
            <tr>
                <td class="border p-2 text-center">{{ $i + 1 }}</td>
                <td class="border p-2 text-center">{{ $row['user']->employee_code ?? '' }}</td>
                <td class="border p-2">
                    {{ $row['user']->name }}
                    @if(!empty($row['contributors']))
                    <span class="text-xs text-gray-600">
                        (incl.
                        {{ collect($row['contributors'])->map(fn($c) => $c['user']->name)->implode(', ') }})
                    </span>
                    @endif
                </td>
                <td class="border p-2 text-center">
                    @if($row['user']->bank_account_no)
                        {{ $row['user']->bank_account_no }}
                    @else
                        <span class="text-red-500 text-xs no-print">account missing</span>
                    @endif
                </td>
                <td class="border p-2 text-right">
                    @if($row['total'] > 0)
                        {{ number_format($row['total']) }}
                    @else
                        <span class="text-gray-500">&ndash;</span>
                    @endif
                </td>
            </tr>
            @endforeach

            </tbody>

            <tfoot>
                <tr class="bg-gray-100 font-bold">
                    <td class="border p-2 text-right" colspan="4">GRAND TOTAL</td>
                    <td class="border p-2 text-right">{{ number_format($grandTotal) }}</td>
                </tr>
            </tfoot>

        </table>

        {{-- RECONCILIATION --}}
        <div class="mt-6 flex justify-end bank-recon">
            <table class="text-sm border">
                <tr>
                    <td class="border px-4 py-1 text-gray-600">Faculty sheet</td>
                    <td class="border px-4 py-1 text-right">{{ number_format($summary['teacher_net']) }}</td>
                </tr>
                <tr>
                    <td class="border px-4 py-1 text-gray-600">Staff sheet</td>
                    <td class="border px-4 py-1 text-right">{{ number_format($summary['staff_net']) }}</td>
                </tr>
                <tr>
                    <td class="border px-4 py-1 text-gray-600">Cheque Amount</td>
                    <td class="border px-4 py-1 text-right">-{{ number_format($summary['cheque_total']) }}</td>
                </tr>
                <tr class="bg-gray-100 font-bold">
                    <td class="border px-4 py-1">Salary to bank sheet</td>
                    <td class="border px-4 py-1 text-right">{{ number_format($grandTotal) }}</td>
                </tr>
            </table>
        </div>

        {{-- LEFT OFF THE SHEET --}}
        @if($excluded->isNotEmpty())
        <div class="mt-6 bg-amber-50 border border-amber-200 rounded p-4 no-print">
            <p class="font-semibold text-amber-800 text-sm mb-2">
                {{ $excluded->count() }} employee(s) are not on this bank sheet
            </p>
            <table class="text-xs w-full">
                <tr class="text-left text-amber-800">
                    <th class="py-1 pr-4">Code</th>
                    <th class="py-1 pr-4">Name</th>
                    <th class="py-1 pr-4 text-right">Net for the month</th>
                    <th class="py-1">Why</th>
                </tr>
                @foreach($excluded->sortBy(fn($r) => $r['user']->employee_code ?: 'zzzz') as $row)
                <tr class="text-amber-900 border-t border-amber-100">
                    <td class="py-1 pr-4">{{ $row['user']->employee_code ?: '-' }}</td>
                    <td class="py-1 pr-4">{{ $row['user']->name }}</td>
                    <td class="py-1 pr-4 text-right">{{ number_format($row['total']) }}</td>
                    <td class="py-1">
                        @if($row['total'] <= 0)
                            Nothing to transfer this month
                        @else
                            No account number on file
                        @endif
                    </td>
                </tr>
                @endforeach
            </table>
            <p class="text-xs text-amber-700 mt-2">
                Add an account number on the staff record, or point them at a
                colleague's account, to bring them onto the sheet.
            </p>
        </div>
        @endif

        {{-- SIGN OFF --}}
        <div class="flex justify-between mt-8 text-sm bank-sign">
            <div>Prepared By: _________________</div>
            <div>Checked By: _________________</div>
            <div>Approved By: _________________</div>
        </div>

    </div>

    @endif

</div>

<style>
@media print {

    @page { size: A4 portrait; margin: 7mm; }

    #bankSheetArea {
        padding: 0 !important;
        box-shadow: none !important;
    }

    /* Squeeze the whole annexure onto a single sheet */
    #bankTable { font-size: 7.5pt !important; }
    #bankTable th,
    #bankTable td { padding: 1px 3px !important; line-height: 1.15 !important; }

    .bank-head { margin-bottom: 4px !important; }
    .bank-head img { height: 30px !important; }
    .bank-head h3 { font-size: 11pt !important; }
    .bank-head p  { margin: 0 !important; font-size: 8pt !important; }
    .bank-head .font-bold.text-lg { font-size: 11pt !important; }

    .bank-recon { margin-top: 6px !important; }
    .bank-recon table { font-size: 7.5pt !important; }
    .bank-recon td { padding: 1px 6px !important; }

    .bank-sign { margin-top: 10px !important; font-size: 8pt !important; }

    /* Header links print as plain text */
    .no-print-link { text-decoration: none !important; color: #000 !important; }

    #bankSheetArea, #bankTable { page-break-inside: auto; }
}
</style>

</x-app-layout>
