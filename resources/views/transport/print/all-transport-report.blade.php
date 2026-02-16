<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Transport Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: white;
            padding: 20px;
        }
        
        .print-header {
            text-align: center;
            border-bottom: 3px solid #003471;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .print-header h2 {
            color: #003471;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .print-header h3 {
            color: #e91e63;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .print-header p {
            font-size: 11px;
            color: #666;
            margin: 2px 0;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #003471;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        
        .summary-card h5 {
            color: #003471;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #e91e63;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background: #003471;
            color: white;
            font-weight: 600;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 10px;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn no-print">
        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">print</span>
        Print
    </button>
    
    <div class="print-header">
        <h2>{{ config('app.name', 'ICMS') }}</h2>
        <h3>All Transport Report</h3>
        <p>{{ config('app.address', 'Defence View') }}</p>
        <p>Phone: {{ config('app.phone', '+923316074246') }} | Email: {{ config('app.email', 'arainabdurrehman3@gmail.com') }}</p>
        <p>Generated: {{ date('d-m-Y H:i:s') }}</p>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <h5>Total Routes</h5>
            <div class="value">{{ $totalRoutes }}</div>
        </div>
        <div class="summary-card">
            <h5>Total Vehicles</h5>
            <div class="value">{{ $totalVehicles }}</div>
        </div>
        <div class="summary-card">
            <h5>Total Students</h5>
            <div class="value">{{ $totalStudents }}</div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campus</th>
                <th>Route Name</th>
                <th class="text-center">No. of Vehicles</th>
                <th class="text-right">Route Fare</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transports as $index => $transport)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $transport->campus ?? 'N/A' }}</td>
                    <td><strong>{{ $transport->route_name }}</strong></td>
                    <td class="text-center">{{ $transport->number_of_vehicle }}</td>
                    <td class="text-right">{{ number_format($transport->route_fare, 2) }}</td>
                    <td>{{ $transport->description ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px;">No transport routes found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if(request()->get('auto_print'))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
</body>
</html>
