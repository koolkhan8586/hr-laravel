<x-app-layout>

<div class="bg-white p-4 rounded shadow mb-6">

<h2 class="text-lg font-semibold mb-4">Leave Approval WhatsApp Numbers</h2>

<p class="text-sm text-gray-500 mb-4">
Everyone in this list receives a WhatsApp message whenever an employee submits a leave application,
with remaining leave balance and Approve / Reject links.
</p>

@if(session('success'))
<div class="bg-green-100 text-green-700 p-3 rounded mb-4">
{{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="bg-red-100 text-red-700 p-3 rounded mb-4">
{{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('admin.leave.approval.whatsapp.store') }}">
@csrf

<div class="grid grid-cols-3 gap-4 mb-4">

<input type="text" name="name" placeholder="Name (optional)" class="border p-2 rounded">

<input type="text" name="mobile" placeholder="WhatsApp number (e.g. 03001234567)" class="border p-2 rounded" required>

<button class="bg-blue-600 text-white px-4 py-2 rounded">
Add Number
</button>

</div>

</form>

</div>

<div class="bg-white rounded shadow">

<table class="w-full text-sm">

<thead class="bg-gray-100">
<tr>
<th class="p-3 text-left">Name</th>
<th class="p-3 text-left">WhatsApp Number</th>
<th class="p-3 text-left">Action</th>
</tr>
</thead>

<tbody>

@forelse($numbers as $item)

<tr class="border-t">

<td class="p-3">{{ $item->name ?? '-' }}</td>
<td class="p-3">{{ $item->mobile }}</td>

<td class="p-3">

<form method="POST" action="{{ route('admin.leave.approval.whatsapp.destroy', $item->id) }}" onsubmit="return confirm('Remove this WhatsApp number?');">
@csrf
@method('DELETE')
<button class="bg-red-500 text-white px-3 py-1 rounded">
Delete
</button>
</form>

</td>

</tr>

@empty

<tr>
<td class="p-3 text-gray-500" colspan="3">No approval WhatsApp numbers configured yet.</td>
</tr>

@endforelse

</tbody>

</table>

</div>

</x-app-layout>
