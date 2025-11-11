<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Behavior Categories Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #003471;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #003471;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #003471;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Behavior Categories Report</h1>
        <p>Generated on: {{ date('F d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Category Name</th>
                <th>Campus</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $index => $category)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $category->category_name }}</td>
                <td>{{ $category->campus }}</td>
                <td>{{ $category->created_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; padding: 20px;">No categories found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Total Categories: {{ $categories->count() }}</p>
    </div>
</body>
</html>

