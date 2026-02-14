<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        margin: 30px;
        color: #000;
    }

    .header {
        text-align: center;
        margin-bottom: 10px;
    }

    .logo {
        width: 120px;
        margin-bottom: 5px;
    }

    .title {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 2px;
    }

    .sub-title {
        font-size: 12px;
        margin-bottom: 10px;
    }

    .info-table {
        width: 100%;
        margin-bottom: 15px;
    }

    .info-table td {
        padding: 3px 0;
    }

    .section-title {
        font-weight: bold;
        border-bottom: 1px solid #000;
        padding-bottom: 3px;
        margin-top: 8px;
        margin-bottom: 6px;
    }

    table.salary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    table.salary-table td {
        padding: 4px 0;
    }

    .amount {
        text-align: right;
    }

    .deduction {
        color: #c00000;
    }

    .net-box {
        margin-top: 10px;
        padding: 8px;
        background: #f2f2f2;
        font-weight: bold;
        text-align: center;
        font-size: 13px;
    }

    .footer {
        margin-top: 25px;
        font-size: 10px;
        text-align: center;
    }

</style>
</head>

<body>

<div class="header">
    <img src="{{ public_path('UOL-Green-V1.png') }}" class="logo">
    <div class="title">Lahore School of Accountancy & Finance (LSAF)</div>
    <div class="sub-title">Salary Slip</div>
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
        <td class="amount">{{ number_format($salary->basic_salary,2) }}</td>
    </tr>
    <tr>
        <td>Invigilation</td>
        <td class="amount">{{ number_format($salary->invigilation,2) }}</td>
    </tr>
    <tr>
        <td>T. Payment</td>
        <td class="amount">{{ number_format($salary->t_payment,2) }}</td>
    </tr>
    <tr>
        <td>Eidi</td>
        <td class="amount">{{ number_format($salary->eidi,2) }}</td>
    </tr>
    <tr>
        <td>Increment</td>
        <td class="amount">{{ number_format($salary->increment,2) }}</td>
    </tr>
    <tr>
        <td><strong>Gross Total</strong></td>
        <td class="amount"><strong>{{ number_format($salary->gross_total,2) }}</strong></td>
    </tr>
</table>

<div class="section-title">DEDUCTIONS DETAIL</div>

<table class="salary-table">
    <tr>
        <td>Extra Leaves</td>
        <td class="amount deduction">{{ number_format($salary->extra_leaves,2) }}</td>
    </tr>
    <tr>
        <td>Income Tax</td>
        <td class="amount deduction">{{ number_format($salary->income_tax,2) }}</td>
    </tr>
    <tr>
        <td>Loan Recovery</td>
        <td class="amount deduction">{{ number_format($salary->loan_deduction,2) }}</td>
    </tr>
    <tr>
        <td>Insurance</td>
        <td class="amount deduction">{{ number_format($salary->insurance,2) }}</td>
    </tr>
    <tr>
        <td>Other Deductions</td>
        <td class="amount deduction">{{ number_format($salary->other_deductions,2) }}</td>
    </tr>
    <tr>
        <td><strong>Total Deductions</strong></td>
        <td class="amount deduction"><strong>{{ number_format($salary->total_deductions,2) }}</strong></td>
    </tr>
</table>

<div class="net-box">
    NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
</div>

<div class="footer">
    Authorized Signature <br>
    HR & Accounts Department <br><br>
    This is a system generated salary slip and does not require manual signature.
</div>

</body>
</html>
