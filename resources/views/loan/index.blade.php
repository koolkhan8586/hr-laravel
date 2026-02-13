<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">
            Loan Management
        </h2>

        <a href="{{ route('loan.create') }}"
           class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded shadow">
            Assign Loan
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-3 gap-6 mb-8">

        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Active Loans</p>
            <p class="text-2xl font-bold">
                {{ $loans->where('status','active')->count() }}
            </p>
        </div>

        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Total Outstanding</p>
            <p class="text-2xl font-bold">
                {{ number_format($loans->sum('remaining_amount'),2) }}
            </p>
        </div>

        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Total Loan Amount</p>
            <p class="text-2xl font-bold">
                {{ number_format($loans->sum('amount'),2) }}
            </p>
        </div>

    </div>

    {{-- Loan Table --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Monthly Deduction</th>
                    <th class="p-3 text-left">Remaining</th>
                    <th class="p-3 text-left">Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse($loans as $loan)
                    <tr class="border-t">
                        <td class="p-3">
                            {{ $loan->user->name ?? 'N/A' }}
                        </td>
                        <td class="p-3">
                            {{ number_format($loan->amount,2) }}
                        </td>
                        <td class="p-3">
                            {{ number_format($loan->amount / $loan->installments,2) }}
                        </td>
                        <td class="p-3">
                            {{ number_format($loan->remaining_amount,2) }}
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-sm rounded 
                                {{ $loan->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-6 text-gray-500">
                            No loans assigned yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</div>

</x-app-layout>
