<h2>Salary Slip Notification</h2>

<p>Dear {{ $salary->user->name }},</p>

<p>Your salary for {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }} {{ $salary->year }} has been posted.</p>

<p><strong>Net Salary:</strong> Rs {{ number_format($salary->net_salary,2) }}</p>

<p>You can download your payslip from your dashboard.</p>

<p>Regards,<br>HR Department</p>
