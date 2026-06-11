<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Tree Report</title>
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
        .parent-sub { font-size: 9px; color: #374151; margin-top: 2px; }
        .student-line { margin: 2px 0; padding-left: 0; }
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
        <div class="report-title">Family Tree Report</div>
        <div class="meta">Generated: {{ $printedAt }}</div>
    </div>

    <div class="top-bar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:22%;">Parent</th>
            <th style="width:58%;">Linked students</th>
            <th class="text-right" style="width:16%;">Total Due</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $index => $row)
            @php $parent = $row['parent']; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <div><strong>{{ $parent->name ?? 'N/A' }}</strong></div>
                    <div class="parent-sub">ID: {{ $parent->id_card_number ?? 'N/A' }}</div>
                </td>
                <td>
                    @if(!empty($row['students']) && $row['students']->count() > 0)
                        @foreach($row['students'] as $student)
                            <div class="student-line">
                                {{ $student->student_name ?? 'N/A' }}
                                ({{ $student->student_code ?? 'N/A' }})
                                — {{ $student->class ?? 'N/A' }}{{ $student->section ? ' / ' . $student->section : '' }}
                            </div>
                        @endforeach
                    @else
                        <span style="color:#6b7280;">N/A</span>
                    @endif
                </td>
                <td class="text-right">{{ number_format($row['due_total'] ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;color:#6b7280;">No parents found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div>
            <div><strong>Total parents:</strong> {{ $rows->count() }}</div>
            <div><strong>Total linked students:</strong> {{ $totalLinkedStudents ?? 0 }}</div>
            <div><strong>Grand total due:</strong> {{ number_format($grandTotalDue ?? 0, 2) }}</div>
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
