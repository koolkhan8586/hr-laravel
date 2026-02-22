<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Salary Management</h2>

        <div class="flex gap-3 items-center">

    <a href="{{ route('admin.salary.sample') }}"
       class="bg-gray-700 text-white px-4 py-2 rounded text-sm">
        Download Sample
    </a>

    {{-- IMPORT --}}
    <form action="{{ route('admin.salary.import') }}"
          method="POST"
          enctype="multipart/form-data"
          class="flex items-center gap-2">
        @csrf
        <input type="file"
               name="file"
               required
               class="border px-2 py-1 rounded text-sm">

        <button type="submit"
                class="bg-green-600 text-white px-4 py-2 rounded text-sm">
            Import
        </button>
    </form>

    <a href="{{ route('admin.salary.export') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
        Export
    </a>

    <a href="{{ route('admin.salary.create') }}"
       class="bg-green-700 text-white px-4 py-2 rounded text-sm">
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

    {{-- MESSAGES --}}
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


    {{-- BULK ACTION FORM --}}
    <form action="{{ route('admin.salary.bulk.post') }}"
          method="POST"
          id="bulkForm">
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
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Month</th>
                    <th class="p-3 text-left">Year</th>
                    <th class="p-3 text-left">Net Salary</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
                </thead>

                <tbody>
                @foreach($salaries as $salary)
                    <tr class="border-t hover:bg-gray-50">

                        {{-- Checkbox --}}
                        <td class="p-3">
                            <input type="checkbox"
                                   name="salary_ids[]"
                                   value="{{ $salary->id }}">
                        </td>

                        {{-- Employee --}}
                        <td class="p-3">
                            {{ $salary->user->name ?? 'N/A' }}
                        </td>

                        {{-- Month --}}
                        <td class="p-3">
                            {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}
                        </td>

                        {{-- Year --}}
                        <td class="p-3">
                            {{ $salary->year }}
                        </td>

                        {{-- Net --}}
                        <td class="p-3 font-semibold text-green-700">
                            Rs {{ number_format($salary->net_salary,2) }}
                        </td>

                        {{-- Status --}}
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

                        {{-- ACTIONS (NO NESTED FORM!) --}}
                        <td class="p-3 text-sm">

                            <a href="{{ route('admin.salary.show', $salary->id) }}"
                               class="text-blue-600 hover:underline mr-2">
                                View
                            </a>

                            <a href="{{ route('admin.salary.edit', $salary->id) }}"
                               class="text-yellow-600 hover:underline mr-2">
                                Edit
                            </a>

                            {{-- DELETE FORM (separate form, NOT wrapping table) --}}
                            <form action="{{ route('admin.salary.delete', $salary->id) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('Delete this salary?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-600 hover:underline mr-2">
                                    Delete
                                </button>
                            </form>

                            {{-- POST / UNPOST --}}
                            @if($salary->is_posted)
                                <form action="{{ route('admin.salary.unpost', $salary->id) }}"
                                      method="POST"
                                      class="inline">
                                    @csrf
                                    <button class="text-gray-600 hover:underline">
                                        Unpost
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.salary.post', $salary->id) }}"
                                      method="POST"
                                      class="inline">
                                    @csrf
                                    <button class="text-green-600 hover:underline">
                                        Post
                                    </button>
                                </form>
                            @endif

                        </td>

                    </tr>
                @endforeach
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
