<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-4">Live Employee Map</h2>

<div id="map" style="height:500px;" class="rounded border"></div>

</div>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDhnfhhx0J0-MoD_MF1llkBikyIsC6MOnQ"></script>

<script>

let map;

function initMap() {

    const center = { lat: 31.5204, lng: 74.3587 };

    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: center
    });

    const employees = @json($employees);

    employees.forEach(emp => {

        let lat = parseFloat(emp.clock_in_latitude);
        let lng = parseFloat(emp.clock_in_longitude);

        if (!lat || !lng) return;

        let marker = new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: map,
            title: emp.user.name
        });

        let info = new google.maps.InfoWindow({
            content: `
                <b>${emp.user.name}</b><br>
                Status: ${emp.status}<br>
                Time: ${emp.clock_in}
            `
        });

        marker.addListener("click", () => {
            info.open(map, marker);
        });

    });
}

window.onload = initMap;

</script>

</x-app-layout>
