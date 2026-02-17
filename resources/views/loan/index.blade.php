<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">
            Loan Management
        </h2>

        <a href="{{ route('admin.loan.create') }}"
           class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded shadow">
            Assign Loan
        </a>

    </div>

    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Opening Balance</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Installments</th>
                    <th class="p-3 text-left">Monthly</th>
                    <th class="p-3 text-left">Remaining</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach($loans as $loan)
                <tr class="border-t">

                    <td class="p-3">{{ $loan->user->name }}</td>

                   
                    <td class="p-3">
                        Rs {{ number_format($loan->amount,2) }}
                    </td>
                    <td>{{ number_format($loan->opening_balance,2) }}</td>
                    <td class="p-3">
                        {{ $loan->installments }}
                    </td>

                    <td class="p-3 text-blue-600">
                        Rs {{ number_format($loan->monthly_deduction,2) }}
                    </td>

                    <td class="p-3 text-red-600">
                        Rs {{ number_format($loan->remaining_balance,2) }}
                    </td>

                    <td class="p-3">
                        <span class="px-2 py-1 rounded text-sm
                            {{ $loan->status == 'approved'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-yellow-100 text-yellow-700' }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>

                    <td class="p-3 space-x-2">

                        <a href="{{ route('admin.loan.ledger',$loan->id) }}"
       class="text-blue-600 font-semibold mr-2">
       Ledger
    </a>
                        <a href="{{ route('admin.loan.edit',$loan->id) }}"
                           class="bg-blue-500 text-white px-3 py-1 rounded text-sm">
                            Edit
                        </a>

                        <form action="{{ route('admin.loan.delete',$loan->id) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('Delete this loan?')">
                            @csrf
                            @method('DELETE')
                            <button class="bg-red-500 text-white px-3 py-1 rounded text-sm">
                                Delete
                            </button>
                        </form>

                    </td>

                </tr>
                @endforeach
            </tbody>

        </table>

    </div>

</div>

</x-app-layout>
