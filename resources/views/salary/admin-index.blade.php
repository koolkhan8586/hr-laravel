<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Salary Management
        </h2>

        <div class="flex gap-3">

            <a href="{{ route('admin.salary.export') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
                Export
            </a>

            <a href="{{ route('admin.salary.import.form') }}"
               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded shadow text-sm">
                Import
            </a>

            <a href="{{ route('admin.salary.create') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                Add Salary
            </a>

        </div>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-4 gap-6 mb-8">

        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Total Salaries</p>
            <p class="text-2xl font-bold">
                {{ $salaries->count() }}
            </p>
        </div>

        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Total Net Paid</p>
            <p class="text-2xl font-bold text-green-600">
                Rs {{ number_format($salaries->where('is_posted', true)->sum('net_salary'),2) }}
            </p>
        </div>

        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Total Deductions</p>
            <p class="text-2xl font-bold text-red-600">
                Rs {{ number_format($salaries->sum('total_deductions'),2) }}
            </p>
        </div>

        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Draft Salaries</p>
            <p class="text-2xl font-bold text-yellow-600">
                {{ $salaries->where('is_posted', false)->count() }}
            </p>
        </div>

    </div>


    {{-- BULK ACTION FORM --}}
    <form method="POST">
        @csrf

        <div class="mb-4 flex flex-wrap gap-3">

            <button formaction="{{ route('admin.salary.bulk.post') }}"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                Bulk Post
            </button>

            <button formaction="{{ route('admin.salary.bulk.unpost') }}"
                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded text-sm">
                Bulk Unpost
            </button>

            <button formaction="{{ route('admin.salary.bulk.delete') }}"
                onclick="return confirm('Delete selected salaries?')"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                Bulk Delete
            </button>

            <button formaction="{{ route('admin.salary.post.all') }}"
                onclick="return confirm('Post ALL draft salaries?')"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm">
                Post All Drafts
            </button>

        </div>


        {{-- SALARY TABLE --}}
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
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($salaries as $salary)
                        <tr class="border-t hover:bg-gray-50">

                            <td class="p-3">
                                <input type="checkbox"
                                       name="salary_ids[]"
                                       value="{{ $salary->id }}">
                            </td>

                            <td class="p-3">
                                {{ $salary->user->name ?? 'N/A' }}
                            </td>

                            <td class="p-3">
                                {{ date('F', mktime(0,0,0,$salary->month,1)) }}
                            </td>

                            <td class="p-3">
                                {{ $salary->year }}
                            </td>

                            <td class="p-3 text-green-600 font-semibold">
                                Rs {{ number_format($salary->net_salary,2) }}
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

                            <td class="p-3 space-x-2">

                                <a href="{{ route('admin.salary.show', $salary->id) }}"
                                   class="text-blue-600 hover:underline">
                                    View
                                </a>

                                <a href="{{ route('admin.salary.edit', $salary->id) }}"
                                   class="text-yellow-600 hover:underline">
                                    Edit
                                </a>

                                <form action="{{ route('admin.salary.delete', $salary->id) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Are you sure?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline">
                                        Delete
                                    </button>
                                </form>

                                @if(!$salary->is_posted)
                                    <form action="{{ route('admin.salary.post', $salary->id) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        <button class="text-green-600 font-semibold hover:underline">
                                            Post
                                        </button>
                                    </form>
                                @endif

                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-6 text-gray-500">
                                No salaries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>

    </form>

</div>

{{-- Select All Script --}}
<script>
function toggleAll(source) {
    let checkboxes = document.querySelectorAll('input[name="salary_ids[]"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}
</script>

</x-app-layout>
