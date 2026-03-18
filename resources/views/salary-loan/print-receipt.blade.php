<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $salary->staff->name ?? 'N/A' }}</title>
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
        
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
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
        
        .receipt-title {
            font-size: 20px;
            color: #003471;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .receipt-number {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .payment-info {
            margin-bottom: 30px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: bold;
            color: #003471;
            width: 200px;
        }
        
        .info-value {
            color: #333;
            text-align: right;
            flex: 1;
        }
        
        .amount-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            border: 2px solid #003471;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }
        
        .amount-label {
            font-weight: bold;
            color: #003471;
        }
        
        .amount-value {
            font-weight: bold;
            color: #28a745;
            font-size: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            background: #28a745;
            color: white;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #003471;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            margin-top: 50px;
            padding-top: 5px;
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
    <div class="receipt-container">
        <div class="header">
            <div class="school-name">ROYAL GRAMMAR SCHOOL</div>
            <div class="receipt-title">PAYMENT RECEIPT</div>
            <div class="receipt-number">Receipt #: SAL-{{ str_pad($salary->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
        
        <div class="payment-info">
            <div class="info-row">
                <span class="info-label">Employee Name:</span>
                <span class="info-value">{{ $salary->staff->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Employee ID:</span>
                <span class="info-value">{{ $salary->staff->emp_id ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Designation:</span>
                <span class="info-value">{{ $salary->staff->designation ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Campus:</span>
                <span class="info-value">{{ $salary->staff->campus ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Salary Month:</span>
                <span class="info-value">{{ $salary->salary_month }} {{ $salary->year }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Date:</span>
                <span class="info-value">{{ $salary->updated_at->format('d-m-Y') }}</span>
            </div>
        </div>
        
        <div class="amount-section">
            @php
                $salaryType = strtolower(trim($salary->staff->salary_type ?? ''));
                $isPerHour = $salaryType === 'per hour';
                $isPerLecture = $salaryType === 'lecture';
                $isFullTime = !$isPerHour && !$isPerLecture;
            @endphp
            
            {{-- Show attendance details based on salary type --}}
            @if($isPerHour && isset($attendanceSummary['total_minutes']))
                @php
                    $totalHours = round($attendanceSummary['total_minutes'] / 60, 2);
                    $totalClasses = $attendanceSummary['present'] ?? 0;
                @endphp
                <div class="amount-row">
                    <span class="amount-label">Salary Type:</span>
                    <span class="info-value">Per Hour</span>
                </div>
                <div class="amount-row">
                    <span class="amount-label">Total Hours:</span>
                    <span class="info-value">{{ number_format($totalHours, 2) }} hours</span>
                </div>
                <div class="amount-row">
                    <span class="amount-label">Total Classes:</span>
                    <span class="info-value">{{ $totalClasses }}</span>
                </div>
            @elseif($isPerLecture && isset($attendanceSummary['total_lectures']))
                <div class="amount-row">
                    <span class="amount-label">Salary Type:</span>
                    <span class="info-value">Per Lecture</span>
                </div>
                <div class="amount-row">
                    <span class="amount-label">Total Lectures:</span>
                    <span class="info-value">{{ $attendanceSummary['total_lectures'] }}</span>
                </div>
            @else
                <div class="amount-row">
                    <span class="amount-label">Salary Type:</span>
                    <span class="info-value">Full Time</span>
                </div>
                <div class="amount-row">
                    <span class="amount-label">Present Days:</span>
                    <span class="info-value">{{ $salary->present ?? 0 }}</span>
                </div>
                <div class="amount-row">
                    <span class="amount-label">Absent Days:</span>
                    <span class="info-value">{{ $salary->absent ?? 0 }}</span>
                </div>
            @endif
            
            <div style="border-top: 1px solid #ddd; margin: 15px 0; padding-top: 15px;"></div>
            
            {{-- Salary breakdown --}}
            <div class="amount-row">
                <span class="amount-label">Basic Salary:</span>
                <span class="info-value">₹{{ number_format($salary->basic ?? 0, 2) }}</span>
            </div>
            
            @if(($salary->bonus_amount ?? 0) > 0)
            <div class="amount-row">
                <span class="amount-label">Bonus:</span>
                <span class="info-value" style="color: #28a745;">+ ₹{{ number_format($salary->bonus_amount ?? 0, 2) }}</span>
            </div>
            @endif
            
            @if(($salary->deduction_amount ?? 0) > 0)
            <div class="amount-row">
                <span class="amount-label">Deduction:</span>
                <span class="info-value" style="color: #dc3545;">- ₹{{ number_format($salary->deduction_amount ?? 0, 2) }}</span>
            </div>
            @endif
            
            @php
                $baseSalary = ($salary->basic ?? 0) + ($salary->bonus_amount ?? 0) - ($salary->deduction_amount ?? 0);
            @endphp
            
            @if($salary->loan_repayment > 0)
            <div class="amount-row">
                <span class="amount-label">Loan Repayment:</span>
                <span class="info-value" style="color: #dc3545;">- ₹{{ number_format($salary->loan_repayment ?? 0, 2) }}</span>
            </div>
            @endif
            
            <div class="amount-row" style="border-top: 1px solid #003471; margin-top: 10px; padding-top: 10px;">
                <span class="amount-label">Salary Generated:</span>
                <span class="info-value" style="font-weight: bold; font-size: 18px;">₹{{ number_format($salary->salary_generated ?? 0, 2) }}</span>
            </div>
            
            <div class="amount-row" style="border-top: 2px solid #003471; margin-top: 15px; padding-top: 15px;">
                <span class="amount-label">Amount Paid:</span>
                <span class="amount-value">₹{{ number_format($salary->amount_paid ?? 0, 2) }}</span>
            </div>
            
            @if($salary->loan_repayment > 0)
            <div class="amount-row" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
                <span class="amount-label">Note:</span>
                <span class="info-value" style="font-style: italic;">Loan repayment of ₹{{ number_format($salary->loan_repayment ?? 0, 2) }} has been deducted from your salary.</span>
            </div>
            @endif
        </div>
        
        <div style="text-align: center;">
            <span class="status-badge">PAID</span>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Employee Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>This is a computer generated receipt.</strong></p>
            <p>Generated on: {{ date('d-m-Y H:i:s') }}</p>
            <p style="margin-top: 10px;">Thank you for your service!</p>
        </div>
        
        <div class="print-btn no-print">
            <button class="btn-print" onclick="window.print()">
                <span style="font-size: 16px; vertical-align: middle;">🖨️</span>
                Print Receipt
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
