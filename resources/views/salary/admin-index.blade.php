<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Salary Management</h2>

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


    {{-- BULK ACTIONS FORM --}}
    <form id="bulkForm" method="POST">
        @csrf

        <div class="flex gap-3 mb-4">
            <button formaction="{{ route('admin.salary.bulk.post') }}"
                    class="bg-green-600 text-white px-4 py-2 rounded">
                Bulk Post
            </button>

            <button formaction="{{ route('admin.salary.bulk.unpost') }}"
                    class="bg-gray-600 text-white px-4 py-2 rounded">
                Bulk Unpost
            </button>

            <button formaction="{{ route('admin.salary.bulk.delete') }}"
                    onclick="return confirm('Delete selected salaries?')"
                    class="bg-red-600 text-white px-4 py-2 rounded">
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
                @foreach($salaries as $salary)
                    <tr class="border-t">
                        <td class="p-3">
                            <input type="checkbox"
                                   name="salary_ids[]"
                                   value="{{ $salary->id }}">
                        </td>

                        <td class="p-3">{{ $salary->user->name ?? 'N/A' }}</td>
                        <td class="p-3">{{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}</td>
                        <td class="p-3">{{ $salary->year }}</td>
                        <td class="p-3 text-green-700 font-semibold">
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

                        <td class="p-3">

                            {{-- INDIVIDUAL DELETE FORM (OUTSIDE BULK FORM) --}}
                            <form method="POST"
                                  action="{{ route('admin.salary.delete', $salary->id) }}"
                                  style="display:inline;"
                                  onsubmit="return confirm('Delete this salary?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600">Delete</button>
                            </form>

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
