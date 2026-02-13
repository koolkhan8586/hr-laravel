<x-app-layout>
<div class="max-w-7xl mx-auto px-6 py-8">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Salary Management</h2>
        <a href="{{ route('admin.salary.create') }}"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
            Add Salary
        </a>
    </div>

    {{-- SUCCESS / ERROR --}}
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
            <p class="text-gray-500 text-sm">Total Posted</p>
            <h3 class="text-xl font-bold">{{ $totalPosted }}</h3>
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
    <form method="GET" class="bg-white p-4 rounded shadow mb-6 flex space-x-4">

        <select name="month" class="border rounded px-3 py-2">
            <option value="">Month</option>
            @for($m=1; $m<=12; $m++)
                <option value="{{ $m }}" {{ request('month')==$m?'selected':'' }}>
                    {{ $m }}
                </option>
            @endfor
        </select>

        <select name="year" class="border rounded px-3 py-2">
            <option value="">Year</option>
            @for($y=2024; $y<=2030; $y++)
                <option value="{{ $y }}" {{ request('year')==$y?'selected':'' }}>
                    {{ $y }}
                </option>
            @endfor
        </select>

        <select name="employee" class="border rounded px-3 py-2">
            <option value="">Employee</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}"
                    {{ request('employee')==$emp->id?'selected':'' }}>
                    {{ $emp->name }}
                </option>
            @endforeach
        </select>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Filter
        </button>

    </form>


    {{-- ================= TABLE ================= --}}
    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full text-sm">

            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
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
                    <tr class="border-t hover:bg-gray-50">

                        <td class="p-3 font-medium">
                            {{ $salary->user->name }}
                        </td>

                        <td class="p-3">
                            {{ $salary->month }}
                        </td>

                        <td class="p-3">
                            {{ $salary->year }}
                        </td>

                        <td class="p-3 font-bold text-green-700">
                            Rs {{ number_format($salary->net_salary,2) }}
                        </td>

                        <td class="p-3">
                            @if($salary->is_posted)
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">
                                    Posted
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full">
                                    Draft
                                </span>
                            @endif
                        </td>

                        <td class="p-3">
                            <a href="{{ route('admin.salary.show', $salary->id) }}"
                               class="text-blue-600 hover:underline">
                                View
                            </a>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500">
                            No salary records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>
</x-app-layout>
