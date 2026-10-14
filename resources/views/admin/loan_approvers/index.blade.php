<x-app-layout>

<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6">

<div class="bg-white p-6 rounded shadow mb-6">

<h2 class="text-xl font-bold mb-2">Loan Approvers</h2>

<p class="text-sm text-gray-500 mb-4">
Everybody switched on here gets a WhatsApp message the moment an employee
applies for a loan, with the amount, the purpose and Approve / Reject links.
Tapping a link opens a confirmation page - nothing is decided until Confirm
is pressed, and the links expire after 72 hours.
</p>

<p class="text-sm mb-4">
WhatsApp session:
<strong style="color:{{ $waha['connected'] ? '#15803d' : '#b91c1c' }};">
{{ $waha['status'] }}
</strong>
<span class="text-gray-500">- {{ $waha['detail'] }}</span>
</p>

@include('loan.partials.flash')

@if($errors->any())
<div class="p-3 rounded mb-4" style="background:#fee2e2; color:#b91c1c;">
{{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('admin.loan.approvers.store') }}">
@csrf

<div style="display:flex; gap:12px; flex-wrap:wrap;">

<input type="text" name="name" value="{{ old('name') }}"
placeholder="Name, e.g. Accounts Manager"
class="border p-2 rounded" style="flex:1 1 220px;">

<input type="text" name="mobile" value="{{ old('mobile') }}"
placeholder="WhatsApp number, e.g. 03001234567"
class="border p-2 rounded" style="flex:1 1 220px;" required>

<button class="text-white px-4 py-2 rounded" style="background:#2563eb;">
Add Approver
</button>

</div>

</form>

</div>

<div class="bg-white rounded shadow" style="overflow-x:auto;">

<table class="w-full text-sm">

<thead class="bg-gray-100">
<tr>
<th class="p-3 text-left">Name</th>
<th class="p-3 text-left">WhatsApp Number</th>
<th class="p-3 text-left">Receiving Applications</th>
<th class="p-3 text-left">Action</th>
</tr>
</thead>

<tbody>

@forelse($approvers as $approver)

<tr class="border-t">

<td class="p-3">{{ $approver->name ?? '-' }}</td>

<td class="p-3">{{ $approver->mobile }}</td>

<td class="p-3">
@if($approver->is_active)
<span style="display:inline-block; padding:2px 8px; border-radius:9999px; font-size:12px; background:#dcfce7; color:#15803d;">
Yes
</span>
@else
<span style="display:inline-block; padding:2px 8px; border-radius:9999px; font-size:12px; background:#e5e7eb; color:#374151;">
Switched off
</span>
@endif
</td>

<td class="p-3" style="display:flex; gap:8px; flex-wrap:wrap;">

<form method="POST" action="{{ route('admin.loan.approvers.test', $approver->id) }}">
@csrf
<button class="text-white px-3 py-1 rounded" style="background:#4f46e5;">
Send Test
</button>
</form>

<form method="POST" action="{{ route('admin.loan.approvers.toggle', $approver->id) }}">
@csrf
<button class="text-white px-3 py-1 rounded" style="background:#6b7280;">
{{ $approver->is_active ? 'Switch Off' : 'Switch On' }}
</button>
</form>

<form method="POST" action="{{ route('admin.loan.approvers.destroy', $approver->id) }}"
onsubmit="return confirm('Remove this approver?');">
@csrf
@method('DELETE')
<button class="text-white px-3 py-1 rounded" style="background:#dc2626;">
Delete
</button>
</form>

</td>

</tr>

@empty

<tr>
<td class="p-3 text-gray-500" colspan="4">
No loan approvers yet. Add the first one above - until then applications are
recorded but nobody is messaged about them.
</td>
</tr>

@endforelse

</tbody>

</table>

</div>

</div>

</x-app-layout>
