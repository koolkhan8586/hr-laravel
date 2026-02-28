<x-app-layout>
<div class="w-full max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold mb-6">
        My Salary Slips
    </h2>

    @if($salaries->count() == 0)
        <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 p-4 rounded">
            No salary records available.
        </div>
    @else

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6">

        <div class="bg-white shadow rounded-lg p-4 sm:p-5 lg:p-6">
            <p class="text-gray-500 text-sm">Total Slips</p>
            <h3 class="text-xl sm:text-2xl font-bold">
                {{ $salaries->count() }}
            </h3>
        </div>

        <div class="bg-white shadow rounded-lg p-4 sm:p-5 lg:p-6">
            <p class="text-gray-500 text-sm">Latest Salary</p>
            <h3 class="text-xl sm:text-2xl font-bold text-green-600">
                Rs {{ number_format($salaries->first()->net_salary, 2) }}
            </h3>
        </div>

        <div class="bg-white shadow rounded-lg p-4 sm:p-5 lg:p-6">
            <p class="text-gray-500 text-sm">Year</p>
            <h3 class="text-xl sm:text-2xl font-bold">
                {{ $salaries->first()->year }}
            </h3>
        </div>

    </div>

    <!-- Responsive Table -->
    <div class="bg-white shadow rounded-lg">

        <div class="w-full overflow-x-auto">

            <table class="min-w-[750px] w-full text-sm lg:text-base">

                <thead class="bg-gray-100 text-gray-700 whitespace-nowrap">
                    <tr>
                        <th class="p-3 sm:p-4 text-left">Month</th>
                        <th class="p-3 sm:p-4 text-left">Year</th>
                        <th class="p-3 sm:p-4 text-left">Gross</th>
                        <th class="p-3 sm:p-4 text-left">Deductions</th>
                        <th class="p-3 sm:p-4 text-left">Net Salary</th>
                        <th class="p-3 sm:p-4 text-left">Action</th>
                    </tr>
                </thead>

                <tbody class="whitespace-nowrap">
                    @foreach($salaries as $salary)
                    <tr class="border-t hover:bg-gray-50 transition">

                        <td class="p-3 sm:p-4">
                            {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}
                        </td>

                        <td class="p-3 sm:p-4">
                            {{ $salary->year }}
                        </td>

                        <td class="p-3 sm:p-4">
                            Rs {{ number_format($salary->gross_total, 2) }}
                        </td>

                        <td class="p-3 sm:p-4 text-red-600">
                            Rs {{ number_format($salary->total_deductions, 2) }}
                        </td>

                        <td class="p-3 sm:p-4 font-semibold text-green-600">
                            Rs {{ number_format($salary->net_salary, 2) }}
                        </td>

                        <td class="p-3 sm:p-4">
                            <a href="{{ route('salary.download', $salary->id) }}"
                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded text-xs sm:text-sm">
                                Download / Print
                            </a>
                        </td>

                    </tr>
                    @endforeach
                </tbody>

            </table>

        </div>

    </div>

    @endif

</div>
</x-app-layout>
