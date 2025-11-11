<!DOCTYPE html>
<html>
<head>
    <title>Management Expenses - PDF Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #003471;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background-color: #003471;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Management Expenses Report</h1>
    <p><strong>Generated:</strong> {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Expenses:</strong> {{ $expenses->count() }}</p>
    <p><strong>Total Amount:</strong> ₹{{ number_format($expenses->sum('amount'), 2) }}</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Campus</th>
                <th>Category</th>
                <th>Title</th>
                <th>Description</th>
                <th class="text-right">Amount</th>
                <th>Method</th>
                <th>Invoice/Receipt</th>
                <th>Date</th>
                <th class="text-center">Notify Admin</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->id }}</td>
                    <td>{{ $expense->campus }}</td>
                    <td>{{ $expense->category }}</td>
                    <td>{{ $expense->title }}</td>
                    <td>{{ Str::limit($expense->description ?? 'N/A', 30) }}</td>
                    <td class="text-right">₹{{ number_format($expense->amount, 2) }}</td>
                    <td>{{ $expense->method }}</td>
                    <td>{{ $expense->invoice_receipt ?? 'N/A' }}</td>
                    <td>{{ $expense->date->format('Y-m-d') }}</td>
                    <td class="text-center">{{ $expense->notify_admin ? 'Yes' : 'No' }}</td>
                    <td>{{ $expense->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align: center;">No expenses found.</td>
                </tr>
            @endforelse
        </tbody>
        @if($expenses->count() > 0)
        <tfoot>
            <tr style="background-color: #003471; color: white; font-weight: bold;">
                <td colspan="5" class="text-right">Total:</td>
                <td class="text-right">₹{{ number_format($expenses->sum('amount'), 2) }}</td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>

