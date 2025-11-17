<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experience Certificate - {{ $staff->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            background: #f5f5f5;
            padding: 20px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }

        .certificate-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 60px 80px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .certificate-border {
            border: 8px solid #003471;
            padding: 40px;
            position: relative;
        }

        .certificate-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .school-logo {
            margin-bottom: 20px;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .school-name {
            font-size: 28px;
            font-weight: bold;
            color: #003471;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .school-address {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .certificate-title {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #003471;
            margin: 40px 0;
            text-decoration: underline;
            text-underline-offset: 10px;
        }

        .certificate-body {
            font-size: 16px;
            line-height: 2;
            text-align: justify;
            margin: 30px 0;
            color: #333;
        }

        .staff-details {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-left: 4px solid #003471;
        }

        .detail-row {
            margin: 10px 0;
            font-size: 16px;
        }

        .detail-label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }

        .detail-value {
            color: #003471;
            font-weight: 600;
        }

        .certificate-footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }

        .signature-section {
            text-align: center;
            width: 45%;
        }

        .signature-line {
            border-top: 2px solid #333;
            width: 200px;
            margin: 60px auto 10px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 14px;
        }

        .signature-title {
            font-size: 12px;
            color: #666;
        }

        .date-section {
            text-align: right;
            margin-top: 40px;
            font-size: 16px;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #003471;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1000;
        }

        .print-btn:hover {
            background: #004a9f;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn no-print">
        <span>🖨️ Print Certificate</span>
    </button>

    <div class="certificate-container">
        <div class="certificate-border">
            <!-- Header -->
            <div class="certificate-header">
                <div class="school-logo">
                    <img src="{{ asset('assets/images/logo-icon.png') }}" alt="School Logo" class="logo-img" onerror="this.style.display='none'">
                </div>
                <h1 class="school-name">{{ $schoolName }}</h1>
                @if($schoolAddress)
                <p class="school-address">{{ $schoolAddress }}</p>
                @endif
            </div>

            <!-- Title -->
            <h2 class="certificate-title">Experience Certificate</h2>

            <!-- Body -->
            <div class="certificate-body">
                <p>This is to certify that <strong>{{ $staff->name }}</strong>
                @if($staff->father_husband_name)
                , son/daughter of <strong>{{ $staff->father_husband_name }}</strong>
                @endif
                , has been working in our institution.</p>

                <div class="staff-details">
                    <div class="detail-row">
                        <span class="detail-label">Name:</span>
                        <span class="detail-value">{{ $staff->name }}</span>
                    </div>
                    @if($staff->emp_id)
                    <div class="detail-row">
                        <span class="detail-label">Employee ID:</span>
                        <span class="detail-value">{{ $staff->emp_id }}</span>
                    </div>
                    @endif
                    @if($staff->designation)
                    <div class="detail-row">
                        <span class="detail-label">Designation:</span>
                        <span class="detail-value">{{ $staff->designation }}</span>
                    </div>
                    @endif
                    @if($staff->campus)
                    <div class="detail-row">
                        <span class="detail-label">Campus:</span>
                        <span class="detail-value">{{ $staff->campus }}</span>
                    </div>
                    @endif
                    @if($staff->joining_date)
                    <div class="detail-row">
                        <span class="detail-label">Joining Date:</span>
                        <span class="detail-value">{{ \Carbon\Carbon::parse($staff->joining_date)->format('d F Y') }}</span>
                    </div>
                    @endif
                    @if($staff->qualification)
                    <div class="detail-row">
                        <span class="detail-label">Qualification:</span>
                        <span class="detail-value">{{ $staff->qualification }}</span>
                    </div>
                    @endif
                </div>

                <p style="margin-top: 30px;">During the period of his/her service at this institution, he/she has shown excellent performance, dedication, and commitment to his/her duties. He/She has been regular in attendance and has contributed significantly to the growth and development of the institution.</p>

                <p style="margin-top: 20px;">This certificate is issued on the request of the employee and we wish him/her success in all future endeavors.</p>
            </div>

            <!-- Footer -->
            <div class="certificate-footer">
                <div class="signature-section">
                    <div class="signature-line"></div>
                    <div class="signature-name">Principal</div>
                    <div class="signature-title">{{ $schoolName }}</div>
                </div>
                <div class="date-section">
                    <p><strong>Date:</strong> {{ $currentDate }}</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto print option (uncomment if needed)
            // window.print();
        };
    </script>
</body>
</html>

