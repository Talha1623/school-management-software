<!DOCTYPE html>
<html>
<head>
    <title>Loan Applications Report</title>
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
    <h2>Loan Applications Report</h2>
    <div>Printed at: {{ now()->format('d M Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Staff</th>
                <th>Campus</th>
                <th class="text-end">Requested Amount</th>
                <th class="text-end">Approved Amount</th>
                <th>Instalments</th>
                <th>Status</th>
                <th>Applied Date</th>
            </tr>
        </thead>
        <tbody>
        @forelse($loanApplications as $index => $loan)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $loan->staff->name ?? 'N/A' }}</td>
                <td>{{ $loan->staff->campus ?? 'N/A' }}</td>
                <td class="text-end">{{ number_format($loan->requested_amount ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($loan->approved_amount ?? 0, 2) }}</td>
                <td>{{ $loan->repayment_instalments ?? 'N/A' }}</td>
                <td>{{ $loan->status ?? 'N/A' }}</td>
                <td>{{ $loan->created_at ? $loan->created_at->format('d M Y') : 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No loan applications found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <script>
        window.print();
    </script>
</body>
</html>
