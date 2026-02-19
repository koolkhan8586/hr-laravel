<h2>Your Leave Status Updated</h2>

<p>Type: {{ $leave->type }}</p>
<p>Dates: {{ $leave->start_date }} to {{ $leave->end_date }}</p>
<p>Status: {{ strtoupper($leave->status) }}</p>
