<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absent Students Today</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; margin: 0; }
        .print-container { width: 100%; max-width: 297mm; margin: 0 auto; }
        :root { --theme-blue: #003471; }
        .header-section { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 18px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 11px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 14px; font-weight: 800; color: var(--theme-blue); margin-top: 6px; text-transform: uppercase; }
        .meta { font-size: 11px; color: #374151; margin-top: 4px; }
        .top-bar { display: flex; justify-content: flex-end; margin: 8px 0; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }
        .table-wrap { overflow-x: auto; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 8px; table-layout: fixed; }
        th, td { border: 1px solid var(--theme-blue); padding: 4px 3px; vertical-align: top; word-wrap: break-word; }
        th { background: var(--theme-blue); color: #fff; text-align: left; font-weight: 700; }
        .num { text-align: right; }
        .footer-section { border-top: 2px solid var(--theme-blue); margin-top: 10px; padding-top: 8px; display: flex; justify-content: space-between; font-size: 11px; flex-wrap: wrap; gap: 8px; }
        @media print {
            .no-print { display: none !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
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
        <div class="report-title">Absent Students Today</div>
        <div class="meta">Date: {{ $dateLabel }} &nbsp;|&nbsp; Generated: {{ $printedAt }} &nbsp;|&nbsp; Absent: {{ $records->count() }}</div>
    </div>

    <div class="top-bar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th style="width:2.5%;">#</th>
                <th style="width:7%;">Code</th>
                <th style="width:11%;">Student</th>
                <th style="width:9%;">Father</th>
                <th style="width:8%;">Phone</th>
                <th style="width:7%;">Campus</th>
                <th style="width:6%;">Class</th>
                <th style="width:5%;">Sec.</th>
                <th style="width:14%;">Remarks</th>
            </tr>
            </thead>
            <tbody>
            @forelse($records as $index => $attendance)
                @php
                    $student = $attendance->student;
                    $class = $attendance->class ?? $student->class ?? 'N/A';
                    $section = $attendance->section ?? $student->section ?? 'N/A';
                    $phone = $student->father_phone ?? $student->whatsapp_number ?? 'N/A';
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                    <td>{{ $student->student_name ?? 'N/A' }}</td>
                    <td>{{ $student->father_name ?? 'N/A' }}</td>
                    <td>{{ $phone }}</td>
                    <td>{{ $attendance->campus ?? $student->campus ?? 'N/A' }}</td>
                    <td>{{ $class }}</td>
                    <td>{{ $section }}</td>
                    <td>{{ $attendance->remarks ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;color:#6b7280;">No absent students found for this date.</td>
                </tr>
            @endforelse
            @if($records->count() > 0)
                <tr>
                    <td colspan="9" class="num" style="font-weight:700;background:#f3f4f6;">Total absent: {{ $records->count() }}</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>

    <div class="footer-section">
        <div>
            <strong>Total absent (listed):</strong> {{ $records->count() }}
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
