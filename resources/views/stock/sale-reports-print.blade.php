<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock and Sale Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #222;
            margin: 24px;
        }
        h2 {
            margin: 0 0 6px 0;
        }
        .meta {
            font-size: 12px;
            color: #555;
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 12px;
            text-align: left;
        }
        th {
            background: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 12px;
            font-size: 12px;
        }
        .print-btn {
            margin-bottom: 12px;
            padding: 6px 12px;
            font-size: 12px;
            border: 1px solid #003471;
            background: #003471;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">Print</button>

    @if($reportType === 'total-products')
        <h2>Total Products Report</h2>
        <div class="meta">Generated at: {{ $generatedAt->format('d M Y, h:i A') }}</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Purchase Price</th>
                    <th>Sale Price</th>
                    <th>Total Stock</th>
                    <th>Campus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $product->product_name }}</td>
                        <td>{{ $product->category }}</td>
                        <td class="text-right">PKR {{ number_format($product->purchase_price, 2) }}</td>
                        <td class="text-right">PKR {{ number_format($product->sale_price, 2) }}</td>
                        <td class="text-right">{{ $product->total_stock }}</td>
                        <td>{{ $product->campus }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @elseif($reportType === 'out-of-stock')
        <h2>Out Of Stock Products Report</h2>
        <div class="meta">Generated at: {{ $generatedAt->format('d M Y, h:i A') }}</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Total Stock</th>
                    <th>Campus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $product->product_name }}</td>
                        <td>{{ $product->category }}</td>
                        <td class="text-right">{{ $product->total_stock }}</td>
                        <td>{{ $product->campus }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No out of stock products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @elseif($reportType === 'low-stock')
        <h2>Low Stock Products Report</h2>
        <div class="meta">Low stock threshold: {{ $lowStockThreshold }} | Generated at: {{ $generatedAt->format('d M Y, h:i A') }}</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Total Stock</th>
                    <th>Campus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $product->product_name }}</td>
                        <td>{{ $product->category }}</td>
                        <td class="text-right">{{ $product->total_stock }}</td>
                        <td>{{ $product->campus }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No low stock products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @elseif($reportType === 'monthly-sales-profit')
        <h2>Monthly Sales & Profit Report</h2>
        <div class="meta">Month: {{ $monthLabel }} | Generated at: {{ $generatedAt->format('d M Y, h:i A') }}</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Sale Date</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Amount</th>
                    <th>Profit</th>
                    <th>Campus</th>
                </tr>
            </thead>
            <tbody>
                @forelse($saleRecords as $index => $record)
                    @php
                        $purchasePrice = $record->product?->purchase_price ?? 0;
                        $profit = ($record->unit_price - $purchasePrice) * $record->quantity;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ date('d M Y', strtotime($record->sale_date)) }}</td>
                        <td>{{ $record->product_name }}</td>
                        <td class="text-right">{{ $record->quantity }}</td>
                        <td class="text-right">PKR {{ number_format($record->unit_price, 2) }}</td>
                        <td class="text-right">PKR {{ number_format($record->total_amount, 2) }}</td>
                        <td class="text-right">PKR {{ number_format($profit, 2) }}</td>
                        <td>{{ $record->campus }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No sales records found for this month.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="totals">
            <strong>Total Sales:</strong> PKR {{ number_format($salesTotal ?? 0, 2) }}
            &nbsp; | &nbsp;
            <strong>Total Profit:</strong> PKR {{ number_format($profitTotal ?? 0, 2) }}
        </div>
    @else
        <h2>Invalid Report</h2>
    @endif
</body>
</html>
