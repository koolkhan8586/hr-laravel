<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Deducted — {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            margin: 0;
            padding: 18px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f5132;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f5132;
        }
        .sub-title {
            font-size: 12px;
            margin-top: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px 6px;
        }
        th {
            background: #f3f4f6;
            text-align: left;
            font-size: 10px;
        }
        td.amount, th.amount {
            text-align: right;
        }
        tfoot td {
            font-weight: bold;
            background: #f3f4f6;
        }
        .meta {
            font-size: 10px;
            color: #555;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

@php
    $period = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
    $money  = fn ($n) => number_format((float) $n, 2);
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
    · {{ $rows->count() }} employee(s)
</div>

<table>
    <thead>
        <tr>
            <th style="width:36px;">#</th>
            <th>Employee ID</th>
            <th>Employee Name</th>
            <th>Category</th>
            <th class="amount">Income Tax Deducted</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row->user->employee_code ?? '-' }}</td>
            <td>{{ $row->user->name }}</td>
            <td style="text-transform: capitalize;">{{ $row->user->salary_category ?? '-' }}</td>
            <td class="amount">{{ $money($row->income_tax) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align:center; padding:16px;">
                No posted salary tax deductions for this month.
            </td>
        </tr>
        @endforelse
    </tbody>
    @if($rows->isNotEmpty())
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right;">TOTAL</td>
            <td class="amount">{{ $money($total) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

</body>
</html>
