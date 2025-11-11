<!DOCTYPE html>
<html>
<head>
    <title>Admin Roles Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
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
        h1 {
            color: #003471;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Admin Roles Report</h1>
    <p><strong>Generated on:</strong> {{ date('d M Y, h:i A') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Admin Of</th>
                <th>Super Admin</th>
            </tr>
        </thead>
        <tbody>
            @forelse($adminRoles as $index => $adminRole)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $adminRole->name }}</td>
                    <td>{{ $adminRole->phone ?? 'N/A' }}</td>
                    <td>{{ $adminRole->email }}</td>
                    <td>{{ $adminRole->admin_of ?? 'N/A' }}</td>
                    <td>{{ $adminRole->super_admin ? 'Yes' : 'No' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No admin roles found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

