<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Defaulter Parents Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; color: #111; }
        .header { display: flex; justify-content: space-between; margin-bottom: 12px; gap: 16px; }
        .title { font-size: 18px; font-weight: 600; margin: 0; }
        .subtitle { font-size: 12px; color: #666; margin: 2px 0 0 0; }
        .print-btn { font-size: 12px; padding: 6px 10px; border: 1px solid #003471; background: #003471; color: #fff; border-radius: 4px; cursor: pointer; }
        .printed-at { font-size: 11px; color: #666; margin-top: 4px; text-align: right; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .empty { text-align: center; color: #666; padding: 24px 0; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">Defaulter Parents Report</h1>
            <p class="subtitle">Total: {{ $rows->count() }}</p>
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
                <th>Parent</th>
                <th>Students</th>
                <th class="text-right">Total Due</th>
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
                    <td colspan="4" class="empty">No defaulter parents found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($autoPrint))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
</body>
</html>
