<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Confirm Loan Decision</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f3f4f6; margin:0; padding:0;">

<div style="max-width:480px; margin:60px auto; background:#ffffff; border-radius:8px; border:1px solid #e5e7eb; padding:32px;">

@if($loan->status === 'cancelled')
    <div style="text-align:center;">
        <div style="font-size:40px;">&#8505;</div>
        <h2 style="color:#374151;">Application Withdrawn</h2>
        <p style="color:#4b5563;">
            {{ $loan->user->name ?? 'The employee' }} has taken this
            application back, so there is nothing to decide.
        </p>
    </div>
@elseif($loan->status !== 'pending')
    <div style="text-align:center;">
        <div style="font-size:40px;">&#8505;</div>
        <h2 style="color:#374151;">Already Decided</h2>
        <p style="color:#4b5563;">
            This loan application has already been marked as
            <strong>{{ $loan->status }}</strong>.
        </p>
    </div>
@else
    <div style="text-align:center; margin-bottom:24px;">
        <h2 style="color:#111827; margin:0 0 8px;">
            Confirm {{ $decision === 'approve' ? 'Approval' : 'Rejection' }}
        </h2>
        <p style="color:#6b7280; margin:0;">
            Please confirm your decision for this loan application.
        </p>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:24px; font-size:14px;">
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Reference</td>
            <td style="padding:6px 0; font-weight:bold;">{{ $loan->reference() }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Employee</td>
            <td style="padding:6px 0; font-weight:bold;">{{ $loan->user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Amount</td>
            <td style="padding:6px 0;">Rs {{ number_format((float) $loan->amount, 2) }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Installments</td>
            <td style="padding:6px 0;">{{ $loan->installments ?: '-' }}</td>
        </tr>
        <tr>
            <td style="padding:6px 0; color:#6b7280;">Monthly deduction</td>
            <td style="padding:6px 0;">Rs {{ number_format((float) $loan->monthly_deduction, 2) }}</td>
        </tr>
        @if($loan->purpose)
        <tr>
            <td style="padding:6px 0; color:#6b7280; vertical-align:top;">Purpose</td>
            <td style="padding:6px 0;">{{ $loan->purpose }}</td>
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
        This confirmation is required so a WhatsApp link preview cannot decide a
        loan on its own.
    </p>
@endif

</div>

</body>
</html>
