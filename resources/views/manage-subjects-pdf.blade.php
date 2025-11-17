<!DOCTYPE html>
<html>
<head>
    <title>Subjects List</title>
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
    <h2>Subjects List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Campus</th>
                <th>Class</th>
                <th>Section</th>
                <th>Subject Name</th>
                <th>Teacher</th>
                <th>Session</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subjects as $subject)
                <tr>
                    <td>{{ $subject->id }}</td>
                    <td>{{ $subject->campus }}</td>
                    <td>{{ $subject->class }}</td>
                    <td>{{ $subject->section }}</td>
                    <td>{{ $subject->subject_name }}</td>
                    <td>{{ $subject->teacher ?? 'N/A' }}</td>
                    <td>{{ $subject->session ?? 'N/A' }}</td>
                    <td>{{ $subject->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No subjects found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

