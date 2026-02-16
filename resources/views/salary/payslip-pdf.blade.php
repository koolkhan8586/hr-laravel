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
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #0f5132;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .logo {
            height: 70px;
            margin-bottom: 5px;
        }

        .company {
            font-size: 16px;
            font-weight: bold;
            color: #0f5132;
        }

        .sub-title {
            font-size: 13px;
            margin-top: 4px;
        }

        .info-table,
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .info-table td {
            padding: 4px 6px;
        }

        .salary-table th,
        .salary-table td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        .salary-table th {
            background: #0f5132;
            color: white;
        }

        .deduction {
            color: red;
        }

        .total {
            font-weight: bold;
            background: #f3f3f3;
        }

        .footer {
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ public_path('UOL-Green-V1.png') }}" class="logo">
    <div class="company">Lahore School of Accountancy and Finance (LSAF)</div>
    <div class="sub-title">
        Salary Slip - {{ date('F', mktime(0,0,0,$salary->month,1)) }} {{ $salary->year }}
    </div>
</div>

<table>
<tr>
    <td class="label">Employee Name:</td>
    <td>{{ $salary->user->name }}</td>

    <td class="label">Month:</td>
    <td>{{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}</td>
</tr>

<tr>
    <td class="label">Employee ID:</td>
    <td>{{ $salary->user->staff->employee_id ?? '' }}</td>

    <td class="label">Year:</td>
    <td>{{ $salary->year }}</td>
</tr>

<tr>
    <td class="label">Department:</td>
    <td>{{ $salary->user->staff->department ?? '' }}</td>

    <td class="label">Date:</td>
    <td>{{ now()->format('d-m-Y') }}</td>
</tr>

<tr>
    <td class="label">Date of Joining:</td>
    <td>{{ optional($salary->user->staff)->joining_date }}</td>
</tr>
</table>

<!-- SPACE -->
<br><br>

<table>
<tr>
    <td width="50%" valign="top">

        <div class="section-title" style="color:green;">EARNINGS DETAIL</div>

        <table>
            <tr><td>Basic Salary</td><td class="amount">{{ number_format($salary->basic_salary,2) }}</td></tr>
            <tr><td>Invigilation</td><td class="amount">{{ number_format($salary->invigilation,2) }}</td></tr>
            <tr><td>T. Payment</td><td class="amount">{{ number_format($salary->t_payment,2) }}</td></tr>
            <tr><td>Eidi</td><td class="amount">{{ number_format($salary->eidi,2) }}</td></tr>
            <tr><td>Increment</td><td class="amount">{{ number_format($salary->increment,2) }}</td></tr>
            <tr><td>Extra Load</td><td class="amount">{{ number_format($salary->other_earnings,2) }}</td></tr>
        </table>

    </td>

    <td width="50%" valign="top">

        <div class="section-title" style="color:red;">DEDUCTIONS DETAIL</div>

        <table>
            <tr><td>Extra Leaves</td><td class="amount">{{ number_format($salary->extra_leaves,2) }}</td></tr>
            <tr><td>Income Tax</td><td class="amount">{{ number_format($salary->income_tax,2) }}</td></tr>
            <tr><td>Loan Recovery</td><td class="amount">{{ number_format($salary->loan_deduction,2) }}</td></tr>
            <tr><td>Insurance</td><td class="amount">{{ number_format($salary->insurance,2) }}</td></tr>
            <tr><td>Other Deductions</td><td class="amount">{{ number_format($salary->other_deductions,2) }}</td></tr>
        </table>

    </td>
</tr>
</table>

<!-- Combined Totals Row -->
<br>

<table>
<tr class="total-row">
    <td width="50%">
        Gross Total: {{ number_format($salary->gross_total,2) }}
    </td>
    <td width="50%" style="text-align:right;">
        Total Deductions: {{ number_format($salary->total_deductions,2) }}
    </td>
</tr>
</table>

<div class="net-box">
    NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
</div>

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
