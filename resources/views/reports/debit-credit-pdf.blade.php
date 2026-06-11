<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Debit & Credit Statement</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #003471; padding-bottom: 8px; }
        .school-name { font-size: 18px; font-weight: 700; color: #003471; }
        .meta { font-size: 11px; color: #374151; margin-top: 3px; }
        .title { font-size: 14px; font-weight: 700; color: #003471; margin-top: 6px; text-transform: uppercase; }
        .row { width: 100%; margin-top: 10px; }
        .col { width: 49%; display: inline-block; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th,td { border: 1px solid #b7bcc3; padding: 5px; }
        th { background: #e9e9d8; text-align: left; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .amount-credit { background: #8dd194; }
        .amount-debit { background: #e8b5b5; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="meta">{{ $settings->address ?? '' }}</div>
        <div class="title">Debit & Credit Statement</div>
        <div class="meta">Generated: {{ $printedAt }}</div>
    </div>
    <div class="row">
        <div class="col">
            <table>
                <thead><tr><th>Fee Head</th><th class="text-center">Paid Txns</th><th class="text-end">Cash</th><th class="text-end">Discount</th><th class="text-end">Paid Amount</th></tr></thead>
                <tbody>
                @forelse($feeHeadSummary as $row)
                    <tr><td>{{ $row['head'] }}</td><td class="text-center">{{ $row['transactions'] }}</td><td class="text-end">{{ number_format($row['cash'] ?? 0, 2) }}</td><td class="text-end">{{ number_format($row['discount'] ?? 0, 2) }}</td><td class="text-end amount-credit">{{ number_format($row['amount'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center">No fee head data found</td></tr>
                @endforelse
                @if($feeHeadSummary->count() > 0)
                    <tr style="font-weight:bold;background:#e3f2fd;"><td class="text-end">Total</td><td class="text-center">{{ $feeTotals['transactions'] ?? 0 }}</td><td class="text-end">{{ number_format($feeTotals['cash'] ?? 0, 2) }}</td><td class="text-end">{{ number_format($feeTotals['discount'] ?? 0, 2) }}</td><td class="text-end amount-credit">{{ number_format($feeTotals['amount'] ?? 0, 2) }}</td></tr>
                @endif
                </tbody>
            </table>
        </div>
        <div class="col" style="margin-left:2%;">
            <table>
                <thead><tr><th>Expense Category</th><th class="text-center">Paid Txns</th><th class="text-end">Paid Amount</th></tr></thead>
                <tbody>
                @forelse($expenseHeadSummary as $row)
                    <tr><td>{{ $row['head'] }}</td><td class="text-center">{{ $row['transactions'] }}</td><td class="text-end amount-debit">{{ number_format($row['amount'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center">No expense data found</td></tr>
                @endforelse
                @if($expenseHeadSummary->count() > 0)
                    <tr style="font-weight:bold;background:#fde8e8;"><td class="text-end">Total</td><td class="text-center">{{ $expenseTotals['transactions'] ?? 0 }}</td><td class="text-end amount-debit">{{ number_format($expenseTotals['amount'] ?? 0, 2) }}</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
