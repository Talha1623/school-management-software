<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report - {{ $settings->school_name ?? 'School' }}</title>
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
            width: 50px;
            text-align: center;
        }
        
        th.text-right {
            text-align: right;
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
        
        .text-right {
            text-align: right;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
            font-style: italic;
        }
        
        /* Totals Section */
        .totals {
            margin-top: 25px;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #003471;
            border-radius: 5px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            font-size: 14px;
        }
        
        .totals-item {
            text-align: center;
        }
        
        .totals-item strong {
            display: block;
            color: #003471;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .totals-item span {
            color: #555;
            font-size: 18px;
            font-weight: 600;
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
            
            .totals {
                flex-direction: column;
                gap: 10px;
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
                @if($reportType === 'total-products')
                    <h2>Total Products Report</h2>
                    <p>Complete listing of all products in inventory</p>
                @elseif($reportType === 'out-of-stock')
                    <h2>Out Of Stock Products Report</h2>
                    <p>Products with zero stock availability</p>
                @elseif($reportType === 'low-stock')
                    <h2>Low Stock Products Report</h2>
                    <p>Products below threshold level ({{ $lowStockThreshold }} units)</p>
                @elseif($reportType === 'monthly-sales-profit')
                    <h2>Monthly Sales & Profit Report</h2>
                    <p>Sales and profit analysis for {{ $monthLabel ?? 'current month' }}</p>
                @endif
            </div>
        </div>
        
        <!-- Print Info -->
        <div class="print-info">
            <div class="print-date">
                <strong>Generated at:</strong> {{ $generatedAt->format('d M Y, h:i A') }}
                @if($reportType === 'low-stock')
                    | <strong>Threshold:</strong> {{ $lowStockThreshold }} units
                @endif
                @if($reportType === 'monthly-sales-profit' && isset($monthLabel))
                    | <strong>Month:</strong> {{ $monthLabel }}
                @endif
            </div>
            <button type="button" class="print-btn no-print" onclick="window.print()">
                🖨️ Print
            </button>
        </div>
        
        <!-- Table Content -->
        <div class="table-wrapper">
            @if($reportType === 'total-products')
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th class="text-right">Purchase Price</th>
                            <th class="text-right">Sale Price</th>
                            <th class="text-right">Total Stock</th>
                            <th>Campus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $product->product_name }}</strong></td>
                                <td>{{ $product->category }}</td>
                                <td class="text-right">PKR {{ number_format($product->purchase_price, 2) }}</td>
                                <td class="text-right">PKR {{ number_format($product->sale_price, 2) }}</td>
                                <td class="text-right">
                                    <strong style="color: {{ $product->total_stock <= 0 ? '#dc3545' : ($product->total_stock <= 5 ? '#ffc107' : '#28a745') }};">
                                        {{ $product->total_stock }}
                                    </strong>
                                </td>
                                <td>{{ $product->campus }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
            @elseif($reportType === 'out-of-stock')
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th class="text-right">Total Stock</th>
                            <th>Campus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $product->product_name }}</strong></td>
                                <td>{{ $product->category }}</td>
                                <td class="text-right">
                                    <strong style="color: #dc3545;">{{ $product->total_stock }}</strong>
                                </td>
                                <td>{{ $product->campus }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">No out of stock products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
            @elseif($reportType === 'low-stock')
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th class="text-right">Total Stock</th>
                            <th>Campus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $product->product_name }}</strong></td>
                                <td>{{ $product->category }}</td>
                                <td class="text-right">
                                    <strong style="color: #ffc107;">{{ $product->total_stock }}</strong>
                                </td>
                                <td>{{ $product->campus }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state">No low stock products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
            @elseif($reportType === 'monthly-sales-profit')
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sale Date</th>
                            <th>Product Name</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Total Amount</th>
                            <th class="text-right">Profit</th>
                            <th>Campus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($saleRecords as $index => $record)
                            @php
                                $purchasePrice = $record->product?->purchase_price ?? 0;
                                $profit = ($record->unit_price - $purchasePrice) * $record->quantity;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ date('d M Y', strtotime($record->sale_date)) }}</td>
                                <td><strong>{{ $record->product_name }}</strong></td>
                                <td class="text-right">{{ $record->quantity }}</td>
                                <td class="text-right">PKR {{ number_format($record->unit_price, 2) }}</td>
                                <td class="text-right">PKR {{ number_format($record->total_amount, 2) }}</td>
                                <td class="text-right">
                                    <strong style="color: {{ $profit >= 0 ? '#28a745' : '#dc3545' }};">
                                        PKR {{ number_format($profit, 2) }}
                                    </strong>
                                </td>
                                <td>{{ $record->campus }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty-state">No sales records found for this month.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                @if(isset($salesTotal) && isset($profitTotal))
                    <div class="totals">
                        <div class="totals-item">
                            <strong>Total Sales</strong>
                            <span style="color: #003471;">PKR {{ number_format($salesTotal, 2) }}</span>
                        </div>
                        <div class="totals-item">
                            <strong>Total Profit</strong>
                            <span style="color: {{ $profitTotal >= 0 ? '#28a745' : '#dc3545' }};">PKR {{ number_format($profitTotal, 2) }}</span>
                        </div>
                    </div>
                @endif
                
            @else
                <div class="empty-state">
                    <h3>Invalid Report</h3>
                    <p>The requested report type is not available.</p>
                </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This is a system-generated report.</p>
        </div>
    </div>
</body>
</html>
