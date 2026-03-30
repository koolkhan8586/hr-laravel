<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6">Office Locations</h2>

{{-- ADD LOCATION --}}
<div class="bg-white p-4 rounded shadow mb-6">
<form method="POST" action="{{ route('admin.office-locations.store') }}">
@csrf

<div class="grid grid-cols-4 gap-4">

<input type="text" name="name" placeholder="Location Name" class="border p-2" required>

<input type="text" name="latitude" placeholder="Latitude" class="border p-2" required>

<input type="text" name="longitude" placeholder="Longitude" class="border p-2" required>

<input type="number" name="radius" placeholder="Radius (meters)" class="border p-2" required>

</div>

<button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
Add Location
</button>

</form>
</div>

{{-- LOCATION LIST --}}
<div class="bg-white rounded shadow">

<table class="w-full text-sm">

<thead class="bg-gray-100">
<tr>
<th class="p-3">Name</th>
<th class="p-3">Latitude</th>
<th class="p-3">Longitude</th>
<th class="p-3">Radius</th>
<th class="p-3">Action</th>
</tr>
</thead>

<tbody>

@foreach($locations as $loc)

<tr class="border-t">

<td class="p-3">{{ $loc->name }}</td>
<td class="p-3">{{ $loc->latitude }}</td>
<td class="p-3">{{ $loc->longitude }}</td>
<td class="p-3">{{ $loc->radius }} m</td>

<td class="p-3 flex gap-2">

<form method="POST" action="{{ route('admin.office-locations.destroy',$loc->id) }}">
@csrf
@method('DELETE')
<button class="bg-red-500 text-white px-3 py-1 rounded">
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
