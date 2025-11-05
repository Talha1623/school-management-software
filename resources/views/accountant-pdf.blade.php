<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountants List - PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        h1 {
            color: #003471;
            text-align: center;
            border-bottom: 3px solid #003471;
            padding-bottom: 10px;
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
        }
        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <h1>Accountants List</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Campus</th>
                <th>App Login</th>
                <th>Web Login</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accountants as $accountant)
                <tr>
                    <td>{{ $accountant->id }}</td>
                    <td>{{ $accountant->name }}</td>
                    <td>{{ $accountant->email }}</td>
                    <td>{{ $accountant->campus ?? 'N/A' }}</td>
                    <td>{{ $accountant->app_login_enabled ? 'Enabled' : 'Disabled' }}</td>
                    <td>{{ $accountant->web_login_enabled ? 'Enabled' : 'Disabled' }}</td>
                    <td>{{ $accountant->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No accountants found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
        <p>Total Accountants: {{ $accountants->count() }}</p>
    </div>
</body>
</html>

