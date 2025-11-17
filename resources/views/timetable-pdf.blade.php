<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Export</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #003471;
            padding-bottom: 10px;
        }
        
        .header h1 {
            color: #003471;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table thead {
            background-color: #003471;
            color: white;
        }
        
        table th,
        table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        table th {
            font-weight: bold;
            font-size: 13px;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        @media print {
            body {
                padding: 10px;
            }
            
            .header {
                page-break-after: avoid;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Timetable Management</h1>
        <p>Generated on {{ date('F d, Y h:i A') }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campus</th>
                <th>Class</th>
                <th>Section</th>
                <th>Subject</th>
                <th>Day</th>
                <th>Starting Time</th>
                <th>Ending Time</th>
            </tr>
        </thead>
        <tbody>
            @if($timetables->count() > 0)
                @foreach($timetables as $index => $timetable)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><span class="badge badge-info">{{ $timetable->campus }}</span></td>
                        <td><strong>{{ $timetable->class }}</strong></td>
                        <td><span class="badge badge-secondary">{{ $timetable->section }}</span></td>
                        <td><strong>{{ $timetable->subject }}</strong></td>
                        <td><span class="badge badge-success">{{ $timetable->day }}</span></td>
                        <td>{{ date('H:i', strtotime($timetable->starting_time)) }}</td>
                        <td>{{ date('H:i', strtotime($timetable->ending_time)) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">
                        No timetables found.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    
    <div class="footer">
        <p>Total Records: {{ $timetables->count() }}</p>
    </div>
</body>
</html>

