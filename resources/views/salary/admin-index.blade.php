<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Salary Management
        </h2>

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

            <form action="{{ route('admin.salary.post.all') }}" method="POST">
                @csrf
                <button class="bg-purple-600 text-white px-4 py-2 rounded text-sm">
                    Post All Drafts
                </button>
            </form>
        </div>
    </div>

    {{-- IMPORT --}}
    <div class="mb-6">
        <form action="{{ route('admin.salary.import') }}"
              method="POST"
              enctype="multipart/form-data"
              class="flex gap-3">
            @csrf
            <input type="file" name="file" required
                   class="border px-3 py-2 rounded text-sm">
            <button class="bg-green-500 text-white px-4 py-2 rounded text-sm">
                Import
            </button>
        </form>
    </div>

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


    {{-- BULK FORM --}}
    <form action="{{ route('admin.salary.bulk.post') }}" method="POST">
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

        <div class="bg-white shadow rounded overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3">
                            <input type="checkbox" onclick="toggleAll(this)">
                        </th>
                        <th class="p-3">Employee</th>
                        <th class="p-3">Month</th>
                        <th class="p-3">Year</th>
                        <th class="p-3">Net Salary</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($salaries as $salary)
                    <tr class="border-t">
                        <td class="p-3">
                            <input type="checkbox"
                                   name="salary_ids[]"
                                   value="{{ $salary->id }}">
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

                        <td class="p-3 text-green-700 font-semibold">
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

                        <td class="p-3 flex gap-3">

                            <a href="{{ route('admin.salary.show', $salary->id) }}"
                               class="text-blue-600 hover:underline">
                                View
                            </a>

                            <a href="{{ route('admin.salary.edit', $salary->id) }}"
                               class="text-yellow-600 hover:underline">
                                Edit
                            </a>

                            {{-- DELETE --}}
                            <form action="{{ route('admin.salary.delete', $salary->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this salary?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">
                                    Delete
                                </button>
                            </form>

                            @if($salary->is_posted)
                                <form action="{{ route('admin.salary.unpost', $salary->id) }}" method="POST">
                                    @csrf
                                    <button class="text-gray-600 hover:underline">
                                        Unpost
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.salary.post', $salary->id) }}" method="POST">
                                    @csrf
                                    <button class="text-green-600 hover:underline">
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

<script>
function toggleAll(source) {
    let checkboxes = document.getElementsByName('salary_ids[]');
    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}
</script>

</x-app-layout>
