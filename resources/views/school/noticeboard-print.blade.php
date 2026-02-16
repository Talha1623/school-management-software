<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticeboard - {{ $noticeboard->title }}</title>
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
        
        .notice {
            width: 100%;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .school-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .notice-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 11px;
            line-height: 1.5;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 70px;
        }
        
        .info-value {
            text-align: right;
            flex: 1;
            word-wrap: break-word;
            margin-left: 10px;
        }
        
        .title-row {
            margin: 10px 0;
            padding: 8px;
            background: #f5f5f5;
            border-left: 3px solid #000;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        
        .notice-content {
            margin: 15px 0;
            padding: 12px;
            border: 1px dashed #000;
            font-size: 11px;
            line-height: 1.6;
            min-height: 80px;
            word-wrap: break-word;
            white-space: pre-wrap;
            text-align: justify;
        }
        
        .date-section {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 11px;
        }
        
        .date-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .date-value {
            font-size: 12px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #000;
            font-size: 9px;
        }
        
        .footer-text {
            margin-top: 8px;
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
            font-weight: bold;
        }
        
        .btn-print:hover {
            background: #004a9f;
        }
        
        @media print {
            .notice-content {
                border: 1px solid #000;
            }
            .title-row {
                background: transparent;
                border-left: 2px solid #000;
            }
        }
    </style>
</head>
<body>
    <div class="notice">
        <div class="header">
            <div class="school-name">School Management System</div>
            <div class="notice-title">Official Notice</div>
        </div>
        
        <div class="divider"></div>
        
        <div class="title-row">
            {{ $noticeboard->title }}
        </div>
        
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value">{{ $noticeboard->date->format('d-m-Y') }}</span>
        </div>
        
        @if($noticeboard->campus)
        <div class="info-row">
            <span class="info-label">Campus:</span>
            <span class="info-value">{{ $noticeboard->campus }}</span>
        </div>
        @endif
        
        @if($noticeboard->show_on === 'Yes')
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">Active</span>
        </div>
        @endif
        
        <div class="divider"></div>
        
        @if($noticeboard->notice)
        <div class="notice-content">
{{ $noticeboard->notice }}
        </div>
        @else
        <div class="notice-content" style="text-align: center; color: #666; font-style: italic;">
            No additional details provided.
        </div>
        @endif
        
        <div class="divider"></div>
        
        <div class="date-section">
            <div class="date-label">Notice Date</div>
            <div class="date-value">{{ $noticeboard->date->format('d-m-Y') }}</div>
        </div>
        
        <div class="footer">
            <div class="footer-text">Thank You</div>
            <div style="margin-top: 8px; font-size: 9px;">
                Generated: {{ date('d-m-Y H:i:s') }}
            </div>
        </div>
        
        <div class="print-btn no-print">
            <button class="btn-print" onclick="window.print()">
                🖨️ Print Notice
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
