<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold mb-4">Leave Transaction Report</h2>

    <form method="GET" class="mb-4 space-x-2">
        <select name="month" class="border p-2">
            <option value="">Month</option>
            @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}">{{ $m }}</option>
            @endfor
        </select>

        <select name="year" class="border p-2">
            <option value="">Year</option>
            @for($y=2025;$y<=2035;$y++)
                <option value="{{ $y }}">{{ $y }}</option>
            @endfor
        </select>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Filter
        </button>

        <a href="{{ route('leave.export.transactions') }}"
           class="bg-green-600 text-white px-4 py-2 rounded">
            Export Excel
        </a>
    </form>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">User</th>
                <th class="p-2 border">Days</th>
                <th class="p-2 border">Before</th>
                <th class="p-2 border">After</th>
                <th class="p-2 border">Action</th>
                <th class="p-2 border">Processed By</th>
                <th class="p-2 border">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
            <tr>
                <td class="p-2 border">{{ $t->user->name }}</td>
                <td class="p-2 border">{{ $t->days }}</td>
                <td class="p-2 border">{{ $t->balance_before }}</td>
                <td class="p-2 border">{{ $t->balance_after }}</td>
                <td class="p-2 border">{{ $t->action }}</td>
                <td class="p-2 border">{{ $t->processed_by }}</td>
                <td class="p-2 border">{{ $t->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>
