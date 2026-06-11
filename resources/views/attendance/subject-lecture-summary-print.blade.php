<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject / Lecture Attendance Summary</title>
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
        table { width: 100%; border-collapse: collapse; font-size: 8.5px; table-layout: fixed; }
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
        <div class="report-title">Subject / Lecture Attendance Summary</div>
        <div class="meta">
            Period: {{ $monthLabel }}
            @if(!empty($filterCampus)) &nbsp;|&nbsp; Campus: {{ $filterCampus }} @endif
            &nbsp;|&nbsp; Generated: {{ $printedAt }}
            &nbsp;|&nbsp; Teachers: {{ $summary->count() }}
            &nbsp;|&nbsp; Total lectures: {{ $totalLectures }}
        </div>
    </div>

    <div class="top-bar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th style="width:3%;">#</th>
                <th style="width:8%;">Emp ID</th>
                <th style="width:18%;">Name</th>
                <th style="width:10%;">Campus</th>
                <th style="width:14%;">Designation</th>
                <th class="num" style="width:10%;">Lectures (sum)</th>
            </tr>
            </thead>
            <tbody>
            @forelse($summary as $index => $row)
                @php $staff = $row['staff']; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $staff->emp_id ?? 'N/A' }}</td>
                    <td>{{ $staff->name ?? 'N/A' }}</td>
                    <td>{{ $staff->campus ?? 'N/A' }}</td>
                    <td>{{ $staff->designation ?? 'N/A' }}</td>
                    <td class="num">{{ $row['total_lectures'] ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#6b7280;">No lecture records for this period (teachers only).</td>
                </tr>
            @endforelse
            @if($summary->count() > 0)
                <tr>
                    <td colspan="5" class="num" style="font-weight:700;background:#f3f4f6;">Total</td>
                    <td class="num" style="font-weight:700;background:#f3f4f6;">{{ $totalLectures }}</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>

    <div class="footer-section">
        <div>
            <strong>Teachers listed:</strong> {{ $summary->count() }}
            &nbsp;|&nbsp;
            <span style="font-size:10px;color:#6b7280;">Rows include staff whose designation contains &ldquo;teacher&rdquo;; lectures summed from conducted_lectures for the month.</span>
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
