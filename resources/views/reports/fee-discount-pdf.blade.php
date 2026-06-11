<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Discount Report</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; }
        .header { text-align:center; border-bottom:2px solid #003471; padding-bottom:8px; }
        .school-name { font-size:18px; font-weight:700; color:#003471; }
        .meta { font-size:11px; color:#374151; margin-top:3px; }
        .title { font-size:14px; font-weight:700; color:#003471; margin-top:6px; text-transform:uppercase; }
        table { width:100%; border-collapse:collapse; margin-top:10px; font-size:10px; }
        th,td { border:1px solid #003471; padding:5px; }
        th { background:#003471; color:#fff; text-align:left; }
        .text-end { text-align:right; }
    </style>
</head>
<body>
<div class="header">
    <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
    <div class="meta">{{ $settings->address ?? '' }}</div>
    <div class="title">Fee Discount Report</div>
    <div class="meta">Generated: {{ $printedAt }}</div>
</div>
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
</body>
</html>
