<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parents Credit System - PDF Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #003471;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #003471;
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table thead {
            background-color: #003471;
            color: white;
        }
        table thead th {
            padding: 10px 8px;
            text-align: left;
            border: 1px solid #003471;
            font-weight: bold;
        }
        table tbody td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .badge {
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Parents Credit System Report</h1>
        <p>Generated on: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Parent ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>ID Card Number</th>
                <th class="text-end">Available Credit</th>
                <th class="text-end">Increase</th>
                <th class="text-end">Decrease</th>
                <th class="text-center">Children</th>
            </tr>
        </thead>
        <tbody>
            @forelse($advanceFees as $index => $fee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $fee->parent_id ?? 'N/A' }}</td>
                    <td>{{ $fee->name ?? 'N/A' }}</td>
                    <td>{{ $fee->email ?? 'N/A' }}</td>
                    <td>{{ $fee->phone ?? 'N/A' }}</td>
                    <td>{{ $fee->id_card_number ?? 'N/A' }}</td>
                    <td class="text-end">Rs. {{ number_format((float)($fee->available_credit ?? 0), 2) }}</td>
                    <td class="text-end">Rs. {{ number_format((float)($fee->increase ?? 0), 2) }}</td>
                    <td class="text-end">Rs. {{ number_format((float)($fee->decrease ?? 0), 2) }}</td>
                    <td class="text-center">{{ $fee->children_count ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Total Records: {{ count($advanceFees) }}</p>
        <p>Total Available Credit: Rs. {{ number_format($advanceFees->sum('available_credit'), 2) }}</p>
    </div>
</body>
</html>
