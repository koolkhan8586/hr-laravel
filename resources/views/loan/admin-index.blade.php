<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            Loan Management
        </h2>

        <div class="flex gap-3">

            {{-- Export --}}
            <a href="{{ route('admin.loan.export') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
                Export
            </a>

            {{-- Import --}}
            <a href="{{ route('admin.loan.import.form') }}"
               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded shadow text-sm">
                Import
            </a>

            {{-- Add Loan --}}
            <a href="{{ route('admin.loan.create') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                Add Loan
            </a>

        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif


    {{-- Loan Table --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Installments</th>
                    <th class="p-3 text-left">Monthly</th>
                    <th class="p-3 text-left">Remaining</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($loans as $loan)
                    <tr class="border-t hover:bg-gray-50">

                        <td class="p-3">
                            {{ $loan->user->name ?? 'N/A' }}
                        </td>

                        <td class="p-3">
                            {{ number_format($loan->amount,2) }}
                        </td>

                        <td class="p-3">
                            {{ $loan->installments }}
                        </td>

                        <td class="p-3">
                            {{ number_format($loan->monthly_deduction,2) }}
                        </td>

                        <td class="p-3">
                            {{ number_format($loan->remaining_balance,2) }}
                        </td>

                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs
                                {{ $loan->status == 'approved'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </td>

                        <td class="p-3 space-x-2">

                            {{-- Edit --}}
                            <a href="{{ route('admin.loan.edit', $loan->id) }}"
                               class="text-blue-600 hover:underline">
                                Edit
                            </a>

                            {{-- Delete --}}
                            <form action="{{ route('admin.loan.delete', $loan->id) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('Delete this loan?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">
                                    Delete
                                </button>
                            </form>

                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center p-6 text-gray-500">
                            No loans found.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>

    </div>

</div>

</x-app-layout>
