<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Print - Timetable</title>
    <style>
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            body {
                margin: 0;
                padding: 5mm;
            }
            .no-print {
                display: none !important;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            background: white;
        }
        
        .receipt {
            width: 100%;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        
        .store-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .store-address {
            font-size: 9px;
            margin-bottom: 2px;
        }
        
        .receipt-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 6px;
            text-transform: uppercase;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 10px;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .info-value {
            text-align: right;
        }
        
        .timetable-table {
            width: 100%;
            margin: 8px 0;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        .timetable-table th,
        .timetable-table td {
            padding: 3px 2px;
            text-align: left;
            border-bottom: 1px dashed #000;
        }
        
        .timetable-table th {
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
        }
        
        .timetable-table tr:last-child td {
            border-bottom: none;
        }
        
        .footer {
            text-align: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #000;
            font-size: 9px;
        }
        
        .btn-print {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            z-index: 1000;
        }
        
        .btn-print:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <button class="btn-print no-print" onclick="window.print()">Print</button>
    
    <div class="receipt">
        <div class="header">
            <div class="store-name">{{ config('app.name', 'ICMS') }}</div>
            <div class="store-address">{{ config('app.address', 'Defence View') }}</div>
            <div class="store-address">Phone: {{ config('app.phone', '+923316074246') }}</div>
            <div class="receipt-title">Timetable</div>
        </div>
        
        <div class="info-row">
            <span class="info-label">Campus:</span>
            <span class="info-value">{{ $timetable->campus }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Class:</span>
            <span class="info-value">{{ $timetable->class }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Section:</span>
            <span class="info-value">{{ $timetable->section }}</span>
        </div>
        
        <div class="divider"></div>
        
        <table class="timetable-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Day</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $timetable->subject }}</td>
                    <td>{{ $timetable->day }}</td>
                    <td>{{ date('H:i', strtotime($timetable->starting_time)) }} - {{ date('H:i', strtotime($timetable->ending_time)) }}</td>
                </tr>
            </tbody>
        </table>
        
        @if(isset($timetable->assigned_teacher) && $timetable->assigned_teacher)
        <div class="info-row" style="margin-top: 6px;">
            <span class="info-label">Teacher:</span>
            <span class="info-value">{{ $timetable->assigned_teacher }}</span>
        </div>
        @endif
        
        <div class="footer">
            <div>Generated: {{ date('d-m-Y H:i:s') }}</div>
        </div>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
