<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <h2 class="text-2xl font-bold mb-4">Leave Transaction History</h2>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Leave ID</th>
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
                <td class="p-2 border">{{ $t->leave_id }}</td>
                <td class="p-2 border">{{ $t->days }}</td>
                <td class="p-2 border">{{ $t->balance_before }}</td>
                <td class="p-2 border">{{ $t->balance_after }}</td>
                <td class="p-2 border capitalize">{{ $t->action }}</td>
                <td class="p-2 border">{{ $t->processed_by }}</td>
                <td class="p-2 border">{{ $t->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>
