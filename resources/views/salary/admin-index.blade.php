<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Salary Management
        </h2>

        <div class="space-x-2">

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

    {{-- ================= SUCCESS MESSAGE ================= --}}
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


    {{-- ================= SUMMARY CARDS ================= --}}
    <div class="grid grid-cols-4 gap-6 mb-8">

        <div class="bg-white p-5 rounded shadow">
            <p class="text-gray-500 text-sm">Total Salaries</p>
            <h3 class="text-xl font-bold">
                {{ $totalSalaries }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded shadow">
            <p class="text-gray-500 text-sm">Total Net Paid</p>
            <h3 class="text-xl font-bold text-green-600">
                Rs {{ number_format($totalNet,2) }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded shadow">
            <p class="text-gray-500 text-sm">Total Deductions</p>
            <h3 class="text-xl font-bold text-red-600">
                Rs {{ number_format($totalDeductions,2) }}
            </h3>
        </div>

        <div class="bg-white p-5 rounded shadow">
            <p class="text-gray-500 text-sm">Draft Salaries</p>
            <h3 class="text-xl font-bold text-yellow-600">
                {{ $draftCount }}
            </h3>
        </div>

    </div>


    {{-- ================= FILTERS ================= --}}
    <form method="GET"
          class="bg-white p-4 rounded shadow mb-6 flex space-x-4">

        <select name="month"
                class="border rounded px-3 py-2 text-sm">
            <option value="">Month</option>
            @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}"
                    {{ request('month') == $m ? 'selected' : '' }}>
                    {{ $m }}
                </option>
            @endfor
        </select>

        <select name="year"
                class="border rounded px-3 py-2 text-sm">
            <option value="">Year</option>
            @for($y=2025;$y<=2035;$y++)
                <option value="{{ $y }}"
                    {{ request('year') == $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endfor
        </select>

        <select name="employee"
                class="border rounded px-3 py-2 text-sm">
            <option value="">Employee</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}"
                    {{ request('employee') == $emp->id ? 'selected' : '' }}>
                    {{ $emp->name }}
                </option>
            @endforeach
        </select>

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
            Filter
        </button>

    </form>


    {{-- ================= SALARY TABLE ================= --}}
    <div class="bg-white rounded shadow overflow-hidden">

        <table class="w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Month</th>
                    <th class="px-4 py-3">Year</th>
                    <th class="px-4 py-3">Net Salary</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y">

                @forelse($salaries as $salary)

                    <tr class="hover:bg-gray-50">

                        <td class="px-4 py-3">
                            {{ $salary->user->name }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $salary->month }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $salary->year }}
                        </td>

                        <td class="px-4 py-3 font-semibold text-green-600">
                            Rs {{ number_format($salary->net_salary,2) }}
                        </td>

                        <td class="px-4 py-3">
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

                        <td class="px-4 py-3 space-x-2">

                            <!-- View -->
                            <a href="{{ route('admin.salary.show', $salary->id) }}"
                               class="text-blue-600 hover:underline text-sm">
                                View
                            </a>

                            <!-- Edit -->
                            <a href="{{ route('admin.salary.edit', $salary->id) }}"
                               class="text-yellow-600 hover:underline text-sm">
                                Edit
                            </a>

                            <!-- Delete -->
                            <form action="{{ route('admin.salary.delete', $salary->id) }}"
                                  method="POST"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline text-sm"
                                        onclick="return confirm('Delete this salary?')">
                                    Delete
                                </button>
                            </form>

                            <!-- Post -->
                            @if(!$salary->is_posted)
                                <form action="{{ route('admin.salary.post', $salary->id) }}"
                                      method="POST"
                                      class="inline">
                                    @csrf
                                    <button class="text-green-600 hover:underline text-sm">
                                        Post
                                    </button>
                                </form>
                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6"
                            class="text-center py-6 text-gray-500">
                            No salaries found.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

</x-app-layout>
