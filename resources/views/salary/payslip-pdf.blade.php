<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: DejaVu Sans; font-size: 12px; }
        .header { text-align:center; margin-bottom:20px; }
        .title { font-size:18px; font-weight:bold; }
        table { width:100%; border-collapse: collapse; margin-top:15px;}
        th, td { border:1px solid #000; padding:6px; text-align:left; }
        .no-border td { border:none; }
    </style>
</head>
<body>

<div class="header">
    <div class="title">University of Lahore</div>
    <div>Salary Slip</div>
</div>

<table class="no-border">
<tr>
    <td><strong>Employee:</strong> {{ $salary->user->name }}</td>
    <td><strong>Month:</strong> {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }}</td>
</tr>
<tr>
    <td><strong>Year:</strong> {{ $salary->year }}</td>
    <td><strong>Date:</strong> {{ now()->format('d-m-Y') }}</td>
</tr>
</table>

<h4>Earnings</h4>
<table>
<tr><td>Basic Salary</td><td>{{ number_format($salary->basic_salary,2) }}</td></tr>
<tr><td>Invigilation</td><td>{{ number_format($salary->invigilation,2) }}</td></tr>
<tr><td>T Payment</td><td>{{ number_format($salary->t_payment,2) }}</td></tr>
<tr><td>Eidi</td><td>{{ number_format($salary->eidi,2) }}</td></tr>
<tr><td>Increment</td><td>{{ number_format($salary->increment,2) }}</td></tr>
<tr><td><strong>Gross Total</strong></td><td><strong>{{ number_format($salary->gross_total,2) }}</strong></td></tr>
</table>

<h4>Deductions</h4>
<table>
<tr><td>Extra Leaves</td><td>{{ number_format($salary->extra_leaves,2) }}</td></tr>
<tr><td>Income Tax</td><td>{{ number_format($salary->income_tax,2) }}</td></tr>
<tr><td>Loan Deduction</td><td>{{ number_format($salary->loan_deduction,2) }}</td></tr>
<tr><td>Insurance</td><td>{{ number_format($salary->insurance,2) }}</td></tr>
<tr><td><strong>Total Deductions</strong></td><td><strong>{{ number_format($salary->total_deductions,2) }}</strong></td></tr>
</table>

<h3 style="margin-top:20px;">
Net Salary: Rs {{ number_format($salary->net_salary,2) }}
</h3>

</body>
</html>
