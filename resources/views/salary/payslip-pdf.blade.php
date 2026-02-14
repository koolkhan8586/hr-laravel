<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo {
            width: 120px;
            margin-bottom: 5px;
        }

        .university {
            font-size: 18px;
            font-weight: bold;
            color: #0b6b3a;
        }

        .campus {
            font-size: 12px;
            letter-spacing: 1px;
        }

        hr {
            border: 0;
            border-top: 2px solid #0b6b3a;
            margin: 10px 0;
        }

        .info {
            width: 100%;
            margin-bottom: 15px;
        }

        .info td {
            padding: 4px 0;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .earnings {
            color: #0b6b3a;
        }

        .deductions {
            color: #c0392b;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .table td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .total {
            font-weight: bold;
        }

        .net-box {
            margin-top: 15px;
            background: #0b6b3a;
            color: #fff;
            padding: 12px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
            color: #555;
        }
    </style>
</head>

<body>

<div class="header">
    {{-- Replace with your logo path --}}
    <img src="{{ public_path('images/UOL-Green-V1.png') }}" class="logo">
    <div class="university">THE UNIVERSITY OF LAHORE</div>
    <div class="campus">CITY CAMPUS LAHORE</div>
</div>

<hr>

<table class="info">
    <tr>
        <td><strong>Employee:</strong> {{ $salary->user->name }}</td>
        <td align="right"><strong>Month:</strong> {{ date('F', mktime(0,0,0,$salary->month,1)) }}</td>
    </tr>
    <tr>
        <td><strong>Year:</strong> {{ $salary->year }}</td>
        <td align="right"><strong>Date:</strong> {{ now()->format('d-m-Y') }}</td>
    </tr>
</table>

<table width="100%">
<tr>
<td width="50%" valign="top">

    <div class="section-title earnings">EARNINGS DETAIL</div>

    <table class="table">
        <tr><td>Basic Salary</td><td align="right">{{ number_format($salary->basic_salary,2) }}</td></tr>
        <tr><td>Invigilation</td><td align="right">{{ number_format($salary->invigilation,2) }}</td></tr>
        <tr><td>T. Payment</td><td align="right">{{ number_format($salary->t_payment,2) }}</td></tr>
        <tr><td>Eidi</td><td align="right">{{ number_format($salary->eidi,2) }}</td></tr>
        <tr><td>Increment</td><td align="right">{{ number_format($salary->increment,2) }}</td></tr>
        <tr class="total">
            <td>Gross Total</td>
            <td align="right">{{ number_format($salary->gross_total,2) }}</td>
        </tr>
    </table>

</td>

<td width="50%" valign="top">

    <div class="section-title deductions">DEDUCTIONS DETAIL</div>

    <table class="table">
        <tr><td>Extra Leaves</td><td align="right">{{ number_format($salary->extra_leaves,2) }}</td></tr>
        <tr><td>Income Tax</td><td align="right">{{ number_format($salary->income_tax,2) }}</td></tr>
        <tr><td>Loan Recovery</td><td align="right">{{ number_format($salary->loan_deduction,2) }}</td></tr>
        <tr><td>Insurance</td><td align="right">{{ number_format($salary->insurance,2) }}</td></tr>
        <tr><td>Others</td><td align="right">{{ number_format($salary->other_deductions,2) }}</td></tr>
        <tr class="total">
            <td>Total Deductions</td>
            <td align="right">{{ number_format($salary->total_deductions,2) }}</td>
        </tr>
    </table>

</td>
</tr>
</table>

<div class="net-box">
    NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
</div>

<div class="footer">
    COMPUTER GENERATED SLIP • LSAF NEXUS HR SYSTEM VERIFIED
</div>

</body>
</html>
