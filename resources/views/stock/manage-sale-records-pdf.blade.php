<!DOCTYPE html>
<html>
<head>
    <title>Sale Records Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h2 {
            color: #003471;
            text-align: center;
            margin-bottom: 20px;
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
            font-weight: bold;
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
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Sale Records Report</h2>
    <p><strong>Generated on:</strong> {{ date('d M Y, h:i A') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Sale Date</th>
                <th>Product Name</th>
                <th>Category</th>
                <th class="text-center">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total Amount</th>
                <th>Payment Method</th>
                <th>Campus</th>
                <th>Received By</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($saleRecords as $index => $record)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $record->sale_date ? date('d M Y', strtotime($record->sale_date)) : 'N/A' }}</td>
                <td>{{ $record->product_name }}</td>
                <td>{{ $record->category }}</td>
                <td class="text-center">{{ $record->quantity }}</td>
                <td class="text-right">PKR {{ number_format($record->unit_price, 2) }}</td>
                <td class="text-right">PKR {{ number_format($record->total_amount, 2) }}</td>
                <td>{{ $record->method }}</td>
                <td>{{ $record->campus }}</td>
                <td>{{ $record->received_by ?? 'N/A' }}</td>
                <td>{{ $record->notes ? (strlen($record->notes) > 30 ? substr($record->notes, 0, 30) . '...' : $record->notes) : 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center;">No sale records found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($saleRecords->count() > 0)
    <div class="summary">
        <div class="summary-row">
            <span>Total Sales:</span>
            <span>PKR {{ number_format($totalSales, 2) }}</span>
        </div>
        <div class="summary-row">
            <span>Total Quantity:</span>
            <span>{{ number_format($totalQuantity, 0) }}</span>
        </div>
        <div class="summary-row">
            <span>Total Records:</span>
            <span>{{ $saleRecords->count() }}</span>
        </div>
    </div>
    @endif
</body>
</html>
