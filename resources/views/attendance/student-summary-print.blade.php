<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Summary</title>
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
        table { width: 100%; border-collapse: collapse; font-size: 7.5px; table-layout: fixed; }
        th, td { border: 1px solid var(--theme-blue); padding: 4px 2px; vertical-align: top; word-wrap: break-word; }
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
        <div class="report-title">Student Attendance Summary</div>
        <div class="meta">Period: {{ $monthLabel }} &nbsp;|&nbsp; Generated: {{ $printedAt }} &nbsp;|&nbsp; Students: {{ $summary->count() }}</div>
    </div>

    <div class="top-bar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th style="width:2.5%;">#</th>
                <th style="width:8%;">Code</th>
                <th style="width:11%;">Student</th>
                <th style="width:9%;">Father</th>
                <th style="width:7%;">Campus</th>
                <th style="width:6%;">Class</th>
                <th style="width:5%;">Sec.</th>
                <th class="num" style="width:6%;">Present</th>
                <th class="num" style="width:6%;">Absent</th>
                <th class="num" style="width:6%;">Leave</th>
                <th class="num" style="width:7%;">Attendance %</th>
            </tr>
            </thead>
            <tbody>
            @forelse($summary as $index => $row)
                @php $student = $row['student']; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                    <td>{{ $student->student_name ?? 'N/A' }}</td>
                    <td>{{ $student->father_name ?? 'N/A' }}</td>
                    <td>{{ $student->campus ?? 'N/A' }}</td>
                    <td>{{ $student->class ?? 'N/A' }}</td>
                    <td>{{ $student->section ?? 'N/A' }}</td>
                    <td class="num">{{ $row['present'] ?? 0 }}</td>
                    <td class="num">{{ $row['absent'] ?? 0 }}</td>
                    <td class="num">{{ $row['leave'] ?? 0 }}</td>
                    <td class="num">{{ number_format($row['percentage'] ?? 0, 2) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;color:#6b7280;">No students found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer-section">
        <div>
            <strong>Total students (listed):</strong> {{ $summary->count() }}
            &nbsp;|&nbsp;
            <span style="font-size:10px;color:#6b7280;">% = Present ÷ (Present + Absent) for the month; all students included (filter by student_id if passed).</span>
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
