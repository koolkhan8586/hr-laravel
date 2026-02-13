<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans; }
        .header { text-align:center; }
        .green { color:#0d7a33; }
        .box { border:1px solid #ddd; padding:10px; margin-bottom:15px; }
        table { width:100%; border-collapse:collapse; }
        th,td { padding:6px; border-bottom:1px solid #ddd; }
        .net { background:#0d7a33; color:white; padding:12px; text-align:center; font-size:20px; }
    </style>
</head>
<body>

<div class="header">
    <h2 class="green">THE UNIVERSITY OF LAHORE</h2>
    <p>City Campus Lahore</p>
</div>

<p><strong>Staff:</strong> {{ $salary->user->name }}</p>
<p><strong>Month:</strong> {{ $salary->month }}/{{ $salary->year }}</p>

<div class="box">
    <h4>Earnings</h4>
    <table>
        <tr><td>Basic Salary</td><td>{{ $salary->basic_salary }}</td></tr>
        <tr><td>Increment</td><td>{{ $salary->increment }}</td></tr>
        <tr><td>Invigilation</td><td>{{ $salary->invigilation }}</td></tr>
        <tr><td>T Payment</td><td>{{ $salary->t_payment }}</td></tr>
        <tr><th>Gross Total</th><th>{{ $salary->gross_total }}</th></tr>
    </table>
</div>

<div class="box">
    <h4>Deductions</h4>
    <table>
        <tr><td>Income Tax</td><td>{{ $salary->income_tax }}</td></tr>
        <tr><td>Loan</td><td>{{ $salary->loan_deduction }}</td></tr>
        <tr><td>Insurance</td><td>{{ $salary->insurance }}</td></tr>
        <tr><td>Others</td><td>{{ $salary->others }}</td></tr>
        <tr><th>Total Deductions</th><th>{{ $salary->total_deductions }}</th></tr>
    </table>
</div>

<div class="net">
    NET PAYABLE AMOUNT: Rs {{ number_format($salary->net_salary) }}
</div>

</body>
</html>
