<!DOCTYPE html>
<html>
<head>
    <title>Transport Routes Report</title>
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
    <h1>Transport Routes Report</h1>
    <p><strong>Generated on:</strong> {{ date('d M Y, h:i A') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campus</th>
                <th>Route Name</th>
                <th>Number Of Vehicle</th>
                <th>Description</th>
                <th>Route Fare</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transports as $index => $transport)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $transport->campus ?? 'N/A' }}</td>
                    <td>{{ $transport->route_name }}</td>
                    <td>{{ $transport->number_of_vehicle }}</td>
                    <td>{{ $transport->description ?? 'N/A' }}</td>
                    <td>{{ number_format($transport->route_fare, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No transport routes found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

