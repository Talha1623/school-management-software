<!DOCTYPE html>
<html>
<head>
    <title>Loan Defaulter Teachers Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        h2 { margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h2>Loan Defaulter Teachers Report</h2>
    <div>Printed at: {{ now()->format('d M Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Staff</th>
                <th>Campus</th>
                <th class="text-end">Approved Amount</th>
                <th class="text-end">Repaid</th>
                <th class="text-end">Due</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse($loanDefaulters as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row['loan']->staff->name ?? 'N/A' }}</td>
                <td>{{ $row['loan']->staff->campus ?? 'N/A' }}</td>
                <td class="text-end">{{ number_format($row['loan']->approved_amount ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($row['repaid'] ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($row['due'] ?? 0, 2) }}</td>
                <td>{{ $row['loan']->status ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No loan defaulters found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <script>
        window.print();
    </script>
</body>
</html>
