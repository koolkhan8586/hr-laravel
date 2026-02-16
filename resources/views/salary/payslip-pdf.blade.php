<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Salary Slip</title>

<style>
body {
    font-family: DejaVu Sans, sans-serif;
    margin: 0;
    padding: 30px;
    font-size: 13px;
    color: #333;
}

.header {
    text-align: center;
}

.logo {
    height: 70px;
    margin-bottom: 5px;
}

.university {
    font-size: 20px;
    font-weight: bold;
    color: #0d5c2f;
    letter-spacing: 1px;
}

.sub-campus {
    font-size: 13px;
    margin-top: 3px;
}

.divider {
    border-bottom: 2px solid #0d5c2f;
    margin: 15px 0 25px 0;
}

.info-section {
    width: 100%;
    margin-bottom: 25px;
}

.info-left {
    float: left;
    width: 60%;
}

.info-right {
    float: right;
    width: 40%;
    text-align: right;
}

.info-section p {
    margin: 6px 0;
}

.section-title {
    font-weight: bold;
    margin-bottom: 10px;
}

.earnings-title {
    color: #0d5c2f;
}

.deductions-title {
    color: #c0392b;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.table td {
    padding: 6px 0;
    border-bottom: 1px solid #ddd;
}

.table td:last-child {
    text-align: right;
}

.total-row {
    font-weight: bold;
    border-top: 2px solid #000;
}

.net-box {
    background: #0d5c2f;
    color: white;
    text-align: center;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
    margin-top: 20px;
    border-radius: 4px;
}

.signature {
    margin-top: 60px;
    text-align: right;
}

.signature-line {
    width: 250px;
    border-top: 1px solid #333;
    margin-left: auto;
    margin-bottom: 5px;
}

.footer-note {
    text-align: center;
    font-size: 11px;
    margin-top: 40px;
    color: #666;
}

.clear {
    clear: both;
}
</style>

</head>
<body>

{{-- HEADER --}}
<div class="header">
    <img src="{{ public_path('UOL-Green-V1.png') }}" class="logo">
    <div class="university">THE UNIVERSITY OF LAHORE</div>
    <div class="sub-campus">City Campus Lahore</div>
</div>

<div class="divider"></div>

{{-- EMPLOYEE INFO --}}
<div class="info-section">
    <div class="info-left">
        <p><strong>Employee Name:</strong> {{ $salary->user->name }}</p>
        <p><strong>Employee ID:</strong> {{ $salary->user->staff->employee_id ?? '-' }}</p>
        <p><strong>Department:</strong> {{ $salary->user->staff->department ?? '-' }}</p>
    </div>

    <div class="info-right">
        <p><strong>Month:</strong> {{ date('F', mktime(0,0,0,$salary->month,1)) }}</p>
        <p><strong>Year:</strong> {{ $salary->year }}</p>
        <p><strong>Date:</strong> {{ now()->format('d-m-Y') }}</p>
    </div>
</div>

<div class="clear"></div>

{{-- SALARY DETAILS --}}
<table width="100%">
<tr>

<td width="50%" valign="top">

    <div class="section-title earnings-title">EARNINGS DETAIL</div>

    <table class="table">
        <tr><td>Basic Salary</td><td>{{ number_format($salary->basic_salary,2) }}</td></tr>
        <tr><td>Invigilation</td><td>{{ number_format($salary->invigilation,2) }}</td></tr>
        <tr><td>T. Payment</td><td>{{ number_format($salary->t_payment,2) }}</td></tr>
        <tr><td>Eidi</td><td>{{ number_format($salary->eidi,2) }}</td></tr>
        <tr><td>Increment</td><td>{{ number_format($salary->increment,2) }}</td></tr>
        <tr><td>Extra Load</td><td>{{ number_format($salary->other_earnings,2) }}</td></tr>

        <tr class="total-row">
            <td>Gross Total</td>
            <td>{{ number_format($salary->gross_total,2) }}</td>
        </tr>
    </table>

</td>

<td width="50%" valign="top">

    <div class="section-title deductions-title">DEDUCTIONS DETAIL</div>

    <table class="table">
        <tr><td>Extra Leaves</td><td>{{ number_format($salary->extra_leaves,2) }}</td></tr>
        <tr><td>Income Tax</td><td>{{ number_format($salary->income_tax,2) }}</td></tr>
        <tr><td>Loan Recovery</td><td>{{ number_format($salary->loan_deduction,2) }}</td></tr>
        <tr><td>Insurance</td><td>{{ number_format($salary->insurance,2) }}</td></tr>
        <tr><td>Other Deductions</td><td>{{ number_format($salary->other_deductions,2) }}</td></tr>

        <tr class="total-row">
            <td>Total Deductions</td>
            <td>{{ number_format($salary->total_deductions,2) }}</td>
        </tr>
    </table>

</td>

</tr>
</table>

{{-- NET PAY --}}
<div class="net-box">
    NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
</div>

{{-- SIGNATURE --}}
<div class="signature">
    <div class="signature-line"></div>
    Authorized Signature<br>
    HR & Accounts Department
</div>

{{-- FOOTER --}}
<div class="footer-note">
    This is a system generated salary slip and does not require manual signature.
</div>

</body>
</html>
