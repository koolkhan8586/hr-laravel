<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center mb-6">

        <h2 class="text-2xl font-bold text-gray-800">
            Salary Management
        </h2>

        <div class="flex items-center gap-3">

            {{-- Download Sample --}}
            <a href="{{ route('admin.salary.sample') }}"
               class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded text-sm shadow">
                Download Sample
            </a>

            {{-- Import Salary --}}
            <form action="{{ route('admin.salary.import') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="flex items-center gap-2">
                @csrf
                <input type="file"
                       name="file"
                       required
                       class="border rounded px-2 py-1 text-sm">

                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm shadow">
                    Import
                </button>
            </form>

            {{-- Export --}}
            <a href="{{ route('admin.salary.export') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm shadow">
                Export
            </a>

            {{-- Add Salary --}}
            <a href="{{ route('admin.salary.create') }}"
               class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded text-sm shadow">
                Add Salary
            </a>

            {{-- Post All Drafts --}}
            <form action="{{ route('admin.salary.post.all') }}"
                  method="POST">
                @csrf
                <button type="submit"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm shadow">
                    Post All Drafts
                </button>
            </form>

        </div>
    </div>


    {{-- ================= SUCCESS / ERROR ================= --}}
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


    {{-- ================= BULK ACTIONS ================= --}}
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
                    class="bg-red-600 text-white px-4 py-2 rounded text-sm"
                    onclick="return confirm('Delete selected salaries?')">
                Bulk Delete
            </button>
        </div>


        {{-- ================= TABLE ================= --}}
        <div class="bg-white shadow rounded overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3 text-center">
                            <input type="checkbox" id="selectAll">
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

                            <td class="p-3 text-center">
                                <input type="checkbox"
                                       name="ids[]"
                                       value="{{ $salary->id }}">
                            </td>

                            <td class="p-3">
                                {{ $salary->user->name ?? '-' }}
                            </td>

                            <td class="p-3">
                                {{ $salary->month }}
                            </td>

                            <td class="p-3">
                                {{ $salary->year }}
                            </td>

                            <td class="p-3 font-semibold text-green-700">
                                Rs {{ number_format($salary->net_salary,2) }}
                            </td>

                            <td class="p-3">
                                @if($salary->status == 'draft')
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">
                                        Draft
                                    </span>
                                @else
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                                        Posted
                                    </span>
                                @endif
                            </td>

                            <td class="p-3 text-center">

                                <div class="flex justify-center gap-2">

                                    <a href="{{ route('admin.salary.show', $salary->id) }}"
                                       class="text-blue-600 text-sm">
                                        View
                                    </a>

                                    <a href="{{ route('admin.salary.edit', $salary->id) }}"
                                       class="text-yellow-600 text-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.salary.delete', $salary->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Delete this salary?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-red-600 text-sm">
                                            Delete
                                        </button>
                                    </form>

                                    @if($salary->status == 'draft')
                                        <form action="{{ route('admin.salary.post', $salary->id) }}"
                                              method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="text-green-600 text-sm">
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

    </form>

</div>

{{-- Select All Script --}}
<script>
document.getElementById('selectAll').addEventListener('click', function(){
    let checkboxes = document.querySelectorAll('input[name="ids[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

</x-app-layout>
