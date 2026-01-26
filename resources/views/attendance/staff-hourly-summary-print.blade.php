<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Hourly Attendance Summary</title>
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
            <h1 class="title">Staff Hourly Attendance Summary</h1>
            <p class="subtitle">Month: {{ $monthLabel }}</p>
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
                <th>Emp. ID</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Total Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summary as $index => $row)
                @php $staff = $row['staff']; @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $staff->emp_id ?? 'N/A' }}</td>
                    <td>{{ $staff->name ?? 'N/A' }}</td>
                    <td>{{ $staff->designation ?? 'N/A' }}</td>
                    <td>{{ number_format(($row['total_minutes'] ?? 0) / 60, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">No hourly data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
