<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }}</title>
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
        <div class="report-title">{{ $reportTitle }}</div>
        <div class="meta">
            @if(!empty($reportSubtitle))
                {{ $reportSubtitle }}
                <span> | </span>
            @endif
            Generated: {{ $printedAt }}
        </div>
    </div>

    <div class="top-bar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:12%;">Student Code</th>
            <th style="width:18%;">Student Name</th>
            <th style="width:14%;">Parent Name</th>
            <th style="width:8%;">Class</th>
            <th style="width:8%;">Section</th>
            <th style="width:12%;">Campus</th>
            <th style="width:13%;">Admission Date</th>
        </tr>
        </thead>
        <tbody>
        @forelse($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->student_code ?? 'N/A' }}</td>
                <td>{{ $student->student_name }}</td>
                <td>{{ $student->father_name ?? 'N/A' }}</td>
                <td>{{ $student->class ?? 'N/A' }}</td>
                <td>{{ $student->section ?? 'N/A' }}</td>
                <td>{{ $student->campus ?? 'N/A' }}</td>
                <td>{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d-m-Y') : ($student->created_at ? $student->created_at->format('d-m-Y') : 'N/A') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" style="text-align:center;color:#6b7280;">{{ $emptyMessage }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div><strong>{{ $totalLabel }}</strong> {{ $students->count() }}</div>
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
