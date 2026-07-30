<x-app-layout>

@php
    // "06th Nov, 2023" - zero padded day with its ordinal, as on the sample
    $ordinal = function ($d) {
        $n = (int) $d->format('j');
        $suffix = ($n % 100 >= 11 && $n % 100 <= 13)
            ? 'th'
            : ['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th';
        return $d->format('d').'|'.$suffix.'|'.$d->format('M, Y');
    };

    [$letterDay, $letterSuffix, $letterRest] = explode('|', $ordinal($letterDate));
    $periodLabel = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
@endphp

<div class="max-w-4xl mx-auto py-6 px-4">

    {{-- ================= TOOLBAR ================= --}}
    <div class="flex justify-between items-start mb-4 flex-wrap gap-3 no-print">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Bank Letter</h2>
            <p class="text-sm text-gray-500 mt-1">
                Covering letter for the annexure. Prints with nothing but the letter,
                so it can go straight onto your letterhead.
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
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
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4 no-print">{{ session('success') }}</div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 no-print">{{ $errors->first() }}</div>
    @endif

    {{-- ================= LETTER FIELDS ================= --}}
    <form method="GET" class="bg-white p-4 rounded shadow mb-4 no-print">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">

            <div>
                <label class="block text-xs text-gray-500 mb-1">Salary month</label>
                <div class="flex gap-2">
                    <select name="month" class="border px-2 py-2 rounded text-sm flex-1">
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                        @endfor
                    </select>
                    <input type="number" name="year" value="{{ $year }}"
                           class="border px-2 py-2 rounded text-sm w-24">
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Letter date</label>
                <input type="date" name="letter_date" id="letterDate"
                       value="{{ $letterDate->toDateString() }}"
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Amount</label>
                <input type="number" step="0.01" name="amount" id="amount"
                       value="{{ $amount }}"
                       class="w-full border px-3 py-2 rounded text-sm">
                <p class="text-[11px] text-gray-500 mt-1">
                    Taken from the {{ $periodLabel }} bank sheet. Change it only if the letter must say something else.
                </p>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Cheque #</label>
                <input type="text" name="cheque_no" id="chequeNo" value="{{ $chequeNo }}"
                       placeholder="e.g. 30835415"
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Cheque date</label>
                <input type="date" name="cheque_date" id="chequeDate"
                       value="{{ $chequeDate->toDateString() }}"
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm w-full">
                    Update Letter
                </button>
            </div>

        </div>

    </form>

    {{-- ================= ADDRESSEE / SIGNATORY ================= --}}
    <details class="bg-white p-4 rounded shadow mb-4 no-print">
        <summary class="cursor-pointer text-sm font-semibold text-gray-700">
            Bank, signatory and letterhead spacing
        </summary>

        <form method="POST" action="{{ route('admin.salary.bank.letter.settings') }}"
              class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1">Bank</label>
                <input type="text" name="bank" value="{{ $settings['bank'] }}" required
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Branch / address</label>
                <input type="text" name="branch" value="{{ $settings['branch'] }}"
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Signatory</label>
                <input type="text" name="signatory" value="{{ $settings['signatory'] }}"
                       placeholder="Name printed under Best Regards"
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Space for letterhead (mm)</label>
                <input type="number" name="top_mm" value="{{ $settings['top_mm'] }}" min="0" max="150" required
                       class="w-full border px-3 py-2 rounded text-sm">
                <p class="text-[11px] text-gray-500 mt-1">
                    How far down the page the letter starts, to clear your printed letterhead.
                </p>
            </div>

            <div class="md:col-span-2">
                <button class="bg-green-600 text-white px-4 py-2 rounded text-sm">Save</button>
            </div>
        </form>
    </details>

    {{-- ================= THE LETTER ================= --}}
    <div class="bg-white rounded shadow p-8 no-print-shadow" id="letterWrap">

        <div id="bankLetter">

            <p class="mb-10">
                Dated: <span id="outLetterDate">{{ $letterDay }}<sup>{{ $letterSuffix }}</sup> {{ $letterRest }}</span>
            </p>

            <p class="mb-8 leading-relaxed">
                Manager<br>
                {{ $settings['bank'] }}<br>
                {{ $settings['branch'] }}
            </p>

            <p class="mb-8">
                Subject:&nbsp;&nbsp;&nbsp;<span class="underline">Transfer of Salaries</span>
            </p>

            <p class="mb-6">Sir</p>

            <p class="mb-5 leading-relaxed">
                This reference to subject cited above you are therefore requested to transfer the
                Amount of Rs.<span id="outAmount">{{ number_format($amount) }}</span> to account for
                our staff as per an ANNEXURE-A attach.
            </p>

            <p class="mb-5 leading-relaxed">
                We have also enclosed cheque#<span id="outChequeNo">{{ $chequeNo }}</span>
                Dated: <span id="outChequeDate">{{ $chequeDate->format('d/m/Y') }}</span>
                of Rs.<span id="outAmount2">{{ number_format($amount) }}</span> in yourself.
            </p>

            <p class="mt-24 mb-16">Best Regards,</p>

            <p>{{ $settings['signatory'] }}</p>

        </div>

    </div>

</div>

<style>
    #bankLetter { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; }

@media print {

    /* Only the letter reaches the paper - the letterhead supplies the rest */
    @page {
        size: A4 portrait;
        margin: {{ $settings['top_mm'] }}mm 20mm 20mm 20mm;
    }

    #letterWrap {
        padding: 0 !important;
        box-shadow: none !important;
        border: 0 !important;
        background: #fff !important;
    }

    #bankLetter { font-size: 12pt !important; }
}
</style>

<script>
(function () {

    const fmt = n => (Math.round(n) || 0).toLocaleString('en-US');

    const ordinal = n => {
        const v = n % 100;
        if (v >= 11 && v <= 13) return 'th';
        return ({ 1: 'st', 2: 'nd', 3: 'rd' })[n % 10] || 'th';
    };

    const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    const parts = value => {
        if (!value) return null;
        const [y, m, d] = value.split('-').map(Number);
        return { y, m, d };
    };

    const set = (id, text) => {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    };

    function render() {

        const ld = parts(document.getElementById('letterDate').value);
        if (ld) {
            const day = String(ld.d).padStart(2, '0');
            const el = document.getElementById('outLetterDate');
            el.innerHTML = day + '<sup>' + ordinal(ld.d) + '</sup> '
                + MONTHS[ld.m - 1] + ', ' + ld.y;
        }

        const cd = parts(document.getElementById('chequeDate').value);
        if (cd) {
            set('outChequeDate', String(cd.d).padStart(2, '0') + '/'
                + String(cd.m).padStart(2, '0') + '/' + cd.y);
        }

        const amount = parseFloat(document.getElementById('amount').value) || 0;
        set('outAmount', fmt(amount));
        set('outAmount2', fmt(amount));

        set('outChequeNo', document.getElementById('chequeNo').value || '');
    }

    ['letterDate', 'chequeDate', 'amount', 'chequeNo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', render);
    });

    render();
})();
</script>

</x-app-layout>
