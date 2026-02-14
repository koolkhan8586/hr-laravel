<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .container {
            border: 2px solid #0b6b3a;
            padding: 20px 25px;
            position: relative;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 40%;
            left: 25%;
            font-size: 70px;
            color: rgba(0,0,0,0.05);
            transform: rotate(-30deg);
        }

        .header {
            text-align: center;
        }

        .header img {
            height: 70px;
            margin-bottom: 5px;
        }

        .header h2 {
            margin: 2px 0;
            font-size: 16px;
        }

        .subheading {
            font-size: 12px;
        }

        .info-table {
            width: 100%;
            margin-top: 10px;
        }

        .info-table td {
            padding: 2px 0;
        }

        .section-title {
            font-weight: bold;
            margin-top: 12px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        table.detail {
            width: 100%;
            margin-top: 5px;
            border-collapse: collapse;
        }

        table.detail td {
            padding: 3px 0;
        }

        .amount {
            text-align: right;
        }

        .deduction {
            color: red;
        }

        .net-box {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
            font-size: 11px;
        }

    </style>
</head>

<body>

<div class="watermark">CONFIDENTIAL</div>

<div class="container">

    <div class="header">
        <img src="{{ public_path('UOL-Green-V1.png') }}">
        <h2>Lahore School of Accountancy and Finance (LSAF)</h2>
        <div class="subheading">City Campus Lahore</div>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Employee Name:</strong> {{ $salary->user->name }}</td>
            <td><strong>Month:</strong> {{ date('F', mktime(0,0,0,$salary->month,1)) }}</td>
        </tr>
        <tr>
            <td><strong>Employee ID:</strong> {{ $salary->user->employee_id ?? 'N/A' }}</td>
            <td><strong>Year:</strong> {{ $salary->year }}</td>
        </tr>
        <tr>
            <td><strong>Department:</strong> {{ $salary->user->staff->department ?? 'N/A' }}</td>
            <td><strong>Date:</strong> {{ now()->format('d-m-Y') }}</td>
        </tr>
    </table>

    <div class="section-title">EARNINGS DETAIL</div>

    <table class="detail">
        <tr><td>Basic Salary</td><td class="amount">{{ number_format($salary->basic_salary,2) }}</td></tr>
        <tr><td>Invigilation</td><td class="amount">{{ number_format($salary->invigilation,2) }}</td></tr>
        <tr><td>T. Payment</td><td class="amount">{{ number_format($salary->t_payment,2) }}</td></tr>
        <tr><td>Eidi</td><td class="amount">{{ number_format($salary->eidi,2) }}</td></tr>
        <tr><td>Increment</td><td class="amount">{{ number_format($salary->increment,2) }}</td></tr>
        <tr><td><strong>Gross Total</strong></td><td class="amount"><strong>{{ number_format($salary->gross_total,2) }}</strong></td></tr>
    </table>

    <div class="section-title">DEDUCTIONS DETAIL</div>

    <table class="detail">
        <tr><td>Extra Leaves</td><td class="amount deduction">{{ number_format($salary->extra_leaves,2) }}</td></tr>
        <tr><td>Income Tax</td><td class="amount deduction">{{ number_format($salary->income_tax,2) }}</td></tr>
        <tr><td>Loan Recovery</td><td class="amount deduction">{{ number_format($salary->loan_deduction,2) }}</td></tr>
        <tr><td>Insurance</td><td class="amount deduction">{{ number_format($salary->insurance,2) }}</td></tr>
        <tr><td>Other Deductions</td><td class="amount deduction">{{ number_format($salary->other_deductions,2) }}</td></tr>
        <tr><td><strong>Total Deductions</strong></td><td class="amount deduction"><strong>{{ number_format($salary->total_deductions,2) }}</strong></td></tr>
    </table>

    <div class="net-box">
        NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
    </div>

    <div class="signature">
        Authorized Signature<br>
        HR & Accounts Department<br><br>
        This is a system generated salary slip and does not require manual signature.
    </div>

</div>

</body>
</html>
