<!DOCTYPE html>
<html>
<head>
    <title>Products & Stock Report</title>
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
    </style>
</head>
<body>
    <h2>Products & Stock Report</h2>
    <p><strong>Generated on:</strong> {{ date('d M Y, h:i A') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Category</th>
                <th class="text-right">Purchase Price</th>
                <th class="text-right">Sale Price</th>
                <th class="text-right">Total Stock</th>
                <th>Campus</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>{{ $product->id }}</td>
                <td>{{ $product->product_name }}</td>
                <td>{{ $product->category }}</td>
                <td class="text-right">{{ number_format($product->purchase_price, 2) }}</td>
                <td class="text-right">{{ number_format($product->sale_price, 2) }}</td>
                <td class="text-right">{{ $product->total_stock }}</td>
                <td>{{ $product->campus }}</td>
                <td>{{ $product->created_at->format('d M Y, h:i A') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center;">No products found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <p style="margin-top: 20px;"><strong>Total Products:</strong> {{ $products->count() }}</p>
</body>
</html>

