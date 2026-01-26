<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Behavior Report - {{ ucfirst(str_replace('-', ' ', $reportType)) }}</title>
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
        
        .report-info {
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #003471;
        }
        
        .report-info p {
            margin: 3px 0;
            font-size: 11px;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
        }
        
        .summary-card h5 {
            color: #003471;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .summary-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #003471;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        table th {
            background-color: #003471;
            color: white;
            padding: 8px;
            text-align: center;
            border: 1px solid #000;
            font-weight: 600;
        }
        
        table td {
            padding: 6px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .positive {
            color: #28a745;
            font-weight: 600;
        }
        
        .negative {
            color: #dc3545;
            font-weight: 600;
        }
        
        .print-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #003471;
            display: flex;
            justify-content: space-between;
        }
        
        .signature {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }
        
        @media print {
            body {
                padding: 10px;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                margin: 1cm;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #003471;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .print-btn:hover {
            background: #004a9f;
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
        <h3>Behavior Report - {{ ucfirst(str_replace('-', ' ', $reportType)) }}</h3>
        <p>{{ config('app.address', 'Defence View') }}</p>
        <p>Phone: {{ config('app.phone', '+923316074246') }} | Email: {{ config('app.email', 'arainabdurrehman3@gmail.com') }}</p>
        <p>Generated: {{ date('d-m-Y H:i:s') }}</p>
    </div>
    
    <div class="report-info">
        <p><strong>Campus:</strong> {{ $campus ?? 'All' }}</p>
        <p><strong>Class:</strong> {{ $class ?? 'All' }}</p>
        @if($section)
        <p><strong>Section:</strong> {{ $section }}</p>
        @endif
        <p><strong>Year:</strong> {{ $year }}</p>
        <p><strong>Report Type:</strong> {{ ucfirst(str_replace('-', ' ', $reportType)) }}</p>
    </div>
    
    @if($reportType == 'summary')
        <!-- Summary Report -->
        <div class="summary-cards">
            <div class="summary-card">
                <h5>Total Records</h5>
                <div class="value">{{ $reportData['total_records'] ?? 0 }}</div>
            </div>
            <div class="summary-card">
                <h5>Total Students</h5>
                <div class="value">{{ $reportData['total_students'] ?? 0 }}</div>
            </div>
            <div class="summary-card">
                <h5>Total Points</h5>
                <div class="value">{{ $reportData['total_points'] ?? 0 }}</div>
            </div>
            <div class="summary-card">
                <h5>Net Points</h5>
                <div class="value {{ ($reportData['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $reportData['total_points'] ?? 0 }}</div>
            </div>
        </div>
        
        @if(isset($reportData['type_wise_summary']) && count($reportData['type_wise_summary']) > 0)
        <h4 style="color: #003471; margin-bottom: 10px;">Type-wise Summary</h4>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Behavior Type</th>
                    <th>Count</th>
                    <th>Total Points</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['type_wise_summary'] as $index => $summary)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $summary['type'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $summary['count'] ?? 0 }}</td>
                    <td class="text-center {{ ($summary['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $summary['total_points'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        
        @if(isset($reportData['student_summary']) && count($reportData['student_summary']) > 0)
        <h4 style="color: #003471; margin-bottom: 10px;">Student Summary</h4>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Code</th>
                    <th>Student Name</th>
                    <th>Total Records</th>
                    <th>Total Points</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['student_summary'] as $index => $student)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $student['student_code'] ?? 'N/A' }}</td>
                    <td>{{ $student['student_name'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $student['total_records'] ?? 0 }}</td>
                    <td class="text-center {{ ($student['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $student['total_points'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        
    @elseif($reportType == 'detailed')
        <!-- Detailed Report -->
        <p style="margin-bottom: 15px;"><strong>Total Records:</strong> {{ $reportData['total_records'] ?? 0 }}</p>
        
        @if(isset($reportData['records']) && $reportData['records']->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Student Code</th>
                    <th>Student Name</th>
                    <th>Type</th>
                    <th>Points</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Description</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['records'] as $index => $record)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $record->date->format('d M Y') }}</td>
                    <td>{{ $record->student ? $record->student->student_code : 'N/A' }}</td>
                    <td>{{ $record->student_name }}</td>
                    <td>{{ $record->type }}</td>
                    <td class="text-center {{ $record->points >= 0 ? 'positive' : 'negative' }}">{{ $record->points }}</td>
                    <td>{{ $record->class }}</td>
                    <td>{{ $record->section ?? 'N/A' }}</td>
                    <td>{{ $record->description ?? 'N/A' }}</td>
                    <td>{{ $record->recorded_by ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No records found for the selected filters.</p>
        @endif
        
    @elseif($reportType == 'monthly')
        <!-- Monthly Report -->
        <p style="margin-bottom: 15px;"><strong>Total Records:</strong> {{ $reportData['total_records'] ?? 0 }}</p>
        
        @if(isset($reportData['monthly_data']) && count($reportData['monthly_data']) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Month</th>
                    <th>Total Records</th>
                    <th>Total Points</th>
                    <th>Positive Points</th>
                    <th>Negative Points</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['monthly_data'] as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['month_formatted'] ?? $item['month'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item['total_records'] ?? 0 }}</td>
                    <td class="text-center {{ ($item['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $item['total_points'] ?? 0 }}</td>
                    <td class="text-center positive">{{ $item['positive_points'] ?? 0 }}</td>
                    <td class="text-center negative">{{ $item['negative_points'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No monthly data found for the selected filters.</p>
        @endif
        
    @elseif($reportType == 'yearly')
        <!-- Yearly Report -->
        <div class="summary-cards">
            <div class="summary-card">
                <h5>{{ $reportData['current_year'] ?? date('Y') }} Records</h5>
                <div class="value">{{ $reportData['current_year_data']['total_records'] ?? 0 }}</div>
            </div>
            <div class="summary-card">
                <h5>{{ $reportData['current_year'] ?? date('Y') }} Points</h5>
                <div class="value {{ ($reportData['current_year_data']['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $reportData['current_year_data']['total_points'] ?? 0 }}</div>
            </div>
            <div class="summary-card">
                <h5>{{ $reportData['previous_year'] ?? date('Y') - 1 }} Records</h5>
                <div class="value">{{ $reportData['previous_year_data']['total_records'] ?? 0 }}</div>
            </div>
            <div class="summary-card">
                <h5>{{ $reportData['previous_year'] ?? date('Y') - 1 }} Points</h5>
                <div class="value {{ ($reportData['previous_year_data']['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $reportData['previous_year_data']['total_points'] ?? 0 }}</div>
            </div>
        </div>
        
        <table style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Total Records</th>
                    <th>Total Points</th>
                    <th>Positive Points</th>
                    <th>Negative Points</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $reportData['current_year'] ?? date('Y') }}</strong></td>
                    <td class="text-center">{{ $reportData['current_year_data']['total_records'] ?? 0 }}</td>
                    <td class="text-center {{ ($reportData['current_year_data']['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $reportData['current_year_data']['total_points'] ?? 0 }}</td>
                    <td class="text-center positive">{{ $reportData['current_year_data']['positive_points'] ?? 0 }}</td>
                    <td class="text-center negative">{{ $reportData['current_year_data']['negative_points'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>{{ $reportData['previous_year'] ?? date('Y') - 1 }}</strong></td>
                    <td class="text-center">{{ $reportData['previous_year_data']['total_records'] ?? 0 }}</td>
                    <td class="text-center {{ ($reportData['previous_year_data']['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $reportData['previous_year_data']['total_points'] ?? 0 }}</td>
                    <td class="text-center positive">{{ $reportData['previous_year_data']['positive_points'] ?? 0 }}</td>
                    <td class="text-center negative">{{ $reportData['previous_year_data']['negative_points'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
        
        @php
            $currentTotal = $reportData['current_year_data']['total_points'] ?? 0;
            $previousTotal = $reportData['previous_year_data']['total_points'] ?? 0;
            $difference = $currentTotal - $previousTotal;
            $percentageChange = $previousTotal != 0 ? round(($difference / abs($previousTotal)) * 100, 1) : 'N/A';
        @endphp
        
        <div style="margin-top: 20px; padding: 10px; background: #e7f3ff; border-left: 4px solid #003471;">
            <p style="margin: 0;"><strong>Year-over-Year Comparison:</strong> Points {{ $difference >= 0 ? 'increased' : 'decreased' }} by <strong>{{ abs($difference) }}</strong> 
            @if($percentageChange !== 'N/A')
                ({{ abs($percentageChange) }}%)
            @endif
            </p>
        </div>
        
    @elseif($reportType == 'type-wise')
        <!-- Type-wise Report -->
        <p style="margin-bottom: 15px;"><strong>Total Records:</strong> {{ $reportData['total_records'] ?? 0 }}</p>
        
        @if(isset($reportData['type_wise_data']) && count($reportData['type_wise_data']) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Behavior Type</th>
                    <th>Total Records</th>
                    <th>Total Points</th>
                    <th>Average Points</th>
                    <th>Students Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['type_wise_data'] as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['type'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item['total_records'] ?? 0 }}</td>
                    <td class="text-center {{ ($item['total_points'] ?? 0) >= 0 ? 'positive' : 'negative' }}">{{ $item['total_points'] ?? 0 }}</td>
                    <td class="text-center">{{ number_format($item['average_points'] ?? 0, 2) }}</td>
                    <td class="text-center">{{ $item['students_count'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No type-wise data found for the selected filters.</p>
        @endif
    @endif
    
    <div class="print-footer">
        <div class="signature">
            <div class="signature-line">
                <strong>INCHARGE</strong>
            </div>
        </div>
        <div class="signature">
            <div class="signature-line">
                <strong>PRINCIPAL</strong>
            </div>
        </div>
    </div>
</body>
</html>

