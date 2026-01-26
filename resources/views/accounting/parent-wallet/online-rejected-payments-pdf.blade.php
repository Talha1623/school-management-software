<!DOCTYPE html>
<html>
<head>
    <title>Online Rejected Payments - PDF</title>
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
    <h2>Online Rejected Payments</h2>
    <div>Printed at: {{ now()->format('d M Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Payment ID</th>
                <th>Student Code</th>
                <th>Parent</th>
                <th>Paid Amount</th>
                <th>Expected Amount</th>
                <th>Date</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payments as $index => $payment)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $payment->payment_id ?? $payment->id }}</td>
                <td>{{ $payment->student_code ?? 'N/A' }}</td>
                <td>{{ $payment->parent_name ?? ($payment->student->father_name ?? 'N/A') }}</td>
                <td>{{ number_format($payment->paid_amount ?? 0, 2) }}</td>
                <td>{{ number_format($payment->expected_amount ?? 0, 2) }}</td>
                <td>{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : 'N/A' }}</td>
                <td>{{ $payment->status ?? 'Rejected' }}</td>
                <td>{{ $payment->remarks ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9">No rejected payments found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
