<!DOCTYPE html>
<html>
<head>
    <title>Campuses Report</title>
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
    <h1>Campuses Report</h1>
    <p><strong>Generated on:</strong> {{ date('d M Y, h:i A') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campus Name</th>
                <th>Campus Address</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($campuses as $index => $campus)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $campus->campus_name }}</td>
                    <td>{{ $campus->campus_address ?? 'N/A' }}</td>
                    <td>{{ $campus->description ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No campuses found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

