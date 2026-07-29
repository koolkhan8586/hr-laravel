<x-app-layout>

<div class="max-w-6xl mx-auto py-6 px-4">

    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Bank Sheet</h2>
            <p class="text-sm text-gray-500 mt-1">
                Employees paid by bank transfer. Anyone paid fully by cheque is excluded.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.salary.sheet', ['month' => $month, 'year' => $year]) }}"
               class="bg-gray-700 text-white px-4 py-2 rounded text-sm">
                Salary Sheet
            </a>
            <button type="button" onclick="window.print()"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
                Print / Export
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
        <div class="text-center mb-4">
            <div class="flex items-center justify-center gap-3 mb-2">
                <img src="{{ asset('uol-logo.png') }}" alt="" style="height:46px"
                     onerror="this.style.display='none'">
                <div class="font-bold text-lg">
                    {{ \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)') }}
                </div>
            </div>
            <h3 class="font-bold text-lg underline">ANNEXURE-A</h3>
            <p class="font-semibold mt-2">Salaries to be Credited</p>
            <p class="text-sm">
                {{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}
            </p>
        </div>

        <table class="w-full text-sm border">

            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 w-16">SR NO.</th>
                    <th class="border p-2 w-28">Employee ID</th>
                    <th class="border p-2 text-left">Name of Employee</th>
                    <th class="border p-2">Account No.</th>
                    <th class="border p-2">New Account #</th>
                    <th class="border p-2 text-right w-32">Amount</th>
                </tr>
            </thead>

            <tbody>

            @foreach($salaries as $i => $salary)
            <tr>
                <td class="border p-2 text-center">{{ $i + 1 }}</td>
                <td class="border p-2 text-center">{{ $salary->user->employee_code ?? '' }}</td>
                <td class="border p-2">{{ $salary->user->name }}</td>
                <td class="border p-2 text-center">{{ $salary->user->bank_account_no ?? '' }}</td>
                <td class="border p-2 text-center">
                    @if($salary->user->new_account_no)
                        {{ $salary->user->new_account_no }}
                    @else
                        <span class="text-red-500 text-xs no-print">account missing</span>
                    @endif
                </td>
                <td class="border p-2 text-right">
                    {{ number_format($salary->bank_amount) }}
                </td>
            </tr>
            @endforeach

            </tbody>

            <tfoot>
                <tr class="bg-gray-100 font-bold">
                    <td class="border p-2 text-right" colspan="5">GRAND TOTAL</td>
                    <td class="border p-2 text-right">{{ number_format($grandTotal) }}</td>
                </tr>
            </tfoot>

        </table>

        {{-- RECONCILIATION --}}
        <div class="mt-6 flex justify-end">
            <table class="text-sm border">
                <tr>
                    <td class="border px-4 py-2 text-gray-600">Faculty sheet</td>
                    <td class="border px-4 py-2 text-right">{{ number_format($summary['teacher_net']) }}</td>
                </tr>
                <tr>
                    <td class="border px-4 py-2 text-gray-600">Staff sheet</td>
                    <td class="border px-4 py-2 text-right">{{ number_format($summary['staff_net']) }}</td>
                </tr>
                <tr>
                    <td class="border px-4 py-2 text-gray-600">Cheque Amount</td>
                    <td class="border px-4 py-2 text-right">-{{ number_format($summary['cheque_total']) }}</td>
                </tr>
                <tr class="bg-gray-100 font-bold">
                    <td class="border px-4 py-2">Salary to bank sheet</td>
                    <td class="border px-4 py-2 text-right">{{ number_format($grandTotal) }}</td>
                </tr>
            </table>
        </div>

        {{-- SIGN OFF --}}
        <div class="flex justify-between mt-12 text-sm">
            <div>Prepared By: _________________</div>
            <div>Checked By: _________________</div>
            <div>Approved By: _________________</div>
        </div>

    </div>

    @endif

</div>

<style>
@media print {
    .no-print { display: none !important; }
}
</style>

</x-app-layout>
