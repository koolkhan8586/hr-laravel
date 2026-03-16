<div>

<p><strong>Clock In:</strong> {{ $attendance->clock_in }}</p>

<p><strong>Clock Out:</strong> {{ $attendance->clock_out ?? 'Not Clocked Out' }}</p>

<p><strong>Status:</strong> {{ $attendance->status }}</p>

<p><strong>Location:</strong>
{{ $attendance->clock_in_latitude }},
{{ $attendance->clock_in_longitude }}
</p>

</div>
