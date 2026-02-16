<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connected Students Report</title>
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
            grid-template-columns: repeat(2, 1fr);
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
        
        .filter-info {
            background: #f8f9fa;
            border-left: 4px solid #003471;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .filter-info p {
            margin: 3px 0;
            font-size: 11px;
        }
        
        .route-section {
            margin-bottom: 30px;
        }
        
        .route-header {
            background: #003471;
            color: white;
            padding: 12px;
            font-weight: bold;
            border: 1px solid #003471;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .route-count {
            font-size: 12px;
            font-weight: normal;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background: #e9ecef;
            color: #000;
            font-weight: 600;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
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
            .route-section {
                page-break-inside: avoid;
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
        <h3>Connected Students Report</h3>
        <p>{{ config('app.address', 'Defence View') }}</p>
        <p>Phone: {{ config('app.phone', '+923316074246') }} | Email: {{ config('app.email', 'arainabdurrehman3@gmail.com') }}</p>
        <p>Generated: {{ date('d-m-Y H:i:s') }}</p>
    </div>
    
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <h5>Total Students</h5>
            <div class="value">{{ $totalStudents }}</div>
        </div>
        <div class="summary-card">
            <h5>Total Routes</h5>
            <div class="value">{{ $totalRoutes }}</div>
        </div>
    </div>
    
    <!-- Students by Route -->
    @forelse($studentsByRoute as $route => $routeStudents)
        <div class="route-section">
            <div class="route-header">
                <span>{{ $route }}</span>
                <span class="route-count">{{ $routeStudents->count() }} {{ Str::plural('student', $routeStudents->count()) }}</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Student Code</th>
                        <th>Father Name</th>
                        <th>Phone</th>
                        <th>Campus</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th class="text-center">Transport Fare</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routeStudents as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $student->student_name ?? 'N/A' }}</strong></td>
                            <td>{{ $student->student_code ?? 'N/A' }}</td>
                            <td>{{ $student->father_name ?? 'N/A' }}</td>
                            <td>{{ $student->father_phone ?? $student->whatsapp_number ?? 'N/A' }}</td>
                            <td>{{ $student->campus ?? 'N/A' }}</td>
                            <td>{{ $student->class ?? 'N/A' }}</td>
                            <td>{{ $student->section ?? 'N/A' }}</td>
                            <td class="text-center">{{ $student->transport_fare ? number_format($student->transport_fare, 2) : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div style="text-align: center; padding: 40px; color: #666;">
            No students found using transport services.
        </div>
    @endforelse
    
    @if(request()->get('auto_print'))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
</body>
</html>
