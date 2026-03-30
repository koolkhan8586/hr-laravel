<x-app-layout>

<div class="bg-white p-4 rounded shadow mb-6">

<form method="POST" action="{{ route('admin.office-locations.store') }}">
@csrf

<div class="grid grid-cols-4 gap-4 mb-4">

<input type="text" name="name" placeholder="Location Name" class="border p-2" required>

<input type="text" id="latitude" name="latitude" placeholder="Latitude" class="border p-2" required>

<input type="text" id="longitude" name="longitude" placeholder="Longitude" class="border p-2" required>

<input type="number" id="radius" name="radius" placeholder="Radius (meters)" class="border p-2" value="100" required>

</div>

<div class="flex gap-3 mb-4">

<button type="button" onclick="getCurrentLocation()" class="bg-green-600 text-white px-4 py-2 rounded">
📍 Use My Location
</button>

<button type="button" onclick="clearMap()" class="bg-gray-500 text-white px-4 py-2 rounded">
Clear
</button>

</div>

{{-- MAP --}}
<div id="map" style="height: 400px;" class="mb-4 rounded border"></div>

<button class="bg-blue-600 text-white px-4 py-2 rounded">
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

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDhnfhhx0J0-MoD_MF1llkBikyIsC6MOnQ"></script>

<script>

let map;
let marker;
let circle;

function initMap() {

    const defaultLocation = { lat: 31.5071600, lng: 74.3460000 }; // Lahore

    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 13,
        center: defaultLocation,
    });

    map.addListener("click", function (e) {
        placeMarker(e.latLng);
    });
}

function placeMarker(location) {

    if (marker) marker.setMap(null);
    if (circle) circle.setMap(null);

    marker = new google.maps.Marker({
        position: location,
        map: map
    });

    document.getElementById("latitude").value = location.lat();
    document.getElementById("longitude").value = location.lng();

    drawRadius();
}

function drawRadius() {

    const radius = document.getElementById("radius").value;

    if (!marker) return;

    if (circle) circle.setMap(null);

    circle = new google.maps.Circle({
        map: map,
        radius: parseInt(radius),
        fillColor: '#4285F4',
        fillOpacity: 0.2,
        strokeColor: '#4285F4',
        strokeWeight: 2,
        center: marker.getPosition()
    });
}

document.getElementById("radius").addEventListener("input", drawRadius);

function getCurrentLocation() {

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {

            const location = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            map.setCenter(location);
            placeMarker(new google.maps.LatLng(location.lat, location.lng));

        });
    } else {
        alert("Geolocation not supported");
    }
}

function clearMap() {
    if (marker) marker.setMap(null);
    if (circle) circle.setMap(null);

    document.getElementById("latitude").value = '';
    document.getElementById("longitude").value = '';
}

window.onload = initMap;

</script>
</x-app-layout>
