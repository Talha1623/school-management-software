<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parents List - {{ $settings->school_name ?? 'School' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            padding: 20px;
            line-height: 1.6;
        }
        
        .print-container {
            max-width: 100%;
            margin: 0 auto;
            background: #ffffff;
        }
        
        /* Header Section */
        .header-section {
            border-bottom: 3px solid #003471;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .school-info {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .school-name {
            font-size: 28px;
            font-weight: 700;
            color: #003471;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .school-details {
            font-size: 13px;
            color: #555;
            line-height: 1.8;
        }
        
        .school-details div {
            margin: 3px 0;
        }
        
        .school-details strong {
            color: #333;
            font-weight: 600;
        }
        
        .report-title {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
        }
        
        .report-title h2 {
            font-size: 22px;
            font-weight: 600;
            color: #003471;
            margin-bottom: 5px;
        }
        
        .report-title p {
            font-size: 12px;
            color: #777;
        }
        
        /* Print Info */
        .print-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .print-date {
            font-size: 11px;
            color: #666;
        }
        
        .print-btn {
            font-size: 13px;
            padding: 8px 16px;
            border: 1px solid #003471;
            background: #003471;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            background: #002a5a;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 52, 113, 0.3);
        }
        
        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        thead {
            background: linear-gradient(135deg, #003471 0%, #004a9e 100%);
            color: #ffffff;
        }
        
        th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #002a5a;
        }
        
        th:first-child {
            width: 40px;
            text-align: center;
        }
        
        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.2s ease;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tbody tr:hover {
            background-color: #e8f0f7;
        }
        
        td {
            padding: 10px;
            border: 1px solid #e0e0e0;
            color: #333;
        }
        
        td:first-child {
            text-align: center;
            font-weight: 600;
            color: #003471;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-style: italic;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            font-size: 11px;
            color: #777;
        }
        
        /* Print Media Queries */
        @media print {
            body {
                padding: 10px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .print-container {
                max-width: 100%;
            }
            
            .header-section {
                page-break-inside: avoid;
            }
            
            table {
                page-break-inside: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tbody tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            tbody tr:nth-child(even) {
                background-color: #f8f9fa !important;
            }
            
            .print-btn {
                display: none;
            }
            
            @page {
                margin: 1cm;
                size: A4;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .school-name {
                font-size: 22px;
            }
            
            .school-details {
                font-size: 11px;
            }
            
            table {
                font-size: 11px;
            }
            
            th, td {
                padding: 8px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="school-info">
                <div class="school-name">
                    {{ $settings->school_name ?? 'School Name' }}
                </div>
                <div class="school-details">
                    @if($settings->address)
                        <div><strong>Address:</strong> {{ $settings->address }}</div>
                    @endif
                    @if($settings->school_phone)
                        <div><strong>Phone:</strong> {{ $settings->school_phone }}</div>
                    @endif
                    @if($settings->school_email)
                        <div><strong>Email:</strong> {{ $settings->school_email }}</div>
                    @endif
                </div>
            </div>
            
            <div class="report-title">
                <h2>Parents Accounts List</h2>
                <p>Complete list of all registered parent accounts</p>
            </div>
        </div>
        
        <!-- Print Info -->
        <div class="print-info">
            <div class="print-date">
                <strong>Printed:</strong> {{ $printedAt }}
            </div>
            <button type="button" class="print-btn no-print" onclick="window.print()">
                🖨️ Print
            </button>
        </div>
        
        <!-- Table -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Parent Name</th>
                        <th>Phone</th>
                        <th>WhatsApp</th>
                        <th>Email</th>
                        <th>ID Card</th>
                        <th>Profession</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parents as $index => $parent)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $parent->name ?? 'N/A' }}</strong></td>
                            <td>{{ $parent->phone ?? 'N/A' }}</td>
                            <td>{{ $parent->whatsapp ?? 'N/A' }}</td>
                            <td>{{ $parent->email ?? 'N/A' }}</td>
                            <td>{{ $parent->id_card_number ?? 'N/A' }}</td>
                            <td>{{ $parent->profession ?? 'N/A' }}</td>
                            <td>{{ $parent->address ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">
                                No parent accounts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This is a system-generated report. Total Records: {{ count($parents) }}</p>
        </div>
    </div>
    
    @if(request()->get('auto_print'))
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        </script>
    @endif
</body>
</html>
