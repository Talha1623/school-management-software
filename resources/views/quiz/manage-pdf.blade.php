<!DOCTYPE html>
<html>
<head>
    <title>Quizzes Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #003471; color: white; }
    </style>
</head>
<body>
    <h2>Quizzes Report</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campus</th>
                <th>Quiz Name</th>
                <th>Description</th>
                <th>For Class</th>
                <th>Section</th>
                <th>Total Questions</th>
                <th>Start Date & Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quizzes as $index => $quiz)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $quiz->campus }}</td>
                    <td>{{ $quiz->quiz_name }}</td>
                    <td>{{ $quiz->description ?? 'N/A' }}</td>
                    <td>{{ $quiz->for_class }}</td>
                    <td>{{ $quiz->section }}</td>
                    <td>{{ $quiz->total_questions }}</td>
                    <td>{{ $quiz->start_date_time->format('d M Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

