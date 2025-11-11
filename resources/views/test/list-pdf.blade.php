<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test List Report</title>
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
            font-size: 11px;
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
        <h1>Test List Report</h1>
        <p>Generated on: {{ date('F d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Test Name</th>
                <th>Campus</th>
                <th>Class</th>
                <th>Section</th>
                <th>Subject</th>
                <th>Test Type</th>
                <th>Date</th>
                <th>Session</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tests as $index => $test)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $test->test_name }}</td>
                <td>{{ $test->campus }}</td>
                <td>{{ $test->for_class }}</td>
                <td>{{ $test->section }}</td>
                <td>{{ $test->subject }}</td>
                <td>{{ $test->test_type }}</td>
                <td>{{ $test->date->format('d M Y') }}</td>
                <td>{{ $test->session }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px;">No tests found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Total Tests: {{ $tests->count() }}</p>
    </div>
</body>
</html>

