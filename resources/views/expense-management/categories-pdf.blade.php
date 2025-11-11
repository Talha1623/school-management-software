<!DOCTYPE html>
<html>
<head>
    <title>Expense Categories - PDF Export</title>
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
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Expense Categories Report</h1>
    <p><strong>Generated:</strong> {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Categories:</strong> {{ $categories->count() }}</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Campus</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->id }}</td>
                    <td>{{ $category->category_name }}</td>
                    <td>{{ $category->campus }}</td>
                    <td>{{ $category->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No categories found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

