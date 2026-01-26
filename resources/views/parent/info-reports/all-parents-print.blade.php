<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Parents Report</title>
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
        .empty { text-align: center; color: #666; padding: 24px 0; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">All Parents Report</h1>
            <p class="subtitle">Total: {{ $parents->count() }}</p>
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
                <th>Phone</th>
                <th>WhatsApp</th>
                <th>ID Card</th>
                <th>Students</th>
            </tr>
        </thead>
        <tbody>
            @forelse($parents as $index => $parent)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $parent->name ?? 'N/A' }}</td>
                    <td>{{ $parent->phone ?? 'N/A' }}</td>
                    <td>{{ $parent->whatsapp ?? 'N/A' }}</td>
                    <td>{{ $parent->id_card_number ?? 'N/A' }}</td>
                    <td>
                        @if($parent->students && $parent->students->count() > 0)
                            {{ $parent->students->pluck('student_name')->implode(', ') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">No parents found.</td>
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
