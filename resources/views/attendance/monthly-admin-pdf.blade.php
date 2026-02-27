<!DOCTYPE html>
<html>
<head>
    <title>Monthly Attendance</title>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #000; padding:5px; text-align:center; }
        th { background:#f2f2f2; }
    </style>
</head>
<body>

<h2>Monthly Attendance Report</h2>

<p><strong>Employee:</strong> {{ $user->name }}</p>
<p><strong>Employee Code:</strong> {{ $user->employee_code }}</p>
<p><strong>Month:</strong> {{ $month }}</p>

<br>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Clock In</th>
            <th>Clock Out</th>
            <th>Total Hours</th>
            <th>Status</th>
            <th>Location</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $record)
        <tr>
            <td>{{ \Carbon\Carbon::parse($record->clock_in)->format('d M Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($record->clock_in)->format('h:i A') }}</td>
            <td>
                {{ $record->clock_out
                    ? \Carbon\Carbon::parse($record->clock_out)->format('h:i A')
                    : '-' }}
            </td>
            <td>{{ $record->total_hours ?? '-' }}</td>
            <td>{{ ucfirst($record->status) }}</td>
            <td>
                {{ $record->latitude && $record->longitude
                    ? $record->latitude.', '.$record->longitude
                    : '-' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
