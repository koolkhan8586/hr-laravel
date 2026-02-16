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

{{-- Employee Info --}}
<table class="info-table">
    <tr>
        <td><strong>Employee Name:</strong> {{ $salary->user->name }}</td>
        <td><strong>Employee ID:</strong> {{ $salary->user->staff->employee_id ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Department:</strong> {{ $salary->user->staff->department ?? '-' }}</td>
        <td><strong>DOJ:</strong>
            {{ optional($salary->user->staff)->joining_date
                ? \Carbon\Carbon::parse($salary->user->staff->joining_date)->format('d-M-Y')
                : '-' }}
        </td>
    </tr>
</table>

{{-- Salary Table --}}
<table class="salary-table">

    <thead>
        <tr>
            <th>Earnings</th>
            <th>Amount (Rs)</th>
            <th>Deductions</th>
            <th>Amount (Rs)</th>
        </tr>
    </thead>

    <tbody>

        <tr>
            <td>Basic Salary</td>
            <td>{{ number_format($salary->basic_salary,2) }}</td>
            <td class="deduction">Extra Leaves</td>
            <td class="deduction">{{ number_format($salary->extra_leaves,2) }}</td>
        </tr>

        <tr>
            <td>Invigilation</td>
            <td>{{ number_format($salary->invigilation,2) }}</td>
            <td class="deduction">Income Tax</td>
            <td class="deduction">{{ number_format($salary->income_tax,2) }}</td>
        </tr>

        <tr>
            <td>T Payment</td>
            <td>{{ number_format($salary->t_payment,2) }}</td>
            <td class="deduction">Loan Deduction</td>
            <td class="deduction">{{ number_format($salary->loan_deduction,2) }}</td>
        </tr>

        <tr>
            <td>Eidi</td>
            <td>{{ number_format($salary->eidi,2) }}</td>
            <td class="deduction">Insurance</td>
            <td class="deduction">{{ number_format($salary->insurance,2) }}</td>
        </tr>

        <tr>
            <td>Increment</td>
            <td>{{ number_format($salary->increment,2) }}</td>
            <td class="deduction">Other Deductions</td>
            <td class="deduction">{{ number_format($salary->other_deductions,2) }}</td>
        </tr>

        <tr>
            <td>Extra Load</td>
            <td>{{ number_format($salary->other_earnings,2) }}</td>
            <td></td>
            <td></td>
        </tr>

        <tr class="total">
            <td>Total Earnings</td>
            <td>{{ number_format($salary->gross_total,2) }}</td>
            <td>Total Deductions</td>
            <td>{{ number_format($salary->total_deductions,2) }}</td>
        </tr>

        <tr class="total">
            <td colspan="2">Net Salary</td>
            <td colspan="2">{{ number_format($salary->net_salary,2) }}</td>
        </tr>

    </tbody>
</table>

<div class="footer">
    Authorized Signature
</div>

</body>
</html>
