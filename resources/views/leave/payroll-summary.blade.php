<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold mb-6">
        Payroll Summary - {{ $year }}
    </h2>

    {{-- Year Filter --}}
    <form method="GET" class="mb-6">
        <input type="number" name="year" value="{{ $year }}"
               class="border p-2 rounded w-32">
        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Filter
        </button>
    </form>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-3 gap-6 mb-8">

        <div class="bg-green-100 p-6 rounded">
            <h3 class="font-bold">Annual Leave Used</h3>
            <p class="text-2xl">{{ $annualUsed }} days</p>
        </div>

        <div class="bg-red-100 p-6 rounded">
            <h3 class="font-bold">Without Pay</h3>
            <p class="text-2xl">{{ $withoutPay }} days</p>
        </div>

        <div class="bg-yellow-100 p-6 rounded">
            <h3 class="font-bold">Sick Leave</h3>
            <p class="text-2xl">{{ $sickUsed }} days</p>
        </div>

    </div>

    {{-- Monthly Breakdown --}}
    <div class="mb-10">
        <h3 class="text-xl font-bold mb-3">Monthly Breakdown</h3>
        <table class="w-full border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 border">Month</th>
                    <th class="p-2 border">Total Days Used</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthly as $m)
                    <tr>
                        <td class="p-2 border">
                            {{ \Carbon\Carbon::create()->month($m->month)->format('F') }}
                        </td>
                        <td class="p-2 border">
                            {{ $m->total }} days
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Employee Summary --}}
    <div>
        <h3 class="text-xl font-bold mb-3">Employee Summary</h3>
        <table class="w-full border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 border">Employee</th>
                    <th class="p-2 border">Total Used</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                    <tr>
                        <td class="p-2 border">
                            {{ $emp->user->name ?? 'N/A' }}
                        </td>
                        <td class="p-2 border">
                            {{ $emp->total_used }} days
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

</x-app-layout>
