<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Discount Print</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-family: Arial, sans-serif; color: #111827; }
        .print-container { width: 210mm; margin: 0 auto; }
        .header { text-align:center; border-bottom:3px solid #003471; padding-bottom:10px; }
        .school-name { font-size:20px; font-weight:800; color:#003471; }
        .meta { font-size:12px; color:#374151; margin-top:4px; }
        .title { font-size:16px; font-weight:800; color:#003471; margin-top:8px; text-transform:uppercase; }
        .top-bar { display:flex; justify-content:flex-end; margin:10px 0; }
        .print-btn { border:1px solid #003471; background:#003471; color:#fff; padding:6px 12px; }
        table { width:100%; border-collapse:collapse; font-size:11px; }
        th,td { border:1px solid #b7bcc3; padding:6px; }
        th { background:#003471; color:#fff; text-align:left; }
        .text-end { text-align:right; }
        @media print { .top-bar { display:none !important; } }
    </style>
</head>
<body>
<div class="print-container">
    <div class="header">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="meta">{{ $settings->address ?? '' }}</div>
        <div class="title">Fee Discount Report</div>
        <div class="meta">Generated: {{ $printedAt }}</div>
    </div>
    <div class="top-bar"><button class="print-btn" onclick="window.print()">Print</button></div>
    <table>
        <thead>
            <tr><th>#</th><th>Date</th><th>Student Code</th><th>Student Name</th><th>Campus</th><th>Class</th><th>Section</th><th>Payment Title</th><th>Payment Amount</th><th>Discount</th><th>Method</th></tr>
        </thead>
        <tbody>
        @forelse($discountRecords as $i => $record)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ !empty($record['payment_date']) ? date('d M Y', strtotime($record['payment_date'])) : 'N/A' }}</td>
                <td>{{ $record['student_code'] }}</td>
                <td>{{ $record['student_name'] }}</td>
                <td>{{ $record['campus'] }}</td>
                <td>{{ $record['class'] }}</td>
                <td>{{ $record['section'] }}</td>
                <td>{{ $record['payment_title'] }}</td>
                <td class="text-end">{{ number_format($record['payment_amount'], 2) }}</td>
                <td class="text-end">{{ number_format($record['discount'], 2) }}</td>
                <td>{{ $record['method'] }}</td>
            </tr>
        @empty
            <tr><td colspan="11" style="text-align:center;">No discount records found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if(request()->get('auto_print'))
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
@endif
</body>
</html>
