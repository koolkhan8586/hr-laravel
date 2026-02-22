<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Salary Management
        </h2>

        <div class="flex gap-3">
            <a href="{{ route('admin.salary.create') }}"
               class="bg-green-600 text-white px-4 py-2 rounded shadow text-sm">
                Add Salary
            </a>
        </div>
    </div>

    {{-- SUCCESS / ERROR MESSAGE --}}
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


    {{-- ================= BULK FORM ================= --}}
    <form method="POST">
        @csrf

        <div class="flex gap-3 mb-4">
            <button type="submit"
                    formaction="{{ route('admin.salary.bulk.post') }}"
                    class="bg-green-600 text-white px-3 py-2 rounded text-sm">
                Bulk Post
            </button>

            <button type="submit"
                    formaction="{{ route('admin.salary.bulk.unpost') }}"
                    class="bg-gray-600 text-white px-3 py-2 rounded text-sm">
                Bulk Unpost
            </button>

            <button type="submit"
                    formaction="{{ route('admin.salary.bulk.delete') }}"
                    onclick="return confirm('Are you sure you want to delete selected salaries?')"
                    class="bg-red-600 text-white px-3 py-2 rounded text-sm">
                Bulk Delete
            </button>
        </div>

        <div class="flex gap-2 mb-4">
    <a href="{{ route('admin.salary.export') }}"
       class="bg-green-600 text-white px-4 py-2 rounded">
        Export Salary
    </a>

    <a href="{{ route('admin.salary.import.form') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded">
        Import Salary
    </a>

    <a href="{{ route('admin.salary.sample') }}"
       class="bg-gray-600 text-white px-4 py-2 rounded">
        Download Sample
    </a>
</div>


        {{-- ================= TABLE ================= --}}
        <div class="bg-white shadow rounded overflow-hidden">
            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3 text-center">
                            <input type="checkbox" onclick="toggleAll(this)">
                        </th>
                        <th class="p-3 text-left">Employee</th>
                        <th class="p-3 text-left">Month</th>
                        <th class="p-3 text-left">Year</th>
                        <th class="p-3 text-left">Net Salary</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($salaries as $salary)
                        <tr class="border-t hover:bg-gray-50">

                            {{-- CHECKBOX --}}
                            <td class="p-3 text-center">
                                <input type="checkbox"
                                       name="selected_ids[]"
                                       value="{{ $salary->id }}">
                            </td>

                            {{-- EMPLOYEE --}}
                            <td class="p-3">
                                {{ $salary->user->name ?? '-' }}
                            </td>

                            {{-- MONTH --}}
                            <td class="p-3">
                                {{ date('F', mktime(0, 0, 0, $salary->month, 10)) }}
                            </td>

                            {{-- YEAR --}}
                            <td class="p-3">
                                {{ $salary->year }}
                            </td>

                            {{-- NET SALARY --}}
                            <td class="p-3 font-semibold text-green-700">
                                Rs {{ number_format($salary->net_salary, 2) }}
                            </td>

                            {{-- STATUS --}}
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

                            {{-- ACTIONS --}}
                            <td class="p-3 text-center">
                                <div class="flex justify-center gap-2">

                                    {{-- View --}}
                                    <a href="{{ route('admin.salary.show', $salary->id) }}"
                                       class="text-blue-600 text-sm">
                                        View
                                    </a>

                                    {{-- Edit --}}
                                    <a href="{{ route('admin.salary.edit', $salary->id) }}"
                                       class="text-yellow-600 text-sm">
                                        Edit
                                    </a>

                                    {{-- Delete --}}
                                    <form action="{{ route('admin.salary.delete', $salary->id) }}"
                                          method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Delete this salary?')"
                                                class="text-red-600 text-sm">
                                            Delete
                                        </button>
                                    </form>

                                    {{-- Post / Unpost --}}
                                    @if(!$salary->is_posted)
                                        <form action="{{ route('admin.salary.post', $salary->id) }}"
                                              method="POST">
                                            @csrf
                                            <button class="text-green-600 text-sm">
                                                Post
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.salary.unpost', $salary->id) }}"
                                              method="POST">
                                            @csrf
                                            <button class="text-gray-600 text-sm">
                                                Unpost
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-6 text-gray-500">
                                No salary records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </form>

</div>

{{-- SELECT ALL SCRIPT --}}
<script>
function toggleAll(source) {
    checkboxes = document.getElementsByName('selected_ids[]');
    for(var i=0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

</x-app-layout>
