<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; margin:0; padding:0; background:#f3f4f6;">

<div style="max-width:520px; margin:24px auto; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb;">

<div style="background:#2563eb; padding:16px 24px;">
<h2 style="color:#ffffff; margin:0; font-size:18px;">New Leave Application</h2>
</div>

<div style="padding:24px;">

<p style="margin-top:0;">A new leave application requires your review.</p>

<table style="width:100%; border-collapse:collapse; margin-bottom:24px;">
<tr>
<td style="padding:6px 0; color:#6b7280;">Employee</td>
<td style="padding:6px 0; font-weight:bold;">{{ $leave->user->name }}</td>
</tr>
<tr>
<td style="padding:6px 0; color:#6b7280;">Type</td>
<td style="padding:6px 0;">{{ ucfirst(str_replace('_',' ', $leave->type)) }}</td>
</tr>
<tr>
<td style="padding:6px 0; color:#6b7280;">From</td>
<td style="padding:6px 0;">{{ $leave->start_date->format('d M Y') }}</td>
</tr>
<tr>
<td style="padding:6px 0; color:#6b7280;">To</td>
<td style="padding:6px 0;">{{ $leave->end_date->format('d M Y') }}</td>
</tr>
<tr>
<td style="padding:6px 0; color:#6b7280;">Days</td>
<td style="padding:6px 0;">{{ $leave->calculated_days }}</td>
</tr>
@if($leave->reason)
<tr>
<td style="padding:6px 0; color:#6b7280; vertical-align:top;">Reason</td>
<td style="padding:6px 0;">{{ $leave->reason }}</td>
</tr>
@endif
</table>

<div>
<a href="{{ $approveUrl }}"
   style="display:inline-block; background:#16a34a; color:#ffffff; text-decoration:none; padding:10px 22px; border-radius:6px; font-weight:bold; margin-right:12px;">
   Approve
</a>

<a href="{{ $rejectUrl }}"
   style="display:inline-block; background:#dc2626; color:#ffffff; text-decoration:none; padding:10px 22px; border-radius:6px; font-weight:bold;">
   Reject
</a>
</div>

<p style="color:#9ca3af; font-size:12px; margin-top:24px; margin-bottom:0;">
This link is valid for 72 hours and can only be used once. You can also review this application by logging into the HR portal.
</p>

</div>

</div>

</body>
</html>
