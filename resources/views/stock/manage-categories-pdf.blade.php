<!DOCTYPE html>
<html>
<head>
    <title>Stock Categories Report</title>
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
    </style>
</head>
<body>
    <h2>Stock Categories Report</h2>
    <p><strong>Generated on:</strong> {{ date('d M Y, h:i A') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Description</th>
                <th>Campus</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
            <tr>
                <td>{{ $category->id }}</td>
                <td>{{ $category->category_name }}</td>
                <td>{{ $category->description ?? 'N/A' }}</td>
                <td>{{ $category->campus }}</td>
                <td>{{ $category->created_at->format('d M Y, h:i A') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center;">No categories found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <p style="margin-top: 20px;"><strong>Total Categories:</strong> {{ $categories->count() }}</p>
</body>
</html>

