<!DOCTYPE html>
<html>
<head>
    <title>Online Classes - PDF Export</title>
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
        }
        th {
            background-color: #003471;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Online Classes Report</h1>
    <p><strong>Generated:</strong> {{ date('Y-m-d H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Campus</th>
                <th>Class</th>
                <th>Section</th>
                <th>Class Topic</th>
                <th>Start Date</th>
                <th>Timing</th>
                <th>Password</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($onlineClasses as $class)
                <tr>
                    <td>{{ $class->id }}</td>
                    <td>{{ $class->campus }}</td>
                    <td>{{ $class->class }}</td>
                    <td>{{ $class->section }}</td>
                    <td>{{ $class->class_topic }}</td>
                    <td>{{ $class->start_date->format('Y-m-d') }}</td>
                    <td>{{ $class->timing }}</td>
                    <td>{{ $class->password }}</td>
                    <td>{{ $class->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center;">No online classes found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

