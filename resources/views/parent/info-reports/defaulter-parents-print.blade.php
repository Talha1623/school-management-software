<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defaulter Parents Report</title>
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
        .meta { font-size: 12px; color: #374151; margin-top: 4px; }
        .top-bar { display: flex; justify-content: flex-end; margin: 10px 0; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10px; }
        th, td { border: 1px solid var(--theme-blue); padding: 5px; vertical-align: top; word-break: break-word; }
        th { background: var(--theme-blue); color: #fff; text-align: left; }
        .text-right { text-align: right; }
        .footer-section { border-top: 2px solid var(--theme-blue); margin-top: 14px; padding-top: 10px; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; font-size: 12px; flex-wrap: wrap; }
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
        <div class="report-title">Defaulter Parents Report</div>
        <div class="meta">Generated: {{ $printedAt }} &nbsp;|&nbsp; Records: {{ $rows->count() }}</div>
    </div>

    <div class="top-bar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:46%;">Parent</th>
            <th style="width:12%;">Students</th>
            <th class="text-right" style="width:18%;">Total Due</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row['parent']->name ?? 'N/A' }}</td>
                <td>{{ $row['student_count'] ?? 0 }}</td>
                <td class="text-right">{{ number_format($row['due_total'] ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;color:#6b7280;">No defaulter parents found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div>
            <div><strong>Total defaulters (listed):</strong> {{ $rows->count() }}</div>
            <div><strong>Grand total due:</strong> {{ number_format($grandTotal ?? 0, 2) }}</div>
        </div>
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
