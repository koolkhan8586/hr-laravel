{{--
    The loan application as a document: printed from the browser, or rendered
    by dompdf for the PDF. One view for both, so what comes out of the printer
    is what was on screen.
--}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Loan Application {{ $loan->reference() }}</title>
<style>
    @page { margin: 12mm 14mm; }

    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 12px;
        color: #111827;
        margin: 0;
    }

    .sheet { max-width: 760px; margin: 0 auto; padding: 16px; }

    .header {
        border-bottom: 2px solid #111827;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }

    .header td { vertical-align: middle; border: none; padding: 0; }

    .logo { height: 50px; }

    .org { font-size: 15px; font-weight: bold; line-height: 1.25; }
    .org-sub { font-size: 11px; color: #4b5563; }

    .doc-title {
        text-align: center;
        font-size: 15px;
        font-weight: bold;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }

    .doc-ref { text-align: center; font-size: 11px; color: #4b5563; margin-bottom: 12px; }

    h3 {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #374151;
        border-bottom: 1px solid #d1d5db;
        padding-bottom: 3px;
        margin: 13px 0 5px;
    }

    table.detail { width: 100%; border-collapse: collapse; }

    table.detail td {
        padding: 3px 6px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: top;
    }

    table.detail td.label { width: 32%; color: #6b7280; }

    .purpose {
        border: 1px solid #e5e7eb;
        padding: 8px;
        min-height: 44px;
        white-space: pre-wrap;
    }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 11px;
    }

    .signatures { width: 100%; margin-top: 30px; border-collapse: collapse; }

    .signatures td {
        width: 33%;
        border: none;
        padding-top: 26px;
        font-size: 11px;
        color: #4b5563;
        text-align: center;
    }

    .signatures .line {
        border-top: 1px solid #9ca3af;
        padding-top: 5px;
        margin: 0 10px;
    }

    .foot { margin-top: 18px; font-size: 10px; color: #9ca3af; text-align: center; }

    .print-bar { text-align: right; margin-bottom: 12px; }

    .print-bar button, .print-bar a {
        font-size: 12px;
        padding: 7px 14px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        color: #ffffff;
        background: #2563eb;
    }

    .print-bar a.pdf { background: #4f46e5; margin-left: 6px; }

    @media print { .print-bar { display: none; } }
</style>
</head>
<body>

<div class="sheet">

    {{-- Not rendered into the PDF: dompdf ignores @media print, so this is
         hidden by the isPdf flag instead of by CSS. --}}
    @if(!isset($isPdf))
    <div class="print-bar">
        <button onclick="window.print()">Print</button>
        <a class="pdf" href="{{ route('loan.application.print', [$loan->id, 'pdf' => 1]) }}">Download PDF</a>
    </div>
    @endif

    @php
        // dompdf reads the logo off disk, the same as the attendance report.
        // A missing file is skipped rather than printing a broken image.
        $logo = public_path('uol-logo.png');
        $logoExists = is_file($logo);

        // The same organisation name the attendance report prints, so the two
        // documents carry one letterhead.
        $orgName = \App\Models\AppSetting::get('org_name', 'The University of Lahore (City Campus)');

        $staff = $loan->user->staff ?? null;
    @endphp

    <table class="header">
        <tr>
            @if($logoExists)
            <td style="width:76px;">
                <img class="logo" src="{{ $logo }}" alt="">
            </td>
            @endif
            <td style="padding-left:10px;">
                <div class="org">{{ $orgName }}</div>
                <div class="org-sub">Human Resources - Employee Loan Application</div>
            </td>
            <td style="text-align:right; font-size:10px; color:#4b5563; width:96px;">
                Printed {{ now()->format('d M Y') }}
            </td>
        </tr>
    </table>

    <div class="doc-title">LOAN APPLICATION FORM</div>
    <div class="doc-ref">
        Reference {{ $loan->reference() }}
        &nbsp;|&nbsp;
        Applied {{ ($loan->requested_at ?? $loan->created_at)->format('d M Y h:i A') }}
    </div>

    <h3>Applicant</h3>

    <table class="detail">
        <tr>
            <td class="label">Name</td>
            <td><strong>{{ $loan->user->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Employee Code</td>
            <td>{{ $loan->user->employee_code ?? '-' }}</td>
        </tr>
        @if($staff)
        <tr>
            <td class="label">Designation</td>
            <td>{{ $staff->designation ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Department</td>
            <td>{{ $staff->department ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Date of Joining</td>
            <td>
                {{ $staff->joining_date ? \Carbon\Carbon::parse($staff->joining_date)->format('d M Y') : '-' }}
            </td>
        </tr>
        @endif
        <tr>
            <td class="label">Contact</td>
            <td>{{ $loan->user->mobile ?? $loan->user->email ?? '-' }}</td>
        </tr>
    </table>

    <h3>Loan Requested</h3>

    <table class="detail">
        <tr>
            <td class="label">Amount</td>
            <td><strong>Rs {{ number_format((float) $loan->amount, 2) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Repayment Period</td>
            <td>{{ $loan->installments ? $loan->installments.' month(s)' : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Monthly Deduction</td>
            <td>Rs {{ number_format((float) $loan->monthly_deduction, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Deductions From</td>
            <td>
                {{ $loan->deduction_start_month
                    ? \Carbon\Carbon::parse($loan->deduction_start_month)->format('F Y')
                    : 'Next salary' }}
            </td>
        </tr>
        <tr>
            <td class="label">Already Outstanding</td>
            <td>
                @if($outstanding > 0)
                    Rs {{ number_format($outstanding, 2) }} on loans already running
                @else
                    None
                @endif
            </td>
        </tr>
    </table>

    <h3>Purpose</h3>

    <div class="purpose">{{ $loan->purpose ?: '-' }}</div>

    <h3>Decision</h3>

    <table class="detail">
        <tr>
            <td class="label">Status</td>
            <td>
                @php
                    $palette = [
                        'pending'   => ['Waiting for a decision', '#fef9c3', '#a16207'],
                        'approved'  => ['Approved', '#dcfce7', '#15803d'],
                        'rejected'  => ['Rejected', '#fee2e2', '#b91c1c'],
                        'cancelled' => ['Withdrawn by the employee', '#e5e7eb', '#4b5563'],
                        'closed'    => ['Paid Off', '#e5e7eb', '#374151'],
                        'completed' => ['Paid Off', '#e5e7eb', '#374151'],
                    ];
                    [$statusLabel, $statusBg, $statusColour] = $palette[$loan->status]
                        ?? [ucfirst((string) $loan->status), '#e5e7eb', '#374151'];
                @endphp
                <span class="badge" style="background:{{ $statusBg }}; color:{{ $statusColour }};">
                    {{ $statusLabel }}
                </span>
            </td>
        </tr>
        @if($loan->decided_at)
        <tr>
            <td class="label">Decided By</td>
            <td>
                {{ $loan->decided_by ?: '-' }}
                @if($loan->decided_via)
                    ({{ $loan->decided_via === 'whatsapp' ? 'via WhatsApp' : 'in the HR panel' }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Decided On</td>
            <td>{{ $loan->decided_at->format('d M Y h:i A') }}</td>
        </tr>
        @endif
        @if(filled($loan->decision_remarks))
        <tr>
            <td class="label">Remarks</td>
            <td>{{ $loan->decision_remarks }}</td>
        </tr>
        @endif
    </table>

    <table class="signatures">
        <tr>
            <td><div class="line">Applicant</div></td>
            <td><div class="line">Accounts</div></td>
            <td><div class="line">Approving Authority</div></td>
        </tr>
    </table>

    <div class="foot">
        I undertake that the monthly deduction shown above may be recovered from
        my salary until the loan is repaid in full.
    </div>

</div>

</body>
</html>
