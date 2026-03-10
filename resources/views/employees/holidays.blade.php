<x-app-layout>

<div class="max-w-6xl mx-auto py-6">

<h2 class="text-xl font-bold mb-6">
Holiday Calendar
</h2>

<table class="w-full border">

<thead class="bg-gray-200">
<tr>
<th class="border p-2">Title</th>
<th class="border p-2">From</th>
<th class="border p-2">To</th>
</tr>
</thead>

<tbody>

@foreach($holidays as $holiday)

<tr>
<td class="border p-2">{{ $holiday->title }}</td>
<td class="border p-2">{{ $holiday->start_date }}</td>
<td class="border p-2">{{ $holiday->end_date }}</td>
</tr>

@endforeach

</tbody>

</table>

</div>

</x-app-layout>
