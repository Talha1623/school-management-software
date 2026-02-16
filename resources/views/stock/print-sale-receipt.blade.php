<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Receipt - {{ $saleRecord->id }}</title>
    <style>
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            body {
                margin: 0;
                padding: 10mm;
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
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 10mm;
            background: white;
        }
        
        .receipt {
            width: 100%;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .store-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .store-address {
            font-size: 10px;
            margin-bottom: 3px;
        }
        
        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 8px;
            text-transform: uppercase;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 11px;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .info-value {
            text-align: right;
        }
        
        .items-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
        }
        
        .items-table th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            font-size: 10px;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 4px 0;
            font-size: 11px;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .total-section {
            border-top: 1px dashed #000;
            margin-top: 10px;
            padding-top: 8px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 12px;
        }
        
        .total-label {
            font-weight: bold;
        }
        
        .total-amount {
            font-weight: bold;
            font-size: 14px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }
        
        .thank-you {
            margin-top: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .print-btn {
            text-align: center;
            margin-top: 20px;
        }
        
        .btn-print {
            background: #003471;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 5px;
        }
        
        .btn-print:hover {
            background: #004a9f;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="store-name">School Management System</div>
            <div class="store-address">Sale Receipt</div>
            <div class="receipt-title">Invoice</div>
        </div>
        
        <div class="divider"></div>
        
        <div class="info-row">
            <span class="info-label">Receipt #:</span>
            <span class="info-value">#{{ str_pad($saleRecord->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value">{{ date('d-m-Y', strtotime($saleRecord->sale_date)) }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Time:</span>
            <span class="info-value">{{ date('H:i:s', strtotime($saleRecord->created_at)) }}</span>
        </div>
        
        <div class="divider"></div>
        
        <div class="info-row">
            <span class="info-label">Product:</span>
            <span class="info-value">{{ $saleRecord->product_name }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Category:</span>
            <span class="info-value">{{ $saleRecord->category }}</span>
        </div>
        
        @if($saleRecord->product && $saleRecord->product->product_code)
        <div class="info-row">
            <span class="info-label">Code:</span>
            <span class="info-value">{{ $saleRecord->product->product_code }}</span>
        </div>
        @endif
        
        <div class="divider"></div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $saleRecord->product_name }}</td>
                    <td class="text-center">{{ $saleRecord->quantity }}</td>
                    <td class="text-right">PKR {{ number_format($saleRecord->unit_price, 2) }}</td>
                    <td class="text-right">PKR {{ number_format($saleRecord->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="divider"></div>
        
        <div class="total-section">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span class="info-value">PKR {{ number_format($saleRecord->total_amount, 2) }}</span>
            </div>
            <div class="total-row">
                <span class="total-label">Total:</span>
                <span class="total-amount">PKR {{ number_format($saleRecord->total_amount, 2) }}</span>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="info-row">
            <span class="info-label">Payment Method:</span>
            <span class="info-value">{{ $saleRecord->method }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Campus:</span>
            <span class="info-value">{{ $saleRecord->campus }}</span>
        </div>
        
        @if($saleRecord->received_by)
        <div class="info-row">
            <span class="info-label">Received By:</span>
            <span class="info-value">{{ $saleRecord->received_by }}</span>
        </div>
        @endif
        
        @if($saleRecord->notes)
        <div class="info-row">
            <span class="info-label">Notes:</span>
            <span class="info-value" style="text-align: left; word-wrap: break-word;">{{ $saleRecord->notes }}</span>
        </div>
        @endif
        
        <div class="footer">
            <div class="thank-you">Thank You!</div>
            <div style="margin-top: 5px;">Visit Again</div>
            <div style="margin-top: 10px; font-size: 9px;">
                Generated: {{ date('d-m-Y H:i:s') }}
            </div>
        </div>
        
        <div class="print-btn no-print">
            <button class="btn-print" onclick="window.print()">
                🖨️ Print Receipt
            </button>
        </div>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
