<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Particular Receipt</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 16px;
            background: #fff;
        }
        .receipt-header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .receipt-title {
            font-weight: 700;
            font-size: 18px;
            margin: 0;
        }
        .section {
            margin-bottom: 16px;
        }
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #f3f4f6;
        }
        .text-end { text-align: right; }
        .text-muted { color: #6b7280; }
        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">
<div class="receipt-header">
    <h4 class="receipt-title">Particular Receipt</h4>
</div>

<div class="section card">
    <div class="grid">
        <div><strong>Student Code:</strong> {{ $studentCode ?? 'N/A' }}</div>
        <div><strong>Student:</strong> {{ $student->student_name ?? 'N/A' }}</div>
        <div><strong>Parent:</strong> {{ $student->father_name ?? 'N/A' }}</div>
        <div><strong>Class:</strong>
            @if($student && $student->class && $student->section)
                {{ $student->class }}/{{ $student->section }}
            @elseif($student && $student->class)
                {{ $student->class }}
            @else
                N/A
            @endif
        </div>
        <div><strong>Campus:</strong> {{ $student->campus ?? 'N/A' }}</div>
        <div><strong>Generated On:</strong> {{ now()->format('d-m-Y h:i:s A') }}</div>
    </div>
</div>

<div class="section card">
    <div class="fw-semibold mb-2">Paid Fees</div>
    <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Amount Paid</th>
                            <th>Late Fee</th>
                            <th>Discount</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_title }}</td>
                                <td>{{ number_format($payment->payment_amount ?? 0, 2) }}</td>
                                <td>{{ number_format($payment->late_fee ?? 0, 2) }}</td>
                                <td>{{ number_format($payment->discount ?? 0, 2) }}</td>
                                <td>{{ $payment->payment_date ? $payment->payment_date->format('d-m-Y') : 'N/A' }}</td>
                                <td>{{ $payment->payment_date ? $payment->payment_date->format('h:i:s A') : 'N/A' }}</td>
                                <td>{{ $payment->accountant ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No paid fees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
        <tfoot>
            <tr>
                <td class="text-end"><strong>Totals</strong></td>
                <td><strong>{{ number_format($totalPaid ?? 0, 2) }}</strong></td>
                <td><strong>{{ number_format($totalLate ?? 0, 2) }}</strong></td>
                <td><strong>{{ number_format($totalDiscount ?? 0, 2) }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="section card">
    <div class="fw-semibold mb-2">Remaining Fees</div>
    <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Remaining Amount</th>
                            <th>Remaining Late</th>
                            <th>Total Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingFees as $fee)
                            <tr>
                                <td>{{ $fee['title'] ?? 'N/A' }}</td>
                                <td>{{ number_format($fee['amount'] ?? 0, 2) }}</td>
                                <td>{{ number_format($fee['late_fee'] ?? 0, 2) }}</td>
                                <td>{{ number_format($fee['total'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No remaining fees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
        <tfoot>
            <tr>
                <td class="text-end"><strong>Total Due</strong></td>
                <td colspan="3"><strong>{{ number_format($totalDue ?? 0, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
