<h2>New Leave Application</h2>

<p>Employee: {{ $leave->user->name }}</p>
<p>Type: {{ $leave->type }}</p>
<p>Dates: {{ $leave->start_date }} to {{ $leave->end_date }}</p>
<p>Days: {{ $leave->calculated_days }}</p>
