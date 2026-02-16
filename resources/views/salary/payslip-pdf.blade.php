<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 25px;
            color: #222;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #0f5132;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            height: 80px;
            margin-bottom: 5px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f5132;
        }

        .sub-title {
            font-size: 13px;
            margin-top: 5px;
        }

        .info-table {
            width: 100%;
            margin-top: 10px;
        }

        .info-table td {
            padding: 5px 4px;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .earnings-title {
            color: #0f5132;
        }

        .deductions-title {
            color: #c82333;
        }

        .details-table {
            width: 100%;
        }

        .details-table td {
            padding: 4px 0;
        }

        .amount {
            text-align: right;
        }

        .spacer {
            height: 20px;
        }

        .totals-row {
            margin-top: 20px;
            font-weight: bold;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .net-box {
            margin-top: 25px;
            background: #0f5132;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            text-align: right;
        }

        .small-note {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>

<!-- ================= HEADER ================= -->
<div class="header">
    <img src="{{ public_path('UOL-Green-V1.png') }}" class="logo">
    <div class="company-name">Lahore School of Accountancy and Finance (LSAF)</div>
    <div class="sub-title">
        Salary Slip - {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }} {{ $salary->year }}
    </div>
</div>


<!-- ================= EMPLOYEE INFO ================= -->
<table class="info-table">
<tr>
    <td><strong>Employee Name:</strong> {{ $salary->user->name }}</td>
    <td><strong>Month:</strong> {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}</td>
</tr>
<tr>
    <td><strong>Employee ID:</strong> {{ $salary->user->staff->employee_id ?? '' }}</td>
    <td><strong>Year:</strong> {{ $salary->year }}</td>
</tr>
<tr>
    <td><strong>Department:</strong> {{ $salary->user->staff->department ?? '' }}</td>
    <td><strong>Date:</strong> {{ now()->format('d-m-Y') }}</td>
</tr>
<tr>
    <td><strong>Date of Joining:</strong>
        {{ optional($salary->user->staff)->joining_date 
            ? \Carbon\Carbon::parse($salary->user->staff->joining_date)->format('d-m-Y') 
            : '' }}
    </td>
</tr>
</table>

<div class="spacer"></div>

<!-- ================= EARNINGS & DEDUCTIONS ================= -->
<table width="100%">
<tr>

    <!-- Earnings -->
    <td width="50%" valign="top">
        <div class="section-title earnings-title">EARNINGS DETAIL</div>
        <table class="details-table">
            <tr><td>Basic Salary</td><td class="amount">{{ number_format($salary->basic_salary,2) }}</td></tr>
            <tr><td>Invigilation</td><td class="amount">{{ number_format($salary->invigilation,2) }}</td></tr>
            <tr><td>T. Payment</td><td class="amount">{{ number_format($salary->t_payment,2) }}</td></tr>
            <tr><td>Eidi</td><td class="amount">{{ number_format($salary->eidi,2) }}</td></tr>
            <tr><td>Increment</td><td class="amount">{{ number_format($salary->increment,2) }}</td></tr>
            <tr><td>Extra Load</td><td class="amount">{{ number_format($salary->other_earnings,2) }}</td></tr>
        </table>
    </td>

    <!-- Deductions -->
    <td width="50%" valign="top">
        <div class="section-title deductions-title">DEDUCTIONS DETAIL</div>
        <table class="details-table">
            <tr><td>Extra Leaves</td><td class="amount">{{ number_format($salary->extra_leaves,2) }}</td></tr>
            <tr><td>Income Tax</td><td class="amount">{{ number_format($salary->income_tax,2) }}</td></tr>
            <tr><td>Loan Recovery</td><td class="amount">{{ number_format($salary->loan_deduction,2) }}</td></tr>
            <tr><td>Insurance</td><td class="amount">{{ number_format($salary->insurance,2) }}</td></tr>
            <tr><td>Other Deductions</td><td class="amount">{{ number_format($salary->other_deductions,2) }}</td></tr>
        </table>
    </td>

</tr>
</table>

<!-- ================= TOTAL ROW ================= -->
<div class="totals-row">
    Gross Total: {{ number_format($salary->gross_total,2) }}
    <span style="float:right;">
        Total Deductions: {{ number_format($salary->total_deductions,2) }}
    </span>
</div>

<!-- ================= NET PAY ================= -->
<div class="net-box">
    NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
</div>

<!-- ================= FOOTER ================= -->
<div class="footer">
    ___________________________<br>
    Authorized Signature<br>
    HR & Accounts Department
</div>

<div class="small-note">
    This is a system generated salary slip and does not require manual signature.
</div>

</body>
</html>
