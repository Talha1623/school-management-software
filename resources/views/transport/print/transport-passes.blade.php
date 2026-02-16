<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Passes</title>
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
        
        .passes-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .pass-card {
            border: 2px solid #003471;
            border-radius: 8px;
            padding: 15px;
            background: white;
            page-break-inside: avoid;
            min-height: 200px;
        }
        
        .pass-header {
            text-align: center;
            border-bottom: 2px solid #003471;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .pass-header h4 {
            color: #003471;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .pass-header p {
            font-size: 10px;
            color: #666;
            margin: 0;
        }
        
        .pass-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .pass-field {
            margin-bottom: 8px;
        }
        
        .pass-field label {
            font-weight: bold;
            font-size: 10px;
            color: #666;
            display: block;
            margin-bottom: 2px;
        }
        
        .pass-field .value {
            font-size: 12px;
            color: #000;
        }
        
        .pass-photo {
            text-align: center;
            border: 1px solid #ddd;
            padding: 10px;
            background: #f8f9fa;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .pass-footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 10px;
            }
            .pass-card {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }
            .passes-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @page {
            margin: 1cm;
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
        <h3>Transport Passes</h3>
        <p>{{ config('app.address', 'Defence View') }}</p>
        <p>Phone: {{ config('app.phone', '+923316074246') }} | Email: {{ config('app.email', 'arainabdurrehman3@gmail.com') }}</p>
        <p>Generated: {{ date('d-m-Y H:i:s') }}</p>
    </div>
    
    
    <!-- Transport Passes -->
    <div class="passes-container">
        @forelse($students as $student)
            <div class="pass-card">
                <div class="pass-header">
                    <h4>TRANSPORT PASS</h4>
                    <p>{{ config('app.name', 'ICMS') }}</p>
                </div>
                
                <div class="pass-content">
                    <div>
                        <div class="pass-field">
                            <label>Student Name:</label>
                            <div class="value"><strong>{{ $student->student_name ?? 'N/A' }}</strong></div>
                        </div>
                        <div class="pass-field">
                            <label>Student Code:</label>
                            <div class="value">{{ $student->student_code ?? 'N/A' }}</div>
                        </div>
                        <div class="pass-field">
                            <label>Class:</label>
                            <div class="value">{{ $student->class ?? 'N/A' }} {{ $student->section ? '- ' . $student->section : '' }}</div>
                        </div>
                        <div class="pass-field">
                            <label>Campus:</label>
                            <div class="value">{{ $student->campus ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div>
                        <div class="pass-field">
                            <label>Transport Route:</label>
                            <div class="value"><strong>{{ $student->transport_route ?? 'N/A' }}</strong></div>
                        </div>
                        <div class="pass-field">
                            <label>Transport Fare:</label>
                            <div class="value">Rs. {{ $student->transport_fare ? number_format($student->transport_fare, 2) : 'N/A' }}</div>
                        </div>
                        <div class="pass-field">
                            <label>Father Name:</label>
                            <div class="value">{{ $student->father_name ?? 'N/A' }}</div>
                        </div>
                        <div class="pass-field">
                            <label>Contact:</label>
                            <div class="value">{{ $student->father_phone ?? $student->whatsapp_number ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="pass-footer">
                    <p>This pass is valid for the academic year {{ date('Y') }}</p>
                    <p>Issued on: {{ date('d M Y') }}</p>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                No students found for the selected criteria.
            </div>
        @endforelse
    </div>
    
    @if(request()->get('auto_print'))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
</body>
</html>
