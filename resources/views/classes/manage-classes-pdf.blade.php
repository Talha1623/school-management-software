<!DOCTYPE html>
<html>
<head>
    <title>Classes List</title>
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
    <h2>Classes List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Campus</th>
                <th>Class Name</th>
                <th>Numeric No</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $class)
                <tr>
                    <td>{{ $class->id }}</td>
                    <td>{{ $class->campus }}</td>
                    <td>{{ $class->class_name }}</td>
                    <td>{{ $class->numeric_no }}</td>
                    <td>{{ $class->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center;">No classes found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

