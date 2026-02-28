<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-6">
        My Salary Slips
    </h2>

    @if($salaries->count() == 0)
        <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 p-4 rounded">
            No salary records available.
        </div>
    @else

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 lg:gap-6 mb-6">

        <div class="bg-white shadow rounded-lg p-4 sm:p-5 lg:p-6">
            <p class="text-gray-500 text-xs sm:text-sm lg:text-base">
                Total Slips
            </p>
            <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold mt-1">
                {{ $salaries->count() }}
            </h3>
        </div>

        <div class="bg-white shadow rounded-lg p-4 sm:p-5 lg:p-6">
            <p class="text-gray-500 text-xs sm:text-sm lg:text-base">
                Latest Salary
            </p>
            <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold text-green-600 mt-1">
                Rs {{ number_format($salaries->first()->net_salary, 2) }}
            </h3>
        </div>

        <div class="bg-white shadow rounded-lg p-4 sm:p-5 lg:p-6">
            <p class="text-gray-500 text-xs sm:text-sm lg:text-base">
                Year
            </p>
            <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold mt-1">
                {{ $salaries->first()->year }}
            </h3>
        </div>

    </div>

    <!-- Salary Table -->
    <div class="bg-white shadow rounded-lg overflow-x-auto">

        <table class="min-w-full text-sm lg:text-base">

            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-3 lg:p-4 text-left">Month</th>
                    <th class="p-3 lg:p-4 text-left">Year</th>
                    <th class="p-3 lg:p-4 text-left">Gross</th>
                    <th class="p-3 lg:p-4 text-left">Deductions</th>
                    <th class="p-3 lg:p-4 text-left">Net Salary</th>
                    <th class="p-3 lg:p-4 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach($salaries as $salary)
                <tr class="border-t hover:bg-gray-50 transition">
                    
                    <td class="p-3 lg:p-4">
                        {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}
                    </td>

                    <td class="p-3 lg:p-4">
                        {{ $salary->year }}
                    </td>

                    <td class="p-3 lg:p-4">
                        Rs {{ number_format($salary->gross_total, 2) }}
                    </td>

                    <td class="p-3 lg:p-4 text-red-600">
                        Rs {{ number_format($salary->total_deductions, 2) }}
                    </td>

                    <td class="p-3 lg:p-4 font-semibold text-green-600">
                        Rs {{ number_format($salary->net_salary, 2) }}
                    </td>

                    <td class="p-3 lg:p-4">
                        <a href="{{ route('salary.download', $salary->id) }}"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-xs sm:text-sm lg:text-base whitespace-nowrap transition">
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
