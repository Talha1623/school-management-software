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
        @media print {
            .no-print {
                display: none;
            }
            @page {
                margin: 10mm;
            }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background: #f5f5f5;
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
            padding: 10mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            page-break-inside: avoid;
            margin-bottom: 10px;
        }
        @media print {
            .voucher {
                width: calc(33.33% - 5mm);
                margin: 0;
                padding: 8mm;
                page-break-inside: avoid;
            }
            .vouchers-container {
                gap: 5mm;
            }
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }
        .school-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .parent-copy {
            position: absolute;
            top: 8mm;
            right: 8mm;
            color: red;
            font-weight: bold;
            font-size: 10px;
        }
        .student-info {
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dotted #ccc;
            font-size: 11px;
        }
        .info-label {
            font-weight: bold;
            width: 80px;
            font-size: 10px;
        }
        .info-value {
            flex: 1;
            font-size: 10px;
        }
        .barcode {
            text-align: center;
            margin: 10px 0;
            padding: 5px;
            background: #f9f9f9;
            font-size: 10px;
        }
        .bank-details {
            margin: 10px 0;
            padding: 5px;
            background: #f0f0f0;
            font-size: 9px;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        .fee-table th,
        .fee-table td {
            padding: 5px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        .fee-table th {
            background-color: #003471;
            color: white;
            font-weight: bold;
        }
        .fee-table .amount {
            text-align: right;
        }
        .dates {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 9px;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 8px;
        }
        .history-table th,
        .history-table td {
            padding: 3px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 7px;
        }
        .history-table th {
            background-color: #003471;
            color: white;
        }
        .notice {
            margin-top: 15px;
            font-size: 8px;
            color: #666;
        }
        .print-btn {
            text-align: center;
            margin: 20px;
        }
        .print-btn button {
            background: #003471;
            color: white;
            padding: 10px 30px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="print-btn no-print">
        <button onclick="window.print()">Print Vouchers</button>
    </div>

    <div class="vouchers-container">
    @foreach($vouchers as $voucher)
        <div class="voucher">
            <div class="parent-copy">PARENT COPY</div>
            
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
                    <tr>
                        <td>Monthly Fee Of {{ $voucher['month'] }} ({{ $voucher['year'] }}):</td>
                        <td class="amount">{{ number_format($voucher['monthly_fee'], 0) }}</td>
                    </tr>
                    <tr>
                        <td><strong>SUBTOTAL</strong></td>
                        <td class="amount"><strong>{{ number_format($voucher['subtotal'], 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td>LATE FEE (PREVIOUS DUES):</td>
                        <td class="amount">{{ number_format($voucher['late_fee'], 0) }}</td>
                    </tr>
                    <tr>
                        <td><strong>TOTAL:</strong></td>
                        <td class="amount"><strong>{{ number_format($voucher['total'], 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td><strong>AFTER DUE DATE:</strong></td>
                        <td class="amount"><strong>{{ number_format($voucher['after_due_date'], 0) }}</strong></td>
                    </tr>
                </tbody>
            </table>

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
    </div>

    <script>
        window.onload = function() {
            // Auto print when page loads
            // window.print();
        };
    </script>
</body>
</html>

