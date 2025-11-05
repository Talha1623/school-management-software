<!DOCTYPE html>
<html>
<head>
    <title>Attendance Accounts - PDF Export</title>
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
    <h1>Attendance Accounts Report</h1>
    <p><strong>Generated:</strong> {{ date('Y-m-d H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User Name</th>
                <th>User ID Card</th>
                <th>Password</th>
                <th>Campus</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accounts as $account)
                <tr>
                    <td>{{ $account->id }}</td>
                    <td>{{ $account->user_name }}</td>
                    <td>{{ $account->user_id_card }}</td>
                    <td>{{ $account->password }}</td>
                    <td>{{ $account->campus }}</td>
                    <td>{{ $account->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No attendance accounts found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

