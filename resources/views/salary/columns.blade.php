<x-app-layout>

<div class="max-w-5xl mx-auto py-8 px-4">

    <div class="flex justify-between items-start mb-6 flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Salary Sheet Columns</h2>
            <p class="text-sm text-gray-500 mt-1">
                Add your own columns to the salary sheet. Earnings add to Total Addition,
                deductions add to Total Deduction.
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

    @if($errors->any())
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ $errors->first() }}</div>
    @endif

    {{-- ================= SHEET HEADER ================= --}}
    <div class="bg-white p-5 rounded shadow mb-6">
        <h3 class="font-semibold mb-3">Sheet Header</h3>

        <form method="POST" action="{{ route('admin.salary.header.update') }}" class="flex gap-3 items-end flex-wrap">
            @csrf
            <div class="flex-1 min-w-64">
                <label class="block text-xs text-gray-500 mb-1">Organisation name (printed on the sheets)</label>
                <input type="text" name="org_name" value="{{ $orgName }}"
                       class="w-full border px-3 py-2 rounded text-sm" required>
            </div>
            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Save Header</button>
        </form>
    </div>

    {{-- ================= ADD COLUMN ================= --}}
    <div class="bg-white p-5 rounded shadow mb-6">
        <h3 class="font-semibold mb-3">Add Column</h3>

        <form method="POST" action="{{ route('admin.salary.columns.store') }}"
              class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
            @csrf

            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Column name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       placeholder="e.g. Conveyance, Bonus, Fine"
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Type</label>
                <select name="type" class="w-full border px-3 py-2 rounded text-sm">
                    <option value="earning">Earning (+)</option>
                    <option value="deduction">Deduction (-)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Show on</label>
                <select name="applies_to" class="w-full border px-3 py-2 rounded text-sm">
                    <option value="both">Both sheets</option>
                    <option value="teacher">Teachers only</option>
                    <option value="staff">Staff only</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">Order</label>
                <input type="number" name="sort_order" value="0"
                       class="w-full border px-3 py-2 rounded text-sm">
            </div>

            <div class="md:col-span-5">
                <button class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded text-sm">
                    Add Column
                </button>
            </div>
        </form>
    </div>

    {{-- ================= EXISTING COLUMNS ================= --}}

    {{-- Forms live outside the table; the row inputs bind to them by id --}}
    @foreach($columns as $col)
    <form id="colUpdate{{ $col->id }}" method="POST"
          action="{{ route('admin.salary.columns.update', $col->id) }}" class="hidden">
        @csrf
        @method('PUT')
    </form>

    <form id="colDelete{{ $col->id }}" method="POST"
          action="{{ route('admin.salary.columns.destroy', $col->id) }}" class="hidden"
          onsubmit="return confirm('Remove this column?');">
        @csrf
        @method('DELETE')
    </form>
    @endforeach

    <div class="bg-white rounded shadow overflow-x-auto">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Shows on</th>
                    <th class="p-3">Order</th>
                    <th class="p-3">Active</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>

            <tbody>

            @forelse($columns as $col)
            <tr class="border-t {{ $col->is_active ? '' : 'bg-gray-50 text-gray-400' }}">

                <td class="p-2">
                    <input type="text" name="name" value="{{ $col->name }}"
                           form="colUpdate{{ $col->id }}"
                           class="border px-2 py-1 rounded w-full text-sm" required>
                </td>

                <td class="p-2">
                    <select name="type" form="colUpdate{{ $col->id }}"
                            class="border px-2 py-1 rounded text-sm">
                        <option value="earning" {{ $col->type === 'earning' ? 'selected' : '' }}>Earning (+)</option>
                        <option value="deduction" {{ $col->type === 'deduction' ? 'selected' : '' }}>Deduction (-)</option>
                    </select>
                </td>

                <td class="p-2">
                    <select name="applies_to" form="colUpdate{{ $col->id }}"
                            class="border px-2 py-1 rounded text-sm">
                        <option value="both" {{ $col->applies_to === 'both' ? 'selected' : '' }}>Both</option>
                        <option value="teacher" {{ $col->applies_to === 'teacher' ? 'selected' : '' }}>Teachers</option>
                        <option value="staff" {{ $col->applies_to === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                </td>

                <td class="p-2">
                    <input type="number" name="sort_order" value="{{ $col->sort_order }}"
                           form="colUpdate{{ $col->id }}"
                           class="border px-2 py-1 rounded w-20 text-sm">
                </td>

                <td class="p-2 text-center">
                    <input type="checkbox" name="is_active" value="1"
                           form="colUpdate{{ $col->id }}"
                           {{ $col->is_active ? 'checked' : '' }}>
                </td>

                <td class="p-2 whitespace-nowrap">
                    <button form="colUpdate{{ $col->id }}"
                            class="bg-blue-600 text-white px-3 py-1 rounded text-xs">Save</button>

                    <button form="colDelete{{ $col->id }}"
                            class="bg-red-500 text-white px-3 py-1 rounded text-xs">Delete</button>
                </td>

            </tr>
            @empty
            <tr>
                <td colspan="6" class="p-4 text-gray-500 text-center">
                    No extra columns yet. The sheet shows the standard columns only.
                </td>
            </tr>
            @endforelse

            </tbody>
        </table>

    </div>

</div>

</x-app-layout>
