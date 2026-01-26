<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dues Report</title>
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
        .total-row td { font-weight: 600; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">Parent Dues Report</h1>
            <p class="subtitle">Parent: {{ $parent->name ?? 'N/A' }}</p>
            @if($parent->id_card_number)
                <p class="subtitle">ID Card: {{ $parent->id_card_number }}</p>
            @endif
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
                <th>Student</th>
                <th>Student Code</th>
                <th>Class/Section</th>
                <th>Fee Title</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Late Fee</th>
                <th class="text-right">Net Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $index => $payment)
                @php
                    $student = $studentsByCode[$payment->student_code] ?? null;
                    $amount = (float) ($payment->payment_amount ?? 0);
                    $discount = (float) ($payment->discount ?? 0);
                    $lateFee = (float) ($payment->late_fee ?? 0);
                    $net = $amount - $discount + $lateFee;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->student_name ?? 'N/A' }}</td>
                    <td>{{ $payment->student_code ?? 'N/A' }}</td>
                    <td>
                        {{ $student->class ?? 'N/A' }}
                        @if($student && $student->section) / {{ $student->section }} @endif
                    </td>
                    <td>{{ $payment->payment_title ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($amount, 2) }}</td>
                    <td class="text-right">{{ number_format($discount, 2) }}</td>
                    <td class="text-right">{{ number_format($lateFee, 2) }}</td>
                    <td class="text-right">{{ number_format($net, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">No dues found.</td>
                </tr>
            @endforelse
            @if($payments->isNotEmpty())
                <tr class="total-row">
                    <td colspan="8" class="text-right">Total Due</td>
                    <td class="text-right">{{ number_format($totalDue, 2) }}</td>
                </tr>
            @endif
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
