<!DOCTYPE html>
<html>
<head>
    <title>Sections List</title>
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
    <h2>Sections List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Campus</th>
                <th>Name</th>
                <th>Nick Name</th>
                <th>Class</th>
                <th>Teacher</th>
                <th>Session</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sections as $section)
                <tr>
                    <td>{{ $section->id }}</td>
                    <td>{{ $section->campus }}</td>
                    <td>{{ $section->name }}</td>
                    <td>{{ $section->nick_name ?? 'N/A' }}</td>
                    <td>{{ $section->class }}</td>
                    <td>{{ $section->teacher ?? 'N/A' }}</td>
                    <td>{{ $section->session ?? 'N/A' }}</td>
                    <td>{{ $section->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No sections found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

