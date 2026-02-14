<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            margin: 40px;
            color: #333;
            position: relative;
        }

        /* WATERMARK */
        .watermark {
            position: fixed;
            top: 35%;
            left: 15%;
            font-size: 80px;
            color: rgba(0, 128, 0, 0.08);
            transform: rotate(-30deg);
            z-index: -1;
        }

        /* HEADER */
        .header {
            text-align: center;
            border-bottom: 3px solid #0f5132;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            height: 60px;
            margin-bottom: 8px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #0f5132;
        }

        /* MONTH BADGE */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            background: #0f5132;
            color: white;
            font-weight: bold;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 6px;
        }

        /* INFO SECTION */
        .info-table {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 6px;
        }

        /* MAIN TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #e9f5ef;
            color: #0f5132;
            padding: 8px;
            border: 1px solid #c7e2d4;
            text-align: left;
        }

        td {
            padding: 8px;
            border: 1px solid #e2e2e2;
        }

        /* NET BOX */
        .net-box {
            margin-top: 25px;
            padding: 15px;
            background: #e9f5ef;
            border: 2px solid #0f5132;
            font-size: 16px;
            font-weight: bold;
            color: #0f5132;
            text-align: center;
        }

        /* SIGNATURE */
        .signature {
            margin-top: 60px;
            text-align: right;
        }

        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
            float: right;
            text-align: center;
            font-size: 12px;
            padding-top: 5px;
        }

        /* FOOTER */
        .footer {
            margin-top: 70px;
            font-size: 11px;
            text-align: center;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

    </style>
</head>

<body>

<div class="watermark">CONFIDENTIAL</div>

<div class="header">
    <img src="{{ public_path('UOL-Green-V1.png') }}" class="logo">
    <div class="title">University of Lahore</div>
    <div class="badge">
        Salary Slip - {{ date('F Y', mktime(0,0,0,$salary->month,1,$salary->year)) }}
    </div>
</div>

<table class="info-table">
    <tr>
        <td><strong>Employee Name:</strong> {{ $salary->user->name }}</td>
        <td><strong>Employee ID:</strong> {{ $salary->user->staff->employee_id ?? 'N/A' }}</td>
    </tr>
    <tr>
        <td><strong>Department:</strong> {{ $salary->user->staff->department ?? 'N/A' }}</td>
        <td><strong>Designation:</strong> {{ $salary->user->staff->designation ?? 'N/A' }}</td>
    </tr>
</table>

<table>
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
            <td>Income Tax</td>
            <td>{{ number_format($salary->income_tax,2) }}</td>
        </tr>

        <tr>
            <td>Invigilation</td>
            <td>{{ number_format($salary->invigilation,2) }}</td>
            <td>Loan Deduction</td>
            <td>{{ number_format($salary->loan_deduction,2) }}</td>
        </tr>

        <tr>
            <td>T. Payment</td>
            <td>{{ number_format($salary->t_payment,2) }}</td>
            <td>Insurance</td>
            <td>{{ number_format($salary->insurance,2) }}</td>
        </tr>

        <tr>
            <td>Eidi</td>
            <td>{{ number_format($salary->eidi,2) }}</td>
            <td>Extra Leaves</td>
            <td>{{ number_format($salary->extra_leaves,2) }}</td>
        </tr>

        <tr>
            <td>Increment</td>
            <td>{{ number_format($salary->increment,2) }}</td>
            <td>Other Deductions</td>
            <td>{{ number_format($salary->other_deductions,2) }}</td>
        </tr>

        <tr>
            <td>Other Earnings</td>
            <td>{{ number_format($salary->other_earnings,2) }}</td>
            <td></td>
            <td></td>
        </tr>

        <tr style="font-weight:bold;">
            <td>Gross Total</td>
            <td>{{ number_format($salary->gross_total,2) }}</td>
            <td>Total Deductions</td>
            <td>{{ number_format($salary->total_deductions,2) }}</td>
        </tr>
    </tbody>
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

<div class="footer">
    This is a computer-generated salary slip and does not require a physical signature.
</div>

</body>
</html>
