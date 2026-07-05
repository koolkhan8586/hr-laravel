<x-app-layout>
<style>
/* CSS Grid fallback for servers without compiled Tailwind */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
@media (min-width: 768px) {
    .summary-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.ledger-grid {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
@media (min-width: 1024px) {
    .ledger-grid {
        display: grid;
        grid-template-columns: 2.2fr 1fr;
    }
}

/* Printing styles */
@media print {
    /* Hide sidebar, top navigation, bottom mobile navigation bar, buttons, actions, and manual form */
    aside, nav, header, .fixed.bottom-0, .no-print, .no-print-col {
        display: none !important;
    }
    
    /* Display the print-only header */
    .print-header {
        display: block !important;
    }
    
    body, main {
        background: white !important;
        color: black !important;
    }
    
    .max-w-6xl {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>

<div class="max-w-6xl mx-auto py-6 px-6">

    {{-- Success and Error Messages --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-6 no-print">
            {{ session('success') }}
        </div>
    @endif

    {{-- Print-Only Header (Hidden on screen) --}}
    <div class="print-header hidden" style="display: none;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #1b4332; padding-bottom: 12px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <img src="{{ asset('uol-logo.png') }}" alt="University Logo" style="height: 70px; width: auto;">
                <div>
                    <h1 style="font-size: 22px; font-weight: bold; color: #1b4332; margin: 0; line-height: 1.2;">
                        Lahore School of Accountancy and Finance
                    </h1>
                    <h2 style="font-size: 16px; font-weight: 600; color: #4a5568; margin: 4px 0 0 0;">
                        Employee Loan Ledger
                    </h2>
                </div>
            </div>
            <div style="text-align: right; font-size: 14px; color: #2d3748; line-height: 1.5;">
                <p style="margin: 0;"><strong>Employee Name:</strong> {{ $loan->user->name }}</p>
                <p style="margin: 4px 0 0 0;"><strong>Employee ID:</strong> {{ $loan->user->employee_id ?? 'N/A' }}</p>
                <p style="margin: 4px 0 0 0;"><strong>Date:</strong> {{ date('d-m-Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Title and Print Button --}}
    <div class="flex justify-between items-center mb-6 no-print">
        <h2 class="text-2xl font-bold text-gray-800">
            Loan Ledger - {{ $loan->user->name }}
        </h2>
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
            Print Ledger
        </button>
    </div>

    {{-- Loan Consolidated Details Card --}}
    <div class="bg-white shadow rounded p-4 mb-6">
        <div class="summary-grid text-center md:text-left">
            <div>
                <p class="text-gray-500 text-sm">Loan Amount</p>
                <p class="font-bold text-lg text-gray-800">
                    Rs {{ number_format($loan->amount,2) }}
                </p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Opening Balance</p>
                <p class="font-bold text-lg text-gray-800">
                    Rs {{ number_format($loan->opening_balance ?? 0,2) }}
                </p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Remaining Balance</p>
                <p class="font-bold text-lg text-red-600">
                    Rs {{ number_format($loan->remaining_balance,2) }}
                </p>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Status</p>
                <p class="font-semibold text-lg text-green-600 capitalize">
                    {{ $loan->status }}
                </p>
            </div>
        </div>
    </div>

    {{-- Ledger History Table and Manual Add Form --}}
    <div class="ledger-grid mb-8">
        {{-- Ledger History Table --}}
        <div>
            <h3 class="text-xl font-semibold mb-4">Ledger History</h3>
            <div class="bg-white shadow rounded overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Amount</th>
                            <th class="p-3 text-left">Remarks</th>
                            <th class="p-3 text-left no-print-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($loan->ledgers as $entry)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 whitespace-nowrap">
                                {{ $entry->created_at->format('d-m-Y') }}
                            </td>
                            <td class="p-3">
                                @if($entry->type == 'opening')
                                    <span class="text-purple-600 font-semibold">Opening Balance</span>
                                @elseif($entry->type == 'deduction')
                                    <span class="text-red-600 font-semibold">Salary Deduction</span>
                                @elseif($entry->type == 'adjustment')
                                    <span class="text-blue-600 font-semibold">Adjustment</span>
                                @elseif($entry->type == 'loan')
                                    <span class="text-green-600 font-semibold">New Loan</span>
                                @endif
                            </td>
                            <td class="p-3 font-semibold whitespace-nowrap">
                                Rs {{ number_format($entry->amount,2) }}
                            </td>
                            <td class="p-3 text-gray-600">
                                {{ $entry->remarks }}
                            </td>
                            <td class="p-3 space-x-2 no-print-col whitespace-nowrap">
                                <a href="{{ route('admin.loan.ledger-entry.edit', $entry->id) }}" class="text-blue-600 hover:underline font-semibold">Edit</a>
                                <form action="{{ route('admin.loan.ledger-entry.delete', $entry->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this ledger entry? This will update the remaining balance.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline font-semibold">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500">
                                No ledger entries found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add Entry Form --}}
        <div class="bg-white shadow rounded p-4 no-print h-fit border border-gray-100">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Add Manual Entry</h3>
            <form action="{{ route('admin.loan.ledger-entry.store', $loan->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1 text-gray-600">Amount</label>
                    <input type="number" name="amount" step="0.01" required class="w-full border rounded px-3 py-1.5 text-sm" placeholder="Enter amount">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1 text-gray-600">Type</label>
                    <select name="type" required class="w-full border rounded px-3 py-1.5 text-sm">
                        <option value="deduction">Salary Deduction (Repayment)</option>
                        <option value="adjustment">Manual Repayment / Adjustment</option>
                        <option value="loan">New Loan Amount</option>
                        <option value="opening">Opening Balance</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1 text-gray-600">Date</label>
                    <input type="date" name="created_at" value="{{ date('Y-m-d') }}" required class="w-full border rounded px-3 py-1.5 text-sm">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-600">Remarks</label>
                    <input type="text" name="remarks" class="w-full border rounded px-3 py-1.5 text-sm" placeholder="e.g. Cash payment" required>
                </div>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded text-sm font-semibold shadow-sm transition-colors">
                    Add Entry
                </button>
            </form>
        </div>
    </div>

    {{-- Individual Loan Records section --}}
    <h3 class="text-xl font-bold mt-10 mb-4">Individual Loan Records</h3>
    <div class="bg-white shadow rounded overflow-x-auto mb-8">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="p-3 text-left">Date</th>
                    <th class="p-3 text-left">New Loan Amount</th>
                    <th class="p-3 text-left">Opening Balance</th>
                    <th class="p-3 text-left">Installments</th>
                    <th class="p-3 text-left">Monthly Deduction</th>
                    <th class="p-3 text-left">Remaining Balance</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left no-print-col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allLoans as $item)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3 whitespace-nowrap">
                            {{ $item->loan_date ? \Carbon\Carbon::parse($item->loan_date)->format('d-m-Y') : '-' }}
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            Rs {{ number_format($item->amount, 2) }}
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            Rs {{ number_format($item->opening_balance, 2) }}
                        </td>
                        <td class="p-3">
                            {{ $item->installments ?? '-' }}
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            Rs {{ number_format($item->monthly_deduction, 2) }}
                        </td>
                        <td class="p-3 whitespace-nowrap">
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
                        <td class="p-3 space-x-2 no-print-col whitespace-nowrap">
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
