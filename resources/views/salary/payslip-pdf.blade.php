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
            padding: 30px;
            position: relative;
        }

        /* WATERMARK */
        .watermark {
            position: fixed;
            top: 40%;
            left: 20%;
            font-size: 70px;
            color: rgba(0, 128, 0, 0.07);
            transform: rotate(-30deg);
            z-index: -1;
        }

        /* BORDER */
        .container {
            border: 3px solid #006633;
            padding: 25px;
        }

        /* HEADER */
        .header {
            text-align: center;
            border-bottom: 2px solid #006633;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            height: 70px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #006633;
        }

        .badge {
            display: inline-block;
            background: #006633;
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            margin-top: 5px;
        }

        /* EMPLOYEE INFO */
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 4px 0;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #006633;
            color: #fff;
            padding: 8px;
            text-align: left;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }

        .amount {
            text-align: right;
        }

        .deduction {
            color: red;
        }

        .total-row {
            font-weight: bold;
            border-top: 2px solid #006633;
        }

        .net-box {
            background: #f5fff7;
            border: 2px solid #006633;
            padding: 10px;
            font-size: 15px;
            font-weight: bold;
            text-align: right;
        }

        /* SIGNATURE */
        .signature {
            margin-top: 50px;
            text-align: right;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin-left: auto;
            padding-top: 5px;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
            color: #555;
        }

    </style>
</head>
<body>

<div class="watermark">CONFIDENTIAL</div>

<div class="container">

    <div class="header">
        <img src="{{ public_path('UOL-Green-V1.png') }}" class="logo">

        <div class="title">
            Lahore School of Accountancy & Finance (LSAF)
        </div>

        <div class="badge">
            Salary Slip - {{ \Carbon\Carbon::create()->month($salary->month)->format('F') }} {{ $salary->year }}
        </div>
    </div>

    {{-- EMPLOYEE INFO --}}
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

    {{-- EARNINGS --}}
    <table>
        <thead>
            <tr>
                <th>Earnings</th>
                <th class="amount">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Basic Salary</td><td class="amount">{{ number_format($salary->basic_salary,2) }}</td></tr>
            <tr><td>Invigilation</td><td class="amount">{{ number_format($salary->invigilation,2) }}</td></tr>
            <tr><td>T. Payment</td><td class="amount">{{ number_format($salary->t_payment,2) }}</td></tr>
            <tr><td>Eidi</td><td class="amount">{{ number_format($salary->eidi,2) }}</td></tr>
            <tr><td>Increment</td><td class="amount">{{ number_format($salary->increment,2) }}</td></tr>
            <tr><td>Other Earnings</td><td class="amount">{{ number_format($salary->other_earnings,2) }}</td></tr>

            <tr class="total-row">
                <td>Gross Total</td>
                <td class="amount">{{ number_format($salary->gross_total,2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- DEDUCTIONS --}}
    <table>
        <thead>
            <tr>
                <th>Deductions</th>
                <th class="amount">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Extra Leaves</td><td class="amount deduction">{{ number_format($salary->extra_leaves,2) }}</td></tr>
            <tr><td>Income Tax</td><td class="amount deduction">{{ number_format($salary->income_tax,2) }}</td></tr>
            <tr><td>Loan Recovery</td><td class="amount deduction">{{ number_format($salary->loan_deduction,2) }}</td></tr>
            <tr><td>Insurance</td><td class="amount deduction">{{ number_format($salary->insurance,2) }}</td></tr>
            <tr><td>Other Deductions</td><td class="amount deduction">{{ number_format($salary->other_deductions,2) }}</td></tr>

            <tr class="total-row">
                <td>Total Deductions</td>
                <td class="amount deduction">{{ number_format($salary->total_deductions,2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- NET --}}
    <div class="net-box">
        NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
    </div>

    {{-- SIGNATURE --}}
    <div class="signature">
        <div class="signature-line">
            Authorized Signature  
            <br>HR & Accounts Department
        </div>
    </div>

    <div class="footer">
        This is a system generated salary slip and does not require manual signature.
    </div>

</div>

</body>
</html>
