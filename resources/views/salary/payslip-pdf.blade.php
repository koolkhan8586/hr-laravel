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

        .container {
            padding: 10px 25px;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        .logo {
            width: 120px;
            margin-bottom: 6px;
        }

        .university {
            font-size: 20px;
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
            margin: 10px 0 15px 0;
        }

        .info-table {
            width: 100%;
            margin-bottom: 18px;
        }

        .info-table td {
            padding: 4px 0;
        }

        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .earnings-title { color: #0b6b3a; }
        .deductions-title { color: #c0392b; }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
        }

        .amount {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
        }

        .net-box {
            margin-top: 25px;
            background: #0b6b3a;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
        }

        .signature-section {
            margin-top: 45px;
            width: 100%;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
            text-align: center;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 30px;
            color: #555;
        }
    </style>
</head>

<body>
<div class="container">

    {{-- HEADER --}}
    <div class="header">
        <img src="{{ public_path('UOL-Green-V1.png') }}" class="logo">
        <div class="university">THE UNIVERSITY OF LAHORE</div>
        <div class="campus">City Campus Lahore</div>
    </div>

    <hr>

    {{-- EMPLOYEE INFO --}}
    <table class="info-table">
        <tr>
            <td><strong>Employee Name:</strong> {{ $salary->user->name }}</td>
            <td align="right"><strong>Month:</strong> {{ date('F', mktime(0,0,0,$salary->month,1)) }}</td>
        </tr>
        <tr>
            <td><strong>Employee ID:</strong> {{ $salary->user->staff->employee_id ?? 'N/A' }}</td>
            <td align="right"><strong>Year:</strong> {{ $salary->year }}</td>
        </tr>
        <tr>
            <td><strong>Department:</strong> {{ $salary->user->staff->department ?? 'N/A' }}</td>
            <td align="right"><strong>Date:</strong> {{ now()->format('d-m-Y') }}</td>
        </tr>
    </table>

    {{-- EARNINGS & DEDUCTIONS --}}
    <table width="100%">
        <tr>
            <td width="50%" valign="top">

                <div class="section-title earnings-title">EARNINGS DETAIL</div>

                <table class="detail-table">
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
                    <tr class="total-row">
                        <td>Gross Total</td>
                        <td class="amount">{{ number_format($salary->gross_total,2) }}</td>
                    </tr>
                </table>

            </td>

            <td width="50%" valign="top">

                <div class="section-title deductions-title">DEDUCTIONS DETAIL</div>

                <table class="detail-table">
                    <tr>
                        <td>Extra Leaves</td>
                        <td class="amount">{{ number_format($salary->extra_leaves,2) }}</td>
                    </tr>
                    <tr>
                        <td>Income Tax</td>
                        <td class="amount">{{ number_format($salary->income_tax,2) }}</td>
                    </tr>
                    <tr>
                        <td>Loan Recovery</td>
                        <td class="amount">{{ number_format($salary->loan_deduction,2) }}</td>
                    </tr>
                    <tr>
                        <td>Insurance</td>
                        <td class="amount">{{ number_format($salary->insurance,2) }}</td>
                    </tr>
                    <tr>
                        <td>Other Deductions</td>
                        <td class="amount">{{ number_format($salary->other_deductions,2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total Deductions</td>
                        <td class="amount">{{ number_format($salary->total_deductions,2) }}</td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

    {{-- NET PAY --}}
    <div class="net-box">
        NET PAYABLE AMOUNT — Rs {{ number_format($salary->net_salary,2) }}
    </div>

    {{-- DIGITAL SIGNATURE --}}
    <div class="signature-section">
        <div style="width:40%; float:right;">
            <div class="signature-line">
                Authorized Signature  
                <br>
                HR & Accounts Department
            </div>
        </div>
        <div style="clear:both;"></div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        This is a system generated salary slip and does not require manual signature.
    </div>

</div>
</body>
</html>
