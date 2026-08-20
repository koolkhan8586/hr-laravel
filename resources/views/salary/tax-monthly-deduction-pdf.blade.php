<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Deducted — {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #222;
            margin: 0;
            padding: 14px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f5132;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f5132;
        }
        .sub-title {
            font-size: 11px;
            margin-top: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 3px 4px;
        }
        th {
            background: #e5e7eb;
            font-size: 8px;
            text-align: left;
        }
        th.amount, td.amount {
            text-align: right;
        }
        th.monthly {
            background: #fecaca;
        }
        td.monthly {
            background: #fef2f2;
            font-weight: bold;
            text-align: right;
        }
        tfoot td {
            font-weight: bold;
            background: #f3f4f6;
        }
        .meta {
            font-size: 9px;
            color: #555;
            margin-bottom: 6px;
        }
        .sub {
            font-weight: normal;
            font-size: 7px;
            display: block;
        }
    </style>
</head>
<body>

@php
    $period  = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
    $divisor = $medicalDivisor ?: 1.1;
    $money   = fn ($n) => ((float) $n) != 0
        ? number_format((float) $n, 2)
        : '';
@endphp

<div class="header">
    <div class="company-name">{{ $orgName }}</div>
    <div class="sub-title">Income Tax Deducted — {{ $period }}</div>
    @if(($category ?? 'all') !== 'all')
        <div class="sub-title" style="text-transform: capitalize;">{{ $category }} sheet</div>
    @endif
</div>

<div class="meta">
    Generated {{ now()->format('d M Y h:i A') }}
    · {{ $rows->count() }} employee(s) with tax deducted
    · Employees with 0 tax are excluded
</div>

<table>
    <thead>
        <tr>
            <th>Employee ID</th>
            <th>Employee Name</th>
            <th>CNIC</th>
            <th class="amount">Monthly Salary<span class="sub">{{ $period }}</span></th>
            <th class="amount">Salary &amp; Wages<span class="sub">yearly</span></th>
            <th class="amount">Additional Income<span class="sub">yearly</span></th>
            <th class="amount">Taxable Income<span class="sub">&divide; {{ $divisor }} (less medical)</span></th>
            <th class="amount">Payable Tax<span class="sub">yearly</span></th>
            <th class="amount">Tax Adjustment</th>
            <th class="amount">Net Payable Tax</th>
            <th class="amount monthly">Monthly Tax<span class="sub">{{ $period }}</span></th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row->user->employee_code ?? '-' }}</td>
            <td>{{ $row->user->name }}</td>
            <td>{{ $row->user->cnic ?: '—' }}</td>
            <td class="amount">{{ $money($row->monthly_salary) }}</td>
            <td class="amount">{{ $money($row->annual) }}</td>
            <td class="amount">{{ $money($row->additional) }}</td>
            <td class="amount">{{ $money($row->taxable) }}</td>
            <td class="amount">{{ $money($row->payable) }}</td>
            <td class="amount">{{ $money($row->adjustment) }}</td>
            <td class="amount">{{ $money($row->net) }}</td>
            <td class="monthly">{{ number_format($row->monthly) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="11" style="text-align:center; padding:16px;">
                No employees with tax deducted for this month.
            </td>
        </tr>
        @endforelse
    </tbody>
    @if($rows->isNotEmpty())
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">TOTAL</td>
            <td class="amount">{{ $money($totalMonthlySalary ?? $rows->sum('monthly_salary')) }}</td>
            <td class="amount">{{ $money($rows->sum('annual')) }}</td>
            <td class="amount">{{ $money($rows->sum('additional')) }}</td>
            <td class="amount">{{ $money($rows->sum('taxable')) }}</td>
            <td class="amount">{{ $money($rows->sum('payable')) }}</td>
            <td class="amount">{{ $money($rows->sum('adjustment')) }}</td>
            <td class="amount">{{ $money($rows->sum('net')) }}</td>
            <td class="monthly">{{ number_format($total) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

</body>
</html>
