<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-4">

<h2 class="text-2xl font-bold mb-6">Leave Calendar</h2>

<div id="calendar"></div>

</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet'>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: [
            @foreach($leaves as $leave)
            {
                title: '{{ $leave->user->name }}',
                start: '{{ $leave->start_date }}',
                end: '{{ \Carbon\Carbon::parse($leave->end_date)->addDay()->format("Y-m-d") }}',
                color: '#22c55e'
            },
            @endforeach
        ]
    });

    calendar.render();
});
</script>

</x-app-layout>
