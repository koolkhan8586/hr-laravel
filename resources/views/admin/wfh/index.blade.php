<x-app-layout>

<div class="max-w-7xl mx-auto py-6">

<h2 class="text-xl font-bold mb-6">
Work From Home Management
</h2>

<table class="w-full border">

<thead class="bg-gray-200">
<tr>
<th class="border p-2">Employee</th>
<th class="border p-2">From</th>
<th class="border p-2">To</th>
<th class="border p-2">Reason</th>
<th class="border p-2">Action</th>
</tr>
</thead>

<tbody>

@forelse($wfh as $item)

<tr>

<td class="border p-2">
{{ $item->user->name ?? '-' }}
</td>

<td class="border p-2">
{{ $item->start_date }}
</td>

<td class="border p-2">
{{ $item->end_date }}
</td>

<td class="border p-2">
{{ $item->reason }}
</td>

<td class="border p-2 flex gap-2">

<a href="{{ route('wfh.edit',$item->id) }}"
class="bg-blue-500 text-white px-2 py-1 rounded text-xs">
Edit
</a>

<form method="POST"
action="{{ route('wfh.delete',$item->id) }}">
@csrf
@method('DELETE')

<button class="bg-red-500 text-white px-2 py-1 rounded text-xs">
Delete
</button>

</form>

</td>

</tr>

@empty

<tr>
<td colspan="5" class="text-center p-4 text-gray-500">
No WFH records found
</td>
</tr>

@endforelse

</tbody>

</table>

</div>

</x-app-layout>
