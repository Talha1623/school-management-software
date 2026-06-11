<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Default Report Print</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; }
        .print-container { width: 210mm; margin: 0 auto; }
        :root { --theme-blue: #003471; }

        .header-section { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 20px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 12px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 16px; font-weight: 800; color: var(--theme-blue); margin-top: 8px; text-transform: uppercase; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; font-size: 12px; color: #374151; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        th, td { border: 1px solid var(--theme-blue); padding: 6px; vertical-align: top; }
        th { background: var(--theme-blue); color: #fff; text-align: left; }
        .text-end { text-align: right; }
        .footer-section { border-top: 2px solid var(--theme-blue); margin-top: 14px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 12px; }
        .no-print { text-align: right; }

        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="print-container">
    <div class="header-section">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="school-details">
            {{ $settings->address ?? '' }}
            @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
            @if(!empty($settings->school_email)) | {{ $settings->school_email }} @endif
        </div>
        <div class="report-title">Fee Default Report</div>
        <div class="school-details" style="margin-top:6px;">
            Generated: {{ $printedAt ?? now()->format('d M Y, h:i A') }}
        </div>
        <div class="school-details" style="margin-top:4px;">
            Campus: {{ $filters['campus'] ?: 'All' }} |
            Class: {{ $filters['class'] ?: 'All' }} |
            Section: {{ $filters['section'] ?: 'All' }} |
            Type: {{ $filters['type'] ?: 'All' }} |
            Status: {{ $filters['status'] ?: 'All' }}
        </div>
    </div>

    <div class="top-bar">
        <div></div>
        <div class="no-print"><button class="print-btn" onclick="window.print()">Print</button></div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>Student Code</th>
            <th>Student</th>
            <th>Parent</th>
            <th>Class</th>
            <th style="width:90px;">Due Invoices</th>
            <th style="width:90px;">Total</th>
            <th style="width:90px;">Paid</th>
            <th style="width:90px;">Late</th>
            <th style="width:100px;">Remaining</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['student_code'] ?? 'N/A' }}</td>
                <td><strong>{{ $row['student_name'] ?? 'N/A' }}</strong></td>
                <td>{{ $row['parent_name'] ?? 'N/A' }}</td>
                <td>{{ $row['class'] ?? 'N/A' }}</td>
                <td class="text-end">{{ $row['due_invoices'] ?? 0 }}</td>
                <td class="text-end">{{ number_format((float)($row['total'] ?? 0), 2) }}</td>
                <td class="text-end">{{ number_format((float)($row['paid'] ?? 0), 2) }}</td>
                <td class="text-end">{{ number_format((float)($row['late'] ?? 0), 2) }}</td>
                <td class="text-end">{{ number_format((float)($row['remaining'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center; color:#6b7280;">No records found.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div><strong>Total:</strong> {{ $rows->count() }}</div>
        <div>System Generated Report</div>
    </div>
</div>

@if(request()->get('auto_print'))
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 300);
    });
</script>
@endif
</body>
</html>
