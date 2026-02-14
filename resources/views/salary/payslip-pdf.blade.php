<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo {
            position: absolute;
            left: 30px;
            top: 20px;
            width: 120px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
        }

        .subtitle {
            font-size: 14px;
        }

        .info-table {
            width: 100%;
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 4px;
        }

        .section-title {
            background: #f0f0f0;
            font-weight: bold;
            padding: 6px;
            margin-top: 15px;
        }

        table.salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .salary-table td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }

        .right {
            text-align: right;
        }

        .net-box {
            margin-top: 20px;
            padding: 10px;
            background: #e8f5e9;
            font-weight: bold;
            font-size: 15px;
            text-align: center;
            border: 1px solid #2e7d32;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
            font-size: 12px;
        }

        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
            float: right;
            text-align: center;
            padding-top: 5px;
        }
    </style>
</head>

<body>

<img src="{{ public_path('UOL-Green-V1.png') }}" class="logo">

<div class="header">
    <div class="title">THE UNIVERSITY OF LAHORE</div>
    <div class="subtitle">City Campus Lahore</div>
</div>

<table class="info-table">
    <tr>
        <td><strong>Employee Name:</strong> {{ $salary->user->name }}</td>
        <td><strong>Month:</strong> {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}</td>
    </tr>
    <tr>
        <td><strong>Employee ID:</strong> {{ $salary->user->staff->employee_id ?? 'N/A' }}</td>
        <td><strong>Year:</strong> {{ $salary->year }}</td>
    </tr>
    <tr>
        <td><strong>Department:</strong> {{ $salary->user->staff->department ?? 'N/A' }}</td>
        <td><strong>Date:</strong> {{ now()->format('d-m-Y') }}</td>
    </tr>
</table>

<div class="section-title">EARNINGS DETAIL</div>

<table class="salary-table">
    <tr>
        <td>Basic Salary</td>
        <td class="right">{{ number_format($salary->basic_salary,2) }}</td>
    </tr>
    <tr>
        <td>Invigilation</td>
        <td class="right">{{ number_format($salary->invigilation,2) }}</td>
    </tr>
    <tr>
        <td>T. Payment</td>
        <td class="right">{{ number_format($salary->t_payment,2) }}</td>
    </tr>
    <tr>
        <td>Eidi</td>
        <td class="right">{{ number_format($salary->eidi,2) }}</td>
    </tr>
    <tr>
        <td>Increment</td>
        <td class="right">{{ number_format($salary->increment,2) }}</td>
    </tr>
    <tr>
        <td>Other Earnings</td>
        <td class="right">{{ number_format($salary->other_earnings,2) }}</td>
    </tr>
    <tr>
        <td><strong>Gross Total</strong></td>
        <td class="right"><strong>{{ number_format($salary->gross_total,2) }}</strong></td>
    </tr>
</table>

<div class="section-title">DEDUCTIONS DETAIL</div>

<table class="salary-table">
    <tr>
        <td>Extra Leaves</td>
        <td class="right">{{ number_format($salary->extra_leaves,2) }}</td>
    </tr>
    <tr>
        <td>Income Tax</td>
        <td class="right">{{ number_format($salary->income_tax,2) }}</td>
    </tr>
    <tr>
        <td>Loan Recovery</td>
        <td class="right">{{ number_format($salary->loan_deduction,2) }}</td>
    </tr>
    <tr>
        <td>Insurance</td>
        <td class="right">{{ number_format($salary->insurance,2) }}</td>
    </tr>
    <tr>
        <td>Other Deductions</td>
        <td class="right">{{ number_format($salary->other_deductions,2) }}</td>
    </tr>
    <tr>
        <td><strong>Total Deductions</strong></td>
        <td class="right"><strong>{{ number_format($salary->total_deductions,2) }}</strong></td>
    </tr>
</table>

<div class="net-box">
    NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
</div>

<div class="signature">
    <div class="signature-line">
        Authorized Signature<br>
        HR & Accounts Department
    </div>
</div>

<div style="clear:both; margin-top:60px; font-size:11px; text-align:center;">
    This is a system generated salary slip and does not require manual signature.
</div>

</body>
</html>
