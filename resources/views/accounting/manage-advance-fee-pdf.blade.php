<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advance Fee Records - PDF Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        h2 {
            color: #003471;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>Advance Fee Records</h2>
    <p><strong>Export Date:</strong> {{ date('Y-m-d H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Parent ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>ID Card Number</th>
                <th>Available Credit</th>
                <th>Increase</th>
                <th>Decrease</th>
                <th>Childs</th>
            </tr>
        </thead>
        <tbody>
            @forelse($advanceFees as $index => $advanceFee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $advanceFee->parent_id ?? 'N/A' }}</td>
                    <td>{{ $advanceFee->name }}</td>
                    <td>{{ $advanceFee->email ?? 'N/A' }}</td>
                    <td>{{ $advanceFee->phone ?? 'N/A' }}</td>
                    <td>{{ $advanceFee->id_card_number ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($advanceFee->available_credit, 2) }}</td>
                    <td class="text-right">{{ number_format($advanceFee->increase, 2) }}</td>
                    <td class="text-right">{{ number_format($advanceFee->decrease, 2) }}</td>
                    <td class="text-center">{{ $advanceFee->childs }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

