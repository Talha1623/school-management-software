<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit & Credit Statement Print</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-family: Arial, sans-serif; color: #111827; }
        .print-container { width: 210mm; margin: 0 auto; }
        :root { --theme-blue: #003471; }
        .header { text-align: center; border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; }
        .school-name { font-size: 20px; font-weight: 800; color: var(--theme-blue); }
        .meta { font-size: 12px; color: #374151; margin-top: 4px; }
        .title { font-size: 16px; font-weight: 800; color: var(--theme-blue); margin-top: 8px; text-transform: uppercase; }
        .top-bar { display:flex; justify-content: flex-end; margin:10px 0; }
        .print-btn { border:1px solid var(--theme-blue); background:var(--theme-blue); color:#fff; padding:6px 12px; }
        .row { display:flex; gap:8px; align-items:flex-start; }
        .col { width:50%; }
        table { width:100%; border-collapse: collapse; font-size:11px; }
        th,td { border:1px solid #b7bcc3; padding:6px; }
        th { background:#e9e9d8; }
        .text-center{text-align:center;}
        .text-end{text-align:right;}
        .amount-credit { background:#8dd194; }
        .amount-debit { background:#e8b5b5; }
        @media print { .top-bar { display:none !important; } }
    </style>
</head>
<body>
<div class="print-container">
    <div class="header">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="meta">{{ $settings->address ?? '' }}</div>
        <div class="title">Debit & Credit Statement</div>
        <div class="meta">Generated: {{ $printedAt }}</div>
    </div>
    <div class="top-bar"><button class="print-btn" onclick="window.print()">Print</button></div>
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
        <div class="col">
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
</div>
@if(request()->get('auto_print'))
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
@endif
</body>
</html>
