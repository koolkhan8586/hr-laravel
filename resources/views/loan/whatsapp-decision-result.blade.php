<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Loan Application</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; margin:0; padding:0;">

<div style="max-width:480px; margin:60px auto; background:#ffffff; border-radius:8px; border:1px solid #e5e7eb; padding:32px; text-align:center;">

@if($status === 'approved')
<div style="font-size:40px;">&#9989;</div>
<h2 style="color:#16a34a;">Loan Approved</h2>
<p style="color:#4b5563;">
{{ $loan->user->name ?? 'The employee' }}'s loan of
Rs {{ number_format((float) $loan->amount, 2) }}
({{ $loan->reference() }}) has been approved.
</p>
<p style="color:#6b7280; font-size:14px;">
Rs {{ number_format((float) $loan->monthly_deduction, 2) }} will be deducted
each month over {{ $loan->installments ?: '-' }} month(s).
</p>

@elseif($status === 'rejected')
<div style="font-size:40px;">&#10060;</div>
<h2 style="color:#dc2626;">Loan Rejected</h2>
<p style="color:#4b5563;">
{{ $loan->user->name ?? 'The employee' }}'s loan application
({{ $loan->reference() }}) has been rejected.
</p>

@else
<div style="font-size:40px;">&#8505;</div>
<h2 style="color:#374151;">Already Decided</h2>
<p style="color:#4b5563;">
This loan application has already been marked as
<strong>{{ $loan->status }}</strong>. Nothing further was done.
</p>
@if($loan->decided_by || $loan->decided_at)
<p style="color:#6b7280; font-size:14px; margin-top:12px;">
@if($loan->decided_by)
Decided by <strong>{{ $loan->decided_by }}</strong>
@if($loan->decided_via) (via {{ $loan->decided_via }})@endif
@endif
@if($loan->decided_at)
<br>on {{ $loan->decided_at->format('d M Y h:i A') }}
@endif
</p>
@endif
@endif

</div>

</body>
</html>
