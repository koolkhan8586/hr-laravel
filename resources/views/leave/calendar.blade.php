<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-4">

    <h2 class="text-2xl font-bold mb-6">Leave Calendar</h2>

    <div id="calendar" class="bg-white p-4 rounded shadow"></div>

</div>

{{-- FullCalendar CDN --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 650,
        events: @json($events)
    });

    calendar.render();
});
</script>

</x-app-layout>
