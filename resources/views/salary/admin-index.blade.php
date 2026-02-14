<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Salary Management
        </h2>

        <div class="flex gap-3">

            <a href="{{ route('admin.salary.export') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
                Export
            </a>

            <a href="{{ route('admin.salary.create') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                Add Salary
            </a>

            {{-- Post All Drafts --}}
            <form method="POST"
                  action="{{ route('admin.salary.post.all') }}">
                @csrf
                <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded shadow text-sm">
                    Post All Drafts
                </button>
            </form>

        </div>
    </div>


    {{-- Success / Error Messages --}}
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


    {{-- BULK FORM START --}}
    <form method="POST">
        @csrf

        {{-- Bulk Buttons --}}
        <div class="flex gap-3 mb-4">

            <button type="submit"
                    formaction="{{ route('admin.salary.bulk.post') }}"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm shadow">
                Bulk Post
            </button>

            <button type="submit"
                    formaction="{{ route('admin.salary.bulk.unpost') }}"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm shadow">
                Bulk Unpost
            </button>

            <button type="submit"
                    formaction="{{ route('admin.salary.bulk.delete') }}"
                    onclick="return confirm('Delete selected salaries?')"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm shadow">
                Bulk Delete
            </button>

        </div>


        {{-- Salary Table --}}
        <div class="bg-white shadow rounded overflow-hidden">

            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">
                            <input type="checkbox" id="selectAll">
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
                                      onsubmit="return confirm('Delete this salary?')">
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
                                        <button class="text-green-600 hover:underline">
                                            Post
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.salary.unpost', $salary->id) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        <button class="text-gray-600 hover:underline">
                                            Unpost
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
    {{-- BULK FORM END --}}

</div>


{{-- Select All Script --}}
<script>
document.getElementById('selectAll').addEventListener('click', function() {
    let checkboxes = document.querySelectorAll('input[name="salary_ids[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

</x-app-layout>
