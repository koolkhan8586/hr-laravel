<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-xl sm:text-2xl font-bold mb-6">My Salary Slips</h2>

    @if($salaries->count() == 0)
        <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 p-4 rounded">
            No salary records available.
        </div>
    @else

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-6">

        <div class="bg-white shadow rounded-lg p-4 sm:p-5">
            <p class="text-gray-500 text-xs sm:text-sm">Total Slips</p>
            <h3 class="text-xl sm:text-2xl font-bold">
                {{ $salaries->count() }}
            </h3>
        </div>

        <div class="bg-white shadow rounded-lg p-4 sm:p-5">
            <p class="text-gray-500 text-xs sm:text-sm">Latest Salary</p>
            <h3 class="text-xl sm:text-2xl font-bold text-green-600">
                Rs {{ number_format($salaries->first()->net_salary, 2) }}
            </h3>
        </div>

        <div class="bg-white shadow rounded-lg p-4 sm:p-5">
            <p class="text-gray-500 text-xs sm:text-sm">Year</p>
            <h3 class="text-xl sm:text-2xl font-bold">
                {{ $salaries->first()->year }}
            </h3>
        </div>

    </div>

    <!-- Responsive Salary Table -->
    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full text-xs sm:text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-2 sm:p-3 text-left">Month</th>
                    <th class="p-2 sm:p-3 text-left">Year</th>
                    <th class="p-2 sm:p-3 text-left">Gross</th>
                    <th class="p-2 sm:p-3 text-left">Deductions</th>
                    <th class="p-2 sm:p-3 text-left">Net Salary</th>
                    <th class="p-2 sm:p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach($salaries as $salary)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-2 sm:p-3">
                        {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}
                    </td>

                    <td class="p-2 sm:p-3">
                        {{ $salary->year }}
                    </td>

                    <td class="p-2 sm:p-3">
                        Rs {{ number_format($salary->gross_total, 2) }}
                    </td>

                    <td class="p-2 sm:p-3 text-red-600">
                        Rs {{ number_format($salary->total_deductions, 2) }}
                    </td>

                    <td class="p-2 sm:p-3 font-semibold text-green-600">
                        Rs {{ number_format($salary->net_salary, 2) }}
                    </td>

                    <td class="p-2 sm:p-3">
                        <a href="{{ route('salary.download', $salary->id) }}"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs sm:text-sm whitespace-nowrap">
                            Download / Print
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @endif

</div>
</x-app-layout>
