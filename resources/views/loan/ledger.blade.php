<x-app-layout>
<div class="max-w-6xl mx-auto py-6 px-6">

    <h2 class="text-2xl font-bold mb-6">
        Loan Ledger - {{ $loan->user->name }}
    </h2>

    <div class="bg-white shadow rounded p-4 mb-6">
        <div class="grid grid-cols-4 gap-4">

            <div>
                <p class="text-gray-500">Loan Amount</p>
                <p class="font-bold text-lg">
                    Rs {{ number_format($loan->amount,2) }}
                </p>
            </div>

            <div>
                <p class="text-gray-500">Opening Balance</p>
                <p class="font-bold text-lg">
                    Rs {{ number_format($loan->opening_balance ?? 0,2) }}
                </p>
            </div>

            <div>
                <p class="text-gray-500">Remaining Balance</p>
                <p class="font-bold text-lg text-red-600">
                    Rs {{ number_format($loan->remaining_balance,2) }}
                </p>
            </div>

            <div>
                <p class="text-gray-500">Status</p>
                <p class="font-semibold">
                    {{ ucfirst($loan->status) }}
                </p>
            </div>

        </div>
    </div>

    <h3 class="text-xl font-semibold mb-4">Ledger History</h3>

    <table class="w-full bg-white shadow rounded">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2">Date</th>
                <th class="p-2">Type</th>
                <th class="p-2">Amount</th>
                <th class="p-2">Remarks</th>
            </tr>
        </thead>

        <tbody>
        @forelse($loan->ledgers as $entry)
            <tr class="border-t">
                <td class="p-2">
                    {{ $entry->created_at->format('d-m-Y') }}
                </td>

                <td class="p-2 capitalize">
                    {{ $entry->type }}
                </td>

                <td class="p-2">
                    Rs {{ number_format($entry->amount,2) }}
                </td>

                <td class="p-2">
                    {{ $entry->remarks }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="p-4 text-center text-gray-500">
                    No ledger entries found.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{-- Individual Loan Records section --}}
    <h3 class="text-xl font-bold mt-10 mb-4">Individual Loan Records</h3>
    <div class="bg-white shadow rounded overflow-x-auto mb-8">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">New Loan Amount</th>
                    <th class="p-3 text-left">Opening Balance</th>
                    <th class="p-3 text-left">Installments</th>
                    <th class="p-3 text-left">Monthly Deduction</th>
                    <th class="p-3 text-left">Remaining Balance</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allLoans as $item)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3">
                            {{ $item->loan_date ? \Carbon\Carbon::parse($item->loan_date)->format('d-m-Y') : '-' }}
                        </td>
                        <td class="p-3">
                            Rs {{ number_format($item->amount, 2) }}
                        </td>
                        <td class="p-3">
                            Rs {{ number_format($item->opening_balance, 2) }}
                        </td>
                        <td class="p-3">
                            {{ $item->installments ?? '-' }}
                        </td>
                        <td class="p-3">
                            Rs {{ number_format($item->monthly_deduction, 2) }}
                        </td>
                        <td class="p-3">
                            Rs {{ number_format($item->remaining_balance, 2) }}
                        </td>
                        <td class="p-3">
                            @if($item->status == 'approved')
                                <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">
                                    Approved
                                </span>
                            @elseif($item->status == 'rejected')
                                <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-700">
                                    Rejected
                                </span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-700">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="p-3 space-x-2">
                            <a href="{{ route('admin.loan.edit', $item->id) }}" class="text-blue-600 hover:underline font-semibold">Edit</a>
                            <form action="{{ route('admin.loan.delete', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this specific loan record?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline font-semibold">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
</x-app-layout>
