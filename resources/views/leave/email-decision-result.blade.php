<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Leave Application</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; margin:0; padding:0;">

<div style="max-width:480px; margin:60px auto; background:#ffffff; border-radius:8px; border:1px solid #e5e7eb; padding:32px; text-align:center;">

@if($status === 'approved')
<div style="font-size:40px;">✅</div>
<h2 style="color:#16a34a;">Leave Approved</h2>
<p style="color:#4b5563;">
{{ $leave->user->name }}'s leave from {{ $leave->start_date->format('d M Y') }} to {{ $leave->end_date->format('d M Y') }} has been approved.
</p>

@elseif($status === 'rejected')
<div style="font-size:40px;">❌</div>
<h2 style="color:#dc2626;">Leave Rejected</h2>
<p style="color:#4b5563;">
{{ $leave->user->name }}'s leave from {{ $leave->start_date->format('d M Y') }} to {{ $leave->end_date->format('d M Y') }} has been rejected.
</p>

@elseif($status === 'insufficient')
<div style="font-size:40px;">⚠️</div>
<h2 style="color:#b45309;">Unable to Approve</h2>
<p style="color:#4b5563;">
{{ $leave->user->name }} does not have enough remaining leave balance for this request. Please review it from the HR portal.
</p>

@else
<div style="font-size:40px;">ℹ️</div>
<h2 style="color:#374151;">Already Processed</h2>
<p style="color:#4b5563;">
This leave application has already been marked as <strong>{{ $leave->status }}</strong>. No further action was taken.
</p>
@if($leave->decidedByLabel() || $leave->decided_at)
<p style="color:#6b7280; font-size:14px; margin-top:12px;">
@if($leave->decidedByLabel())
Decided by <strong>{{ $leave->decidedByLabel() }}</strong>
@if($leave->decidedViaLabel()) (via {{ $leave->decidedViaLabel() }})@endif
@endif
@if($leave->decided_at)
<br>on {{ $leave->decided_at->format('d M Y h:i A') }}
@endif
</p>
@endif
@endif

</div>

</body>
</html>
