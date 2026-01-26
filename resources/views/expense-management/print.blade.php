<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Expense Print</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; color: #111; background: #f8f9fa; }
        .sheet { background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 18px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 12px; gap: 16px; }
        .title { font-size: 18px; font-weight: 600; margin: 0; }
        .subtitle { font-size: 12px; color: #666; margin: 2px 0 0 0; }
        .print-btn { font-size: 12px; padding: 6px 10px; border: 1px solid #003471; background: #003471; color: #fff; border-radius: 4px; cursor: pointer; }
        .printed-at { font-size: 11px; color: #666; margin-top: 4px; text-align: right; }
        .section-title { font-size: 13px; font-weight: 600; color: #003471; margin: 12px 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #e0e0e0; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .desc { margin-top: 8px; padding: 8px; border: 1px dashed #e0e0e0; border-radius: 6px; background: #fafbfc; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <div>
                <h1 class="title">Management Expense</h1>
                <p class="subtitle">Record ID: {{ $managementExpense->id }}</p>
            </div>
            <div>
                <button type="button" class="print-btn no-print" onclick="window.print()">Print</button>
                <div class="printed-at">Printed: {{ $printedAt }}</div>
            </div>
        </div>

        <div class="section-title">Expense Details</div>
        <table>
            <tbody>
                <tr>
                    <th>Campus</th>
                    <td>{{ $managementExpense->campus }}</td>
                    <th>Category</th>
                    <td>{{ $managementExpense->category }}</td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td>{{ $managementExpense->title }}</td>
                    <th>Date</th>
                    <td>{{ $managementExpense->date->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td class="text-right">₹{{ number_format($managementExpense->amount, 2) }}</td>
                    <th>Method</th>
                    <td>{{ $managementExpense->method }}</td>
                </tr>
                <tr>
                    <th>Notify Admin</th>
                    <td>{{ $managementExpense->notify_admin ? 'Yes' : 'No' }}</td>
                    <th>Invoice/Receipt</th>
                    <td>{{ $managementExpense->invoice_receipt ? 'Yes' : 'No' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Description</div>
        <div class="desc">{{ $managementExpense->description ?? 'N/A' }}</div>
    </div>
</body>
</html>
