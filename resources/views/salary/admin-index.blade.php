<x-app-layout>
<div class="max-w-7xl mx-auto py-8 px-4">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Salary Management</h2>

        <a href="{{ route('admin.salary.create') }}"
           class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700">
            Add Salary
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
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
                <tr class="border-t">
                    <td class="p-3">{{ $salary->user->name }}</td>
                    <td class="p-3">{{ $salary->month }}</td>
                    <td class="p-3">{{ $salary->year }}</td>
                    <td class="p-3 font-semibold">
                        Rs {{ number_format($salary->net_salary, 2) }}
                    </td>
                    <td class="p-3">
                        @if($salary->is_posted)
                            <span class="text-green-600 font-semibold">Posted</span>
                        @else
                            <span class="text-yellow-600 font-semibold">Draft</span>
                        @endif
                    </td>
                    <td class="p-3 space-x-2">
                        <a href="{{ route('admin.salary.show', $salary->id) }}"
                           class="text-blue-600">View</a>

                        @if(!$salary->is_posted)
                        <form action="{{ route('admin.salary.post', $salary->id) }}"
                              method="POST"
                              class="inline">
                            @csrf
                            <button type="submit"
                                    class="text-green-600">
                                Post
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-500">
                        No salaries created yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-app-layout>
