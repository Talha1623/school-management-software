<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Vouchers - Print</title>
    @php
        use Carbon\Carbon;
    @endphp
    <style>
        * {
            box-sizing: border-box;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            @page {
                margin: 8mm;
                size: A4;
            }
            body {
                background: white;
                padding: 0;
            }
        }
        
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            margin: 0;
            padding: 10px;
            background: #f5f5f5;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .vouchers-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-start;
        }
        
        .voucher {
            background: white;
            width: calc(33.33% - 7px);
            min-height: auto;
            padding: 12mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            page-break-inside: avoid;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }
        
        @media print {
            .voucher {
                width: calc(33.33% - 4mm);
                margin: 0;
                padding: 10mm;
                page-break-inside: avoid;
                border: 1px solid #ccc;
                box-shadow: none;
            }
            .vouchers-container {
                gap: 4mm;
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
        }
        
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
            color: #000;
        }
        
        .parent-copy {
            position: absolute;
            top: 10mm;
            right: 10mm;
            color: #dc3545;
            font-weight: bold;
            font-size: 11px;
            letter-spacing: 1px;
        }
        
        .student-info {
            margin-bottom: 8px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px dotted #999;
            font-size: 11px;
            line-height: 1.5;
        }
        
        .info-label {
            font-weight: 600;
            width: 90px;
            font-size: 11px;
            color: #333;
        }
        
        .info-value {
            flex: 1;
            font-size: 11px;
            color: #000;
            text-align: right;
        }
        
        .barcode {
            text-align: center;
            margin: 8px 0;
            padding: 6px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 11px;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        
        .bank-details {
            margin: 8px 0;
            padding: 6px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            font-size: 10px;
            line-height: 1.6;
        }
        
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11px;
        }
        
        .fee-table th,
        .fee-table td {
            padding: 6px 5px;
            text-align: left;
            border: 1px solid #333;
            font-size: 10px;
            line-height: 1.4;
        }
        
        .fee-table th {
            background-color: #003471;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            padding: 7px 5px;
        }
        
        .fee-table td {
            background-color: white;
            color: #000;
        }
        
        .fee-table .amount {
            text-align: right;
            font-weight: 500;
        }
        
        .fee-table tr[style*="background-color: #f0f0f0"] {
            background-color: #e8e8e8 !important;
        }
        
        .fee-table tr[style*="background-color: #f0f0f0"] td {
            background-color: #e8e8e8 !important;
            font-weight: bold;
            font-size: 11px;
        }
        
        .dates {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 10px;
            padding: 5px 0;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }
        
        .dates strong {
            font-weight: 600;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 9px;
        }
        
        .history-table th,
        .history-table td {
            padding: 4px 2px;
            text-align: center;
            border: 1px solid #999;
            font-size: 8px;
        }
        
        .history-table th {
            background-color: #003471;
            color: white;
            font-weight: bold;
            padding: 5px 2px;
        }
        
        .history-table td {
            background-color: white;
        }
        
        .history-table td strong {
            font-weight: 600;
        }
        
        .notice {
            margin-top: 12px;
            font-size: 9px;
            color: #555;
            line-height: 1.5;
            padding-top: 8px;
            border-top: 1px solid #ddd;
        }
        
        .notice strong {
            font-weight: 600;
        }
        
        h4 {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 6px 0;
            color: #000;
        }
        
        .print-btn {
            text-align: center;
            margin: 20px;
        }
        
        .print-btn button {
            background: #003471;
            color: white;
            padding: 12px 40px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
        }
        
        .print-btn button:hover {
            background: #004a9f;
        }
        
        @media print {
            .fee-table th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .history-table th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

@if(request()->get('auto_print'))
    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
@endif
</head>
<body>
    <div class="print-btn no-print">
        <button onclick="window.print()">Print Vouchers</button>
    </div>

    <div class="vouchers-container">
    @foreach($vouchers as $voucher)
        @foreach($copyLabels as $copyLabel)
        <div class="voucher">
            <div class="parent-copy">{{ $copyLabel }}</div>
            
            <div class="header">
                <div class="school-name">ROYAL GRAMMAR SCHOOL</div>
            </div>

            <div class="student-info">
                <div class="info-row">
                    <span class="info-label">NAME:</span>
                    <span class="info-value">{{ $voucher['student']->student_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">PARENT:</span>
                    <span class="info-value">{{ $voucher['student']->father_name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CLASS/SEC:</span>
                    <span class="info-value">{{ ($voucher['student']->class ?? 'N/A') }}/{{ ($voucher['student']->section ?? 'N/A') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ROLL NO:</span>
                    <span class="info-value">{{ $voucher['student']->student_code ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CAMPUS:</span>
                    <span class="info-value">{{ $voucher['student']->campus ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">VOUCHER:</span>
                    <span class="info-value">{{ $voucher['voucher_number'] }}</span>
                </div>
            </div>

            <div class="barcode">
                <div style="font-family: monospace; letter-spacing: 2px;">{{ $voucher['voucher_number'] }}</div>
            </div>

            <div class="bank-details">
                <div>Demo Bank - Branch: wsa</div>
                <div>Acc# dsadsadaweq - Acc. Title: adsdsa</div>
            </div>

            <table class="fee-table">
                <thead>
                    <tr>
                        <th>FEE DESCRIPTION</th>
                        <th class="amount">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($voucher['pending_fees']) && $voucher['pending_fees']->count() > 0)
                        @foreach($voucher['pending_fees'] as $fee)
                            <tr>
                                <td>{{ $fee['description'] }}</td>
                                <td class="amount">{{ number_format($fee['amount'], 0) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>Monthly Fee Of {{ $voucher['month'] }} ({{ $voucher['year'] }})</td>
                            <td class="amount">0</td>
                        </tr>
                    @endif
                    @if(isset($voucher['current_fees_subtotal']) && $voucher['current_fees_subtotal'] > 0)
                        <tr style="background-color: #f0f0f0;">
                            <td><strong>CURRENT FEES SUBTOTAL</strong></td>
                            <td class="amount"><strong>{{ number_format($voucher['current_fees_subtotal'], 0) }}</strong></td>
                        </tr>
                    @endif
                    @if(isset($voucher['arrears_amount']) && $voucher['arrears_amount'] > 0)
                        <tr>
                            <td><strong>ARREARS (PREVIOUS DUES)</strong></td>
                            <td class="amount"><strong>{{ number_format($voucher['arrears_amount'], 0) }}</strong></td>
                        </tr>
                    @endif
                    <tr style="background-color: #f0f0f0;">
                        <td><strong>SUBTOTAL</strong></td>
                        <td class="amount"><strong>{{ number_format($voucher['subtotal'], 0) }}</strong></td>
                    </tr>
                    @if($voucher['late_fee'] > 0)
                        <tr>
                            <td>LATE FEE (PREVIOUS DUES)</td>
                            <td class="amount">{{ number_format($voucher['late_fee'], 0) }}</td>
                        </tr>
                    @endif
                    <tr style="background-color: #f0f0f0;">
                        <td><strong>TOTAL:</strong></td>
                        <td class="amount"><strong>{{ number_format($voucher['total'], 0) }}</strong></td>
                    </tr>
                    <tr style="background-color: #f0f0f0;">
                        <td><strong>AFTER DUE DATE:</strong></td>
                        <td class="amount"><strong>{{ number_format($voucher['after_due_date'], 0) }}</strong></td>
                    </tr>
                </tbody>
            </table>

            <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: 11px; font-weight: 600;">
                <span>Total Amount</span>
                <span>{{ number_format($voucher['total'], 0) }}</span>
            </div>

            <div class="dates">
                <div>
                    <strong>Voucher Validity:</strong> {{ $voucher['voucher_validity']->format('d/m/Y') }}
                </div>
                <div>
                    <strong>Due Date:</strong> {{ $voucher['due_date']->format('d/m/Y') }}
                </div>
            </div>

            <div style="margin-top: 30px;">
                <h4 style="margin-bottom: 10px;">STUDENT FEE HISTORY</h4>
                <p style="margin-bottom: 10px;">Year {{ $voucher['year'] }}</p>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            @php
                                $months = ['Dec', 'Nov', 'Oct', 'Sep', 'Aug', 'Jul', 'Jun', 'May', 'Apr', 'Mar', 'Feb', 'Jan'];
                            @endphp
                            @foreach($months as $month)
                                <th>{{ $month }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Total</strong></td>
                            @foreach($months as $month)
                                @php
                                    $fullMonth = Carbon::parse("1 {$month} {$voucher['year']}")->format('F');
                                    $total = $voucher['fee_history'][$fullMonth]['total'] ?? 0;
                                @endphp
                                <td>{{ $total > 0 ? number_format($total, 0) : '0' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td><strong>Paid</strong></td>
                            @foreach($months as $month)
                                @php
                                    $fullMonth = Carbon::parse("1 {$month} {$voucher['year']}")->format('F');
                                    $paid = $voucher['fee_history'][$fullMonth]['paid'] ?? 0;
                                @endphp
                                <td>{{ $paid > 0 ? number_format($paid, 0) : '0' }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="notice">
                <strong>NOTICE:</strong> * This is a computer generated fee voucher, No manual corrections will be acceptable.
            </div>
        </div>
        @endforeach
    @endforeach
    </div>

    <script>
        window.onload = function() {
            // Auto print when page loads
            // window.print();
        };
    </script>
</body>
</html>

