<?php>
<h2>Monthly Attendance Report</h2>
<p>Employee: {{ $user->name }}</p>
<p>Month: {{ $month }}</p>

<table border="1" width="100%" cellpadding="5">
    <tr>
        <th>Date</th>
        <th>Clock In</th>
        <th>Clock Out</th>
        <th>Hours</th>
        <th>Status</th>
    </tr>

    @foreach($records as $record)
    <tr>
        <td>{{ \Carbon\Carbon::parse($record->clock_in)->format('d M Y') }}</td>
        <td>{{ $record->clock_in }}</td>
        <td>{{ $record->clock_out }}</td>
        <td>{{ $record->total_hours }}</td>
        <td>{{ $record->status }}</td>
    </tr>
    @endforeach
</table>
