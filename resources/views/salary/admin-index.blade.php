<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-start mb-6">

        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                Salary Management
            </h2>

            {{-- FILTER FORM --}}
            <form method="GET" class="flex gap-3 mt-4">

                <select name="employee" class="border px-3 py-2 rounded text-sm">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                            {{ request('employee') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>

                <select name="month" class="border px-3 py-2 rounded text-sm">
                    <option value="">All Months</option>
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}"
                            {{ request('month') == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                    @endfor
                </select>

                <input type="number"
                       name="year"
                       value="{{ request('year') }}"
                       placeholder="Year"
                       class="border px-3 py-2 rounded text-sm">

                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
                    Filter
                </button>
            </form>
        </div>

        {{-- ACTION BUTTONS --}}
        <div class="flex gap-3">
            <a href="{{ route('admin.salary.sample') }}"
               class="bg-gray-700 text-white px-4 py-2 rounded text-sm">
                Download Sample
            </a>

            <a href="{{ route('admin.salary.export') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
                Export
            </a>

            <a href="{{ route('admin.salary.create') }}"
               class="bg-green-600 text-white px-4 py-2 rounded text-sm">
                Add Salary
            </a>
        </div>
    </div>

    {{-- ================= IMPORT ================= --}}
    <div class="mb-6">
        <form action="{{ route('admin.salary.import') }}"
              method="POST"
              enctype="multipart/form-data"
              class="flex gap-3">
            @csrf
            <input type="file" name="file" required
                   class="border px-3 py-2 rounded text-sm">
            <button type="submit"
                    class="bg-green-500 text-white px-4 py-2 rounded text-sm">
                Import
            </button>
        </form>
    </div>

    {{-- ================= ALERTS ================= --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif


    {{-- ================= BULK FORM (ONLY BUTTONS) ================= --}}
    <form method="POST" id="bulkForm">
        @csrf

        <div class="flex gap-3 mb-4">
            <button formaction="{{ route('admin.salary.bulk.post') }}"
                    class="bg-green-600 text-white px-4 py-2 rounded text-sm">
                Bulk Post
            </button>

            <button formaction="{{ route('admin.salary.bulk.unpost') }}"
                    class="bg-gray-600 text-white px-4 py-2 rounded text-sm">
                Bulk Unpost
            </button>

            <button formaction="{{ route('admin.salary.bulk.delete') }}"
                    onclick="return confirm('Delete selected salaries?')"
                    class="bg-red-600 text-white px-4 py-2 rounded text-sm">
                Bulk Delete
            </button>
        </div>
    </form>


    {{-- ================= TABLE ================= --}}
    <div class="bg-white shadow rounded overflow-hidden">
        <table class="w-full text-sm">

            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3">
                        <input type="checkbox" onclick="toggleAll(this)">
                    </th>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Month</th>
                    <th class="p-3 text-left">Year</th>
                    <th class="p-3 text-left">Net Salary</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>
            @forelse($salaries as $salary)
                <tr class="border-t hover:bg-gray-50">

                    {{-- CHECKBOX (Linked to bulkForm) --}}
                    <td class="p-3">
                        <input type="checkbox"
                               name="salary_ids[]"
                               value="{{ $salary->id }}"
                               form="bulkForm">
                    </td>

                    <td class="p-3">
                        {{ $salary->user->name ?? 'N/A' }}
                    </td>

                    <td class="p-3">
                        {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}
                    </td>

                    <td class="p-3">
                        {{ $salary->year }}
                    </td>

                    <td class="p-3 font-semibold text-green-700">
                        Rs {{ number_format($salary->net_salary ?? 0, 2) }}
                    </td>

                    <td class="p-3">
                        @if($salary->is_posted)
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                                Posted
                            </span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">
                                Draft
                            </span>
                        @endif
                    </td>

                    <td class="p-3">
                        <div class="flex gap-3">

                            <a href="{{ route('admin.salary.show', $salary->id) }}"
                               class="text-blue-600 hover:underline">
                                View
                            </a>

                            <a href="{{ route('admin.salary.edit', $salary->id) }}"
                               class="text-yellow-600 hover:underline">
                                Edit
                            </a>

                            {{-- INDIVIDUAL DELETE --}}
                            <form action="{{ route('admin.salary.delete', $salary->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this salary?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">
                                    Delete
                                </button>
                            </form>

                            {{-- POST / UNPOST --}}
                            @if($salary->is_posted)
                                <form action="{{ route('admin.salary.unpost', $salary->id) }}"
                                      method="POST">
                                    @csrf
                                    <button class="text-gray-600 hover:underline">
                                        Unpost
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.salary.post', $salary->id) }}"
                                      method="POST">
                                    @csrf
                                    <button class="text-green-600 hover:underline">
                                        Post
                                    </button>
                                </form>
                            @endif

                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="7"
                        class="text-center p-6 text-gray-500">
                        No salaries found.
                    </td>
                </tr>
            @endforelse
            </tbody>

        </table>
    </div>

</div>

<script>
function toggleAll(source) {
    let checkboxes = document.querySelectorAll('input[name="salary_ids[]"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}
</script>

</x-app-layout>
