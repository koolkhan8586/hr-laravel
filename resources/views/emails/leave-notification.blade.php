<h2>Leave Notification</h2>

<p>Dear {{ $leave->user->name }},</p>

<p>Your leave has been recorded with the following details:</p>

<ul>
    <li>Type: {{ ucfirst($leave->type) }}</li>
    <li>From: {{ $leave->start_date }}</li>
    <li>To: {{ $leave->end_date }}</li>
    <li>Status: {{ ucfirst($leave->status) }}</li>
</ul>

<p>Regards,<br>HR Department</p>
