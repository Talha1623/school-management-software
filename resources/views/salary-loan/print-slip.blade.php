<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip - {{ $salary->staff->name ?? 'N/A' }}</title>
    <style>
        @media print {
            @page {
                margin: 10mm;
                size: A4;
            }
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .slip-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #003471;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #003471;
            margin-bottom: 5px;
        }
        
        .slip-title {
            font-size: 18px;
            color: #666;
            margin-top: 10px;
        }
        
        .employee-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #003471;
            display: inline-block;
            width: 150px;
        }
        
        .info-value {
            color: #333;
        }
        
        .salary-details {
            margin-top: 30px;
        }
        
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .salary-table th,
        .salary-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .salary-table th {
            background-color: #003471;
            color: white;
            font-weight: bold;
        }
        
        .salary-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #e9ecef !important;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #003471;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .print-btn {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-print:hover {
            background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        }
    </style>
</head>
<body>
    <div class="slip-container">
        <div class="header">
            <div class="school-name">ROYAL GRAMMAR SCHOOL</div>
            <div class="slip-title">SALARY SLIP</div>
            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                {{ $salary->salary_month }} {{ $salary->year }}
            </div>
        </div>
        
        <div class="employee-info">
            <div>
                <div class="info-item">
                    <span class="info-label">Employee ID:</span>
                    <span class="info-value">{{ $salary->staff->emp_id ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $salary->staff->name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Designation:</span>
                    <span class="info-value">{{ $salary->staff->designation ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Campus:</span>
                    <span class="info-value">{{ $salary->staff->campus ?? 'N/A' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Month:</span>
                    <span class="info-value">{{ $salary->salary_month }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Year:</span>
                    <span class="info-value">{{ $salary->year }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        @if($salary->status == 'Paid')
                            <span style="color: green; font-weight: bold;">Paid</span>
                        @elseif($salary->status == 'Partial')
                            <span style="color: orange; font-weight: bold;">Partial</span>
                        @else
                            <span style="color: red; font-weight: bold;">Pending</span>
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value">{{ $salary->updated_at->format('d-m-Y') }}</span>
                </div>
            </div>
        </div>
        
        <div class="salary-details">
            <h3 style="color: #003471; margin-bottom: 15px;">Salary Details</h3>
            <table class="salary-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Salary</td>
                        <td style="text-align: right;">{{ number_format($salary->basic ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Present Days</td>
                        <td style="text-align: right;">{{ $salary->present ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Absent Days</td>
                        <td style="text-align: right;">{{ $salary->absent ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td>Late Arrivals</td>
                        <td style="text-align: right;">{{ $salary->late ?? 0 }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Salary Generated</td>
                        <td style="text-align: right;">{{ number_format($salary->salary_generated ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Amount Paid</td>
                        <td style="text-align: right; color: green; font-weight: bold;">{{ number_format($salary->amount_paid ?? 0, 2) }}</td>
                    </tr>
                    @if($salary->loan_repayment > 0)
                    <tr>
                        <td>Loan Repayment</td>
                        <td style="text-align: right; color: red;">{{ number_format($salary->loan_repayment ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Net Amount</td>
                        <td style="text-align: right; font-size: 16px; color: #003471;">
                            {{ number_format(($salary->amount_paid ?? 0) - ($salary->loan_repayment ?? 0), 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>This is a computer generated salary slip.</p>
            <p>Generated on: {{ date('d-m-Y H:i:s') }}</p>
        </div>
        
        <div class="print-btn no-print">
            <button class="btn-print" onclick="window.print()">
                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                Print Slip
            </button>
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

