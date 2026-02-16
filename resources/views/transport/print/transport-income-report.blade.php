<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Income Report</title>
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
        
        .summary-card {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .summary-card h5 {
            color: #155724;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #28a745;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background: #003471;
            color: white;
            font-weight: 600;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .month-section {
            margin-bottom: 30px;
        }
        
        .month-header {
            background: #e9ecef;
            padding: 10px;
            font-weight: bold;
            border: 1px solid #ccc;
            border-bottom: none;
        }
        
        .total-row {
            font-weight: bold;
            background: #fff3cd !important;
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
        <h3>Transport Income Report</h3>
        <p>{{ config('app.address', 'Defence View') }}</p>
        <p>Phone: {{ config('app.phone', '+923316074246') }} | Email: {{ config('app.email', 'arainabdurrehman3@gmail.com') }}</p>
        <p>Generated: {{ date('d-m-Y H:i:s') }}</p>
    </div>
    
    
    <!-- Total Income Summary -->
    <div class="summary-card">
        <h5>Total Transport Income</h5>
        <div class="value">Rs. {{ number_format($totalIncome, 2) }}</div>
    </div>
    
    <!-- Monthly Breakdown -->
    @forelse($monthlyIncome as $month => $data)
        <div class="month-section">
            <div class="month-header">
                {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }} - 
                Total: Rs. {{ number_format($data['total'], 2) }} 
                ({{ $data['count'] }} {{ Str::plural('payment', $data['count']) }})
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Code</th>
                        <th>Payment Title</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">Late Fee</th>
                        <th class="text-right">Total</th>
                        <th>Payment Date</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['payments'] as $index => $payment)
                        @php
                            $amount = (float) ($payment->payment_amount ?? 0);
                            $discount = (float) ($payment->discount ?? 0);
                            $lateFee = (float) ($payment->late_fee ?? 0);
                            $total = max(0, $amount - $discount) + max(0, $lateFee);
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $payment->student_code ?? 'N/A' }}</td>
                            <td>{{ $payment->payment_title ?? 'N/A' }}</td>
                            <td class="text-right">{{ number_format($amount, 2) }}</td>
                            <td class="text-right">{{ number_format($discount, 2) }}</td>
                            <td class="text-right">{{ number_format($lateFee, 2) }}</td>
                            <td class="text-right"><strong>{{ number_format($total, 2) }}</strong></td>
                            <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}</td>
                            <td>{{ $payment->method ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6" class="text-right"><strong>Month Total:</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($data['total'], 2) }}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <div style="text-align: center; padding: 40px; color: #666;">
            No transport income records found for the selected criteria.
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
