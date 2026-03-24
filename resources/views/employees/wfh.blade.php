<x-app-layout>

<div class="max-w-6xl mx-auto py-6">

<h2 class="text-xl font-bold mb-6">
My Work From Home
</h2>

<table class="w-full border">

<thead class="bg-gray-200">
<tr>
<th class="border p-2">From</th>
<th class="border p-2">To</th>
<th class="border p-2">Reason</th>
</tr>
</thead>

<tbody>

@forelse($wfh as $item)

<tr>

<td class="border p-2">
{{ $item->start_date }}
</td>

<td class="border p-2">
{{ $item->end_date }}
</td>

<td class="border p-2">
{{ $item->reason }}
</td>

</tr>

@empty

<tr>
<td colspan="3" class="text-center p-4 text-gray-500">
No Work From Home records found
</td>
</tr>

@endforelse

</tbody>

</table>

</div>

</x-app-layout>
