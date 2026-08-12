<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Confirm Leave Decision</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; margin:0; padding:0;">

<div style="max-width:480px; margin:60px auto; background:#ffffff; border-radius:8px; border:1px solid #e5e7eb; padding:32px;">

@if($leave->status !== 'pending')
    <div style="text-align:center;">
        <div style="font-size:40px;">ℹ️</div>
        <h2 style="color:#374151;">Already Processed</h2>
        <p style="color:#4b5563;">
            This leave application has already been marked as <strong>{{ $leave->status }}</strong>.
        </p>
    </div>
@else
    <div style="text-align:center; margin-bottom:24px;">
        <h2 style="color:#111827; margin:0 0 8px;">
            Confirm {{ $decision === 'approve' ? 'Approval' : 'Rejection' }}
        </h2>
        <p style="color:#6b7280; margin:0;">
            Please confirm your decision for this leave request.
        </p>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:24px; font-size:14px;">
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

    <form method="POST" action="{{ $confirmUrl }}" style="text-align:center;">
        @csrf
        <button type="submit"
            style="display:inline-block; border:none; cursor:pointer; color:#ffffff; padding:12px 28px; border-radius:6px; font-weight:bold; font-size:15px;
            background:{{ $decision === 'approve' ? '#16a34a' : '#dc2626' }};">
            {{ $decision === 'approve' ? 'Confirm Approve' : 'Confirm Reject' }}
        </button>
    </form>

    <p style="color:#9ca3af; font-size:12px; margin-top:24px; text-align:center; margin-bottom:0;">
        This confirmation is required so WhatsApp/email link previews cannot approve leave automatically.
    </p>
@endif

</div>

</body>
</html>
