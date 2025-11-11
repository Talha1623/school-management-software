<!DOCTYPE html>
<html>
<head>
    <title>Final Exam Grades</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #003471; color: white; }
    </style>
</head>
<body>
    <h2>Final Exam Grades</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campus</th>
                <th>Name</th>
                <th>From %</th>
                <th>To %</th>
                <th>Session</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grades as $index => $grade)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $grade->campus }}</td>
                <td>{{ $grade->name }}</td>
                <td>{{ number_format($grade->from_percentage, 2) }}%</td>
                <td>{{ number_format($grade->to_percentage, 2) }}%</td>
                <td>{{ $grade->session }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

