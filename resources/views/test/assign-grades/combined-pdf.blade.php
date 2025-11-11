<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Combined Result Grades - PDF Export</title>
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
            padding-bottom: 20px;
        }
        .header h1 {
            color: #003471;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #003471;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Combined Result Grades</h1>
        <p>Generated on: {{ date('d M Y, h:i A') }}</p>
    </div>

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
            @forelse($grades as $index => $grade)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><span class="badge">{{ $grade->campus }}</span></td>
                    <td><strong>{{ $grade->name }}</strong></td>
                    <td>{{ number_format($grade->from_percentage, 2) }}%</td>
                    <td>{{ number_format($grade->to_percentage, 2) }}%</td>
                    <td>{{ $grade->session }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No grades found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Total Records: {{ $grades->count() }}</p>
    </div>
</body>
</html>

