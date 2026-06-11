<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unpaid Invoices Print</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        .header { text-align: center; border-bottom: 2px solid #003471; padding-bottom: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #003471; padding: 5px; }
        th { background: #003471; color: #fff; }
        .no-print { text-align: right; margin-bottom: 8px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="header">
    <h2 style="margin:0;color:#003471;">{{ $settings->school_name ?? 'School' }}</h2>
    <div>List of Unpaid Invoices — {{ $printedAt }}</div>
    @if($filterCampus) <div>Campus: {{ $filterCampus }}</div> @endif
    @if($filterType) <div>Type: {{ $filterType }}</div> @endif
</div>
<div class="no-print"><button onclick="window.print()">Print</button></div>
<table>
    <thead>
        <tr>
            <th>#</th><th>Code</th><th>Name</th><th>Class</th><th>Fee Type</th><th>Expected</th><th>Paid</th><th>Unpaid</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($unpaidInvoices as $i => $row)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row['student_code'] }}</td>
            <td>{{ $row['student_name'] }}</td>
            <td>{{ $row['class'] }}</td>
            <td>{{ $row['fee_type'] }}</td>
            <td>{{ number_format($row['expected_amount'], 2) }}</td>
            <td>{{ number_format($row['paid_amount'], 2) }}</td>
            <td>{{ number_format($row['unpaid_amount'], 2) }}</td>
            <td>{{ $row['status'] }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;">No records</td></tr>
        @endforelse
    </tbody>
</table>
@if(request()->get('auto_print'))
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
@endif
</body>
</html>
