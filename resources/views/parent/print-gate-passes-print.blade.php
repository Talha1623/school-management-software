<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Gate Passes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #e5e5e5;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .print-container {
            max-width: 100%;
            margin: 0 auto;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .cards-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                page-break-inside: avoid;
            }

            .gate-pass-card {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }

        /* Gate Pass Card Styling */
        .gate-pass-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            position: relative;
            width: 100%;
            min-height: 500px;
            display: flex;
            flex-direction: column;
            border: 2px solid #e9ecef;
            overflow: hidden;
        }

        .gate-pass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #003471 0%, #004a9f 50%, #003471 100%);
        }

        .gate-pass-header {
            margin-bottom: 12px;
        }

        .header-content {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .school-logo {
            flex-shrink: 0;
        }

        .logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
            background: white;
            padding: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .school-info {
            flex: 1;
        }

        .school-name {
            font-size: 16px;
            font-weight: 700;
            color: #003471;
            margin-bottom: 3px;
            line-height: 1.3;
            letter-spacing: 0.3px;
        }

        .campus-name {
            font-size: 12px;
            color: #555;
            margin-bottom: 2px;
            line-height: 1.3;
            font-weight: 500;
        }

        .contact-info {
            font-size: 10px;
            color: #777;
            line-height: 1.3;
        }

        .gate-pass-title {
            background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 18px;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 2px 6px rgba(0, 52, 113, 0.3);
        }

        .gate-pass-details {
            position: relative;
            margin-bottom: 15px;
            flex: 1;
            padding-right: 10px;
        }

        .gate-pass-details::before {
            content: '';
            position: absolute;
            top: -10px;
            right: -10px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(0, 52, 113, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
        }

        .detail-item {
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
            padding: 8px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 6px;
            border-left: 3px solid #003471;
        }

        .detail-label {
            font-size: 10px;
            color: #666;
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 13px;
            color: #003471;
            font-weight: 700;
            display: block;
            line-height: 1.4;
        }

        .gate-pass-qr {
            text-align: center;
            margin: 18px 0;
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .qr-code-img {
            width: 130px;
            height: 130px;
            display: block;
            margin: 0 auto;
        }

        .gate-pass-footer {
            font-size: 9px;
            color: #666;
            text-align: center;
            margin-top: auto;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
            line-height: 1.5;
            font-weight: 500;
        }

        @media (max-width: 1400px) {
            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 1000px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="cards-grid">
            @foreach($parents as $parent)
            <div class="gate-pass-card">
                <!-- Header -->
                <div class="gate-pass-header">
                    <div class="header-content">
                        <div class="school-logo">
                            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="School Logo" class="logo-img" onerror="this.style.display='none'">
                        </div>
                        <div class="school-info">
                            <h6 class="school-name">{{ config('app.name', 'ICMS Management System') }}</h6>
                            <p class="campus-name">{{ $parent['campus'] }}</p>
                            @if(config('app.contact'))
                            <p class="contact-info">Contact: {{ config('app.contact') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Title Bar -->
                <div class="gate-pass-title">
                    Gate Pass
                </div>
                
                <!-- Details -->
                <div class="gate-pass-details">
                    <div class="detail-item">
                        <span class="detail-label">For Campus:</span>
                        <span class="detail-value">{{ $parent['campus'] }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Issued To:</span>
                        <span class="detail-value">{{ $parent['name'] }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Issued On:</span>
                        <span class="detail-value">{{ $parent['issue_date'] }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Valid Till:</span>
                        <span class="detail-value">
                            @if($parent['pass_validity'] == '1 Month')
                                1 Month from the issue date
                            @elseif($parent['pass_validity'] == '3 Months')
                                3 Months from the issue date
                            @elseif($parent['pass_validity'] == '6 Months')
                                Six Months from the issue date
                            @elseif($parent['pass_validity'] == '1 Year')
                                1 Year from the issue date
                            @else
                                {{ $parent['pass_validity'] }} from the issue date
                            @endif
                        </span>
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="gate-pass-qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode('GatePass:' . $parent['id'] . ':' . $parent['name'] . ':' . $parent['issue_date']) }}" alt="QR Code" class="qr-code-img">
                </div>
                
                <!-- Footer -->
                <div class="gate-pass-footer">
                    Please carry this card with you when visiting the school. Without a pass, access will not be granted.
                </div>
            </div>
            @endforeach
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

