<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absent Students Today</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; color: #111; }
        .header { display: flex; justify-content: space-between; margin-bottom: 12px; }
        .title { font-size: 18px; font-weight: 600; margin: 0; }
        .subtitle { font-size: 12px; color: #666; margin: 0; }
        .print-btn { font-size: 12px; padding: 6px 10px; border: 1px solid #003471; background: #003471; color: #fff; border-radius: 4px; cursor: pointer; }
        .printed-at { font-size: 11px; color: #666; margin-top: 4px; text-align: right; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .empty { text-align: center; color: #666; padding: 24px 0; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">Absent Students Today</h1>
            <p class="subtitle">Date: {{ $dateLabel }}</p>
        </div>
        <div>
            <button type="button" class="print-btn no-print" onclick="window.print()">Print</button>
            <div class="printed-at">Printed: {{ $printedAt }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>Student Code</th>
                <th>Father Name</th>
                <th>Phone</th>
                <th>Campus</th>
                <th>Class</th>
                <th>Section</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $attendance)
                @php $student = $attendance->student; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->student_name ?? 'N/A' }}</td>
                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                    <td>{{ $student->father_name ?? 'N/A' }}</td>
                    <td>{{ $student->father_phone ?? $student->whatsapp_number ?? 'N/A' }}</td>
                    <td>{{ $attendance->campus ?? $student->campus ?? 'N/A' }}</td>
                    <td>{{ $attendance->class ?? $student->class ?? 'N/A' }}</td>
                    <td>{{ $attendance->section ?? $student->section ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">No absent students found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
