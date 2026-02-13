<x-app-layout>
<div class="max-w-7xl mx-auto py-8 px-6">

    <!-- Page Title -->
    <h2 class="text-2xl font-bold mb-6 text-gray-800">
        Leave Transaction Report
    </h2>

    <!-- Filter + Export Section -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">

        <form method="GET" class="flex flex-wrap items-center gap-3">

            <select name="month" class="border rounded px-3 py-2">
                <option value="">Month</option>
                @for($m=1;$m<=12;$m++)
                    <option value="{{ $m }}" 
                        {{ request('month') == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                    </option>
                @endfor
            </select>

            <select name="year" class="border rounded px-3 py-2">
                <option value="">Year</option>
                @for($y=2025;$y<=2035;$y++)
                    <option value="{{ $y }}"
                        {{ request('year') == $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endfor
            </select>

            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                Filter
            </button>

            <a href="{{ route('admin.leave.transactions.export', request()->all()) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                Export Excel
            </a>

        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">

        <table class="w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                    <th class="p-3">User</th>
                    <th class="p-3">Days</th>
                    <th class="p-3">Before</th>
                    <th class="p-3">After</th>
                    <th class="p-3">Action</th>
                    <th class="p-3">Processed By</th>
                    <th class="p-3">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y">

                @forelse($transactions as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="p-3">{{ $t->user->name ?? '-' }}</td>
                        <td class="p-3">{{ $t->days }}</td>
                        <td class="p-3">{{ $t->balance_before }}</td>
                        <td class="p-3">{{ $t->balance_after }}</td>
                        <td class="p-3 capitalize">
                            <span class="px-2 py-1 rounded text-xs
                                {{ $t->action == 'approved' ? 'bg-green-100 text-green-700' : 
                                   ($t->action == 'rejected' ? 'bg-red-100 text-red-700' : 
                                   'bg-gray-100 text-gray-700') }}">
                                {{ $t->action }}
                            </span>
                        </td>
                        <td class="p-3">
                            {{ \App\Models\User::find($t->processed_by)->name ?? '-' }}
                        </td>
                        <td class="p-3">
                            {{ $t->created_at->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-6 text-center text-gray-500">
                            No transactions found.
                        </td>
                    </tr>
                @endforelse

            </tbody>
        </table>

    </div>

</div>
</x-app-layout>
