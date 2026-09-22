<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Attendance</title>
    <style>
        body { font-family: DejaVu Sans; font-size: 11px; }

        h2 { margin: 0 0 8px 0; font-size: 16px; }
        .meta { margin-bottom: 10px; }
        .meta span { margin-right: 18px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        th { background: #f2f2f2; }

        td.left { text-align: left; }

        /* A day that needs looking at should be findable at a glance. */
        tr.absent  td { background: #fdecec; }
        tr.leave   td { background: #eaf1fb; }
        tr.holiday td,
        tr.off     td { background: #f5f5f5; color: #555; }

        .summary { margin-top: 14px; width: auto; }
        .summary td, .summary th { padding: 3px 10px; }
    </style>
</head>
<body>

<h2>Monthly Attendance Report</h2>

<p class="meta">
    <span><strong>Employee:</strong> {{ $user->name }}</span>
    <span><strong>Employee Code:</strong> {{ $user->employee_code ?: '-' }}</span>
    <span><strong>Month:</strong> {{ $month }}</span>
</p>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Day</th>
            <th>Clock In</th>
            <th>Clock Out</th>
            <th>Total Hours</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Location</th>
        </tr>
    </thead>
    <tbody>
        @forelse($days as $row)
        <tr class="{{ $row['bucket'] }}">
            <td>{{ $row['date']->format('d M Y') }}</td>
            <td>{{ $row['day_name'] }}</td>
            <td>{{ $row['clock_in'] ?: '-' }}</td>
            <td>{{ $row['clock_out'] ?: '-' }}</td>
            <td>{{ $row['hours'] !== null ? number_format($row['hours'], 2) : '-' }}</td>
            <td>{{ $row['label'] }}</td>
            <td class="left">{{ $row['note'] ?: '-' }}</td>
            <td>{{ $row['location'] ?: '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="8">Nothing to show for this month yet.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if(!empty($days))
<table class="summary">
    <tr>
        <th>Present</th>
        <th>Late</th>
        <th>Half Day</th>
        <th>Leave</th>
        <th>Absent</th>
        <th>Work From Home</th>
        <th>Holiday</th>
        <th>Weekly Off</th>
        <th>Total Hours</th>
    </tr>
    <tr>
        <td>{{ $totals['present'] }}</td>
        <td>{{ $totals['late'] }}</td>
        <td>{{ $totals['half_day'] }}</td>
        <td>{{ $totals['leave'] }}</td>
        <td>{{ $totals['absent'] }}</td>
        <td>{{ $totals['wfh'] }}</td>
        <td>{{ $totals['holiday'] }}</td>
        <td>{{ $totals['off'] }}</td>
        <td>{{ number_format($totals['hours'], 2) }}</td>
    </tr>
</table>
@endif

</body>
</html>
