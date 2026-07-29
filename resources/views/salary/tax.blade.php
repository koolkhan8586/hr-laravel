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

        <div class="flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('admin.salary.tax.preset') }}"
                  onsubmit="return confirm('Replace every slab below with the FBR 2026-27 bands? Anything you have changed here will be lost.');">
                @csrf
                <button class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded text-sm">
                    Load FBR 2026&ndash;27
                </button>
            </form>

            <a href="{{ route('admin.salary.sheet') }}"
               class="bg-gray-700 text-white px-4 py-2 rounded text-sm">
                Back to Salary Sheet
            </a>
        </div>
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

    {{-- ================= MEDICAL ALLOWANCE ================= --}}
    <div class="bg-white p-5 rounded shadow mb-6">
        <h3 class="font-semibold mb-3">Medical Allowance</h3>

        <form method="POST" action="{{ route('admin.salary.tax.medical') }}" class="flex gap-4 items-end flex-wrap">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1">Tax sheet divides salary by</label>
                <input type="number" step="0.01" min="1" max="5" name="divisor"
                       value="{{ $divisor }}" required
                       class="border px-3 py-2 rounded text-sm w-32">
            </div>

            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Save</button>

            <p class="text-xs text-gray-500 flex-1 min-w-64">
                Medical allowance is exempt, so the Tax Sheet divides Salary &amp; Wages by this
                figure to reach taxable income. 1.1 removes a 10% medical component.
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

    {{-- Forms sit outside the table; row inputs bind to them by id --}}
    @foreach($slabs as $slab)
    <form id="slabUpdate{{ $slab->id }}" method="POST"
          action="{{ route('admin.salary.tax.update', $slab->id) }}" class="hidden">
        @csrf
        @method('PUT')
    </form>

    <form id="slabDelete{{ $slab->id }}" method="POST"
          action="{{ route('admin.salary.tax.destroy', $slab->id) }}" class="hidden"
          onsubmit="return confirm('Remove this slab?');">
        @csrf
        @method('DELETE')
    </form>
    @endforeach

    <div class="bg-white rounded shadow overflow-x-auto mb-6">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-right">From (over)</th>
                    <th class="p-3 text-right">To (up to)</th>
                    <th class="p-3 text-right">Fixed amount</th>
                    <th class="p-3 text-right">Rate %</th>
                    <th class="p-3">Active</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>

            <tbody>

            @forelse($slabs as $slab)
            <tr class="border-t {{ $slab->is_active ? '' : 'bg-gray-50 text-gray-400' }}">

                <td class="p-2">
                    <input type="number" step="0.01" min="0" name="from_amount"
                           value="{{ $slab->from_amount }}" form="slabUpdate{{ $slab->id }}" required
                           class="border px-2 py-1 rounded w-32 text-sm text-right">
                </td>

                <td class="p-2">
                    <input type="number" step="0.01" min="0" name="to_amount"
                           value="{{ $slab->to_amount }}" form="slabUpdate{{ $slab->id }}"
                           placeholder="no limit"
                           class="border px-2 py-1 rounded w-32 text-sm text-right">
                </td>

                <td class="p-2">
                    <input type="number" step="0.01" min="0" name="fixed_amount"
                           value="{{ $slab->fixed_amount }}" form="slabUpdate{{ $slab->id }}" required
                           class="border px-2 py-1 rounded w-32 text-sm text-right">
                </td>

                <td class="p-2">
                    <input type="number" step="0.01" min="0" max="100" name="percentage"
                           value="{{ $slab->percentage }}" form="slabUpdate{{ $slab->id }}" required
                           class="border px-2 py-1 rounded w-24 text-sm text-right">
                </td>

                <td class="p-2 text-center">
                    <input type="checkbox" name="is_active" value="1"
                           form="slabUpdate{{ $slab->id }}"
                           {{ $slab->is_active ? 'checked' : '' }}>
                </td>

                <td class="p-2 whitespace-nowrap text-center">
                    <button form="slabUpdate{{ $slab->id }}"
                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs">Save</button>

                    <button form="slabDelete{{ $slab->id }}"
                            class="bg-red-500 text-white px-3 py-1 rounded text-xs">Delete</button>
                </td>

            </tr>
            @empty
            <tr>
                <td colspan="6" class="p-4 text-gray-500 text-center">
                    No slabs configured. Use <strong>Load FBR 2026&ndash;27</strong> above, or add one below.
                </td>
            </tr>
            @endforelse

            </tbody>
        </table>

    </div>

    @if($slabs->count())
    <p class="text-xs text-gray-500 mb-6">
        Bands read as: tax = fixed amount + (income &minus; From) &times; rate, for the band the income falls in.
        Leaving <em>To</em> blank makes it the top band.
    </p>
    @endif

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
