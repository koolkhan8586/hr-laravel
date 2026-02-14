<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">
            Loan Management
        </h2>

        <a href="{{ route('admin.loan.create') }}"
           class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded shadow">
            Assign Loan
        </a>
    </div>

    {{-- ================= SUMMARY CARDS ================= --}}
    <div class="grid grid-cols-4 gap-6 mb-8">

        {{-- Total Loans --}}
        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Total Loans</p>
            <p class="text-2xl font-bold">
                {{ $loans->count() }}
            </p>
        </div>

        {{-- Approved Loans --}}
        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Approved Loans</p>
            <p class="text-2xl font-bold text-green-600">
                {{ $loans->where('status','approved')->count() }}
            </p>
        </div>

        {{-- Total Outstanding --}}
        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Total Outstanding</p>
            <p class="text-2xl font-bold text-red-600">
                Rs {{ number_format($loans->sum('remaining_balance'),2) }}
            </p>
        </div>

        {{-- Total Loan Amount --}}
        <div class="bg-white shadow rounded p-6">
            <p class="text-gray-500 text-sm">Total Loan Amount</p>
            <p class="text-2xl font-bold">
                Rs {{ number_format($loans->sum('amount'),2) }}
            </p>
        </div>

    </div>

    {{-- ================= LOAN TABLE ================= --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full">
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

                        <td class="p-3 font-medium">
                            Rs {{ number_format($loan->amount,2) }}
                        </td>

                        <td class="p-3">
                            {{ $loan->installments }}
                        </td>

                        <td class="p-3 text-blue-600 font-medium">
                            Rs {{ number_format($loan->monthly_deduction,2) }}
                        </td>

                        <td class="p-3 text-red-600 font-medium">
                            Rs {{ number_format($loan->remaining_balance,2) }}
                        </td>

                        <td class="p-3">
                            @if($loan->status == 'approved')
                                <span class="px-3 py-1 text-sm rounded bg-green-100 text-green-700">
                                    Approved
                                </span>
                            @elseif($loan->status == 'pending')
                                <span class="px-3 py-1 text-sm rounded bg-yellow-100 text-yellow-700">
                                    Pending
                                </span>
                            @else
                                <span class="px-3 py-1 text-sm rounded bg-red-100 text-red-700">
                                    Rejected
                                </span>
                            @endif
                        </td>

                        <td class="p-3 space-x-2">

                            {{-- Edit --}}
                            <a href="{{ route('admin.loan.edit', $loan->id) }}"
                               class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                Edit
                            </a>

                            {{-- Delete --}}
                            <form action="{{ route('admin.loan.delete', $loan->id) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('Are you sure you want to delete this loan?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                    Delete
                                </button>
                            </form>

                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center p-6 text-gray-500">
                            No loans assigned yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</div>

</x-app-layout>
