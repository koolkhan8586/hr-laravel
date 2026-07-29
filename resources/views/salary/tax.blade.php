<x-app-layout>

<div class="max-w-5xl mx-auto py-8 px-4">

    <div class="flex justify-between items-start mb-6 flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tax Calculate</h2>
            <p class="text-sm text-gray-500 mt-1">
                Define the slabs used by <strong>Auto-calculate Tax</strong> on the salary sheet.
                Every amount stays editable by hand afterwards.
            </p>
        </div>

        <a href="{{ route('admin.salary.sheet') }}"
           class="bg-gray-700 text-white px-4 py-2 rounded text-sm">
            Back to Salary Sheet
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    @if($errors->any())
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ $errors->first() }}</div>
    @endif

    {{-- ================= BASIS ================= --}}
    <div class="bg-white p-5 rounded shadow mb-6">
        <h3 class="font-semibold mb-3">Slab Basis</h3>

        <form method="POST" action="{{ route('admin.salary.tax.basis') }}" class="flex gap-4 items-end flex-wrap">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1">The amounts below are</label>
                <select name="basis" class="border px-3 py-2 rounded text-sm">
                    <option value="annual" {{ $basis === 'annual' ? 'selected' : '' }}>Yearly income</option>
                    <option value="monthly" {{ $basis === 'monthly' ? 'selected' : '' }}>Monthly income</option>
                </select>
            </div>

            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Save Basis</button>

            <p class="text-xs text-gray-500 flex-1 min-w-64">
                @if($basis === 'annual')
                    Slabs are read as yearly figures. The sheet multiplies the month's Total Addition
                    by 12, finds the tax, then divides it back by 12.
                @else
                    Slabs are read as monthly figures and applied straight to the month's Total Addition.
                @endif
            </p>
        </form>
    </div>

    {{-- ================= ADD SLAB ================= --}}
    <div class="bg-white p-5 rounded shadow mb-6">
        <h3 class="font-semibold mb-1">Add Slab</h3>
        <p class="text-xs text-gray-500 mb-3">
            Tax = Fixed amount + (income &minus; From) &times; Rate %. Leave <em>To</em> blank for the top slab.
        </p>

        <form method="POST" action="{{ route('admin.salary.tax.store') }}"
              class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1">From (over)</label>
                <input type="number" step="0.01" min="0" name="from_amount"
                       value="{{ old('from_amount', 0) }}" required
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">To (up to)</label>
                <input type="number" step="0.01" min="0" name="to_amount"
                       value="{{ old('to_amount') }}" placeholder="blank = no limit"
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Fixed amount</label>
                <input type="number" step="0.01" min="0" name="fixed_amount"
                       value="{{ old('fixed_amount', 0) }}" required
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Rate %</label>
                <input type="number" step="0.01" min="0" max="100" name="percentage"
                       value="{{ old('percentage', 0) }}" required
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <button class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded text-sm w-full">
                    Add Slab
                </button>
            </div>
        </form>
    </div>

    {{-- ================= SLABS ================= --}}
    <div class="bg-white rounded shadow overflow-x-auto mb-6">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-right">From (over)</th>
                    <th class="p-3 text-right">To (up to)</th>
                    <th class="p-3 text-right">Fixed amount</th>
                    <th class="p-3 text-right">Rate %</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>

            <tbody>

            @forelse($slabs as $slab)
            <tr class="border-t">
                <td class="p-3 text-right">{{ number_format($slab->from_amount) }}</td>
                <td class="p-3 text-right">
                    {{ is_null($slab->to_amount) ? 'and above' : number_format($slab->to_amount) }}
                </td>
                <td class="p-3 text-right">{{ number_format($slab->fixed_amount) }}</td>
                <td class="p-3 text-right">{{ rtrim(rtrim(number_format($slab->percentage, 2), '0'), '.') }}%</td>
                <td class="p-3 text-center">
                    <form method="POST" action="{{ route('admin.salary.tax.destroy', $slab->id) }}"
                          onsubmit="return confirm('Remove this slab?');">
                        @csrf
                        @method('DELETE')
                        <button class="bg-red-500 text-white px-3 py-1 rounded text-xs">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="p-4 text-gray-500 text-center">
                    No slabs configured. Auto-calculate Tax will do nothing until you add some.
                </td>
            </tr>
            @endforelse

            </tbody>
        </table>

    </div>

    {{-- ================= TESTER ================= --}}
    @if($slabs->count())
    <div class="bg-white p-5 rounded shadow">
        <h3 class="font-semibold mb-3">Check a figure</h3>

        <div class="flex gap-3 items-end flex-wrap">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Monthly income</label>
                <input type="number" id="testIncome" value="100000"
                       class="border px-3 py-2 rounded text-sm w-40">
            </div>
            <div class="text-sm">
                <div class="text-gray-500 text-xs">Monthly tax</div>
                <div class="font-bold text-lg" id="testResult">-</div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const SLABS = @json($slabs);
        const BASIS = @json($basis);

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

        function monthlyTax(m) {
            if (m <= 0) return 0;
            return BASIS === 'monthly' ? taxFor(m) : taxFor(m * 12) / 12;
        }

        const input = document.getElementById('testIncome');
        const out   = document.getElementById('testResult');

        function run() {
            const v = parseFloat(input.value) || 0;
            out.textContent = monthlyTax(v).toLocaleString(undefined, {
                minimumFractionDigits: 0, maximumFractionDigits: 2
            });
        }

        input.addEventListener('input', run);
        run();
    })();
    </script>
    @endif

</div>

</x-app-layout>
