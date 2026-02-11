<h2>Attendance {{ $type }}</h2>

@if($type == 'Clock In')
<p>You have successfully clocked in at: {{ $attendance->clock_in }}</p>
@endif

@if($type == 'Clock Out')
<p>You clocked out at: {{ $attendance->clock_out }}</p>
<p>Total Hours: {{ round($attendance->total_hours, 2) }}</p>
@endif
