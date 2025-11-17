<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date of Birth Certificate - {{ $student->student_name }}</title>
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

        .student-details {
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

        .birth-info {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #003471;
            border-radius: 10px;
        }

        .birth-date {
            font-size: 36px;
            font-weight: bold;
            color: #003471;
            margin: 20px 0;
        }

        .birth-date-text {
            font-size: 20px;
            color: #555;
            margin-top: 10px;
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
            <h2 class="certificate-title">Date of Birth Certificate</h2>

            <!-- Body -->
            <div class="certificate-body">
                <p>This is to certify that according to the school records, the following information is correct:</p>

                <div class="student-details">
                    <div class="detail-row">
                        <span class="detail-label">Student Name:</span>
                        <span class="detail-value">{{ $student->student_name }}</span>
                    </div>
                    @if($student->father_name)
                    <div class="detail-row">
                        <span class="detail-label">Father's Name:</span>
                        <span class="detail-value">{{ $student->father_name }}</span>
                    </div>
                    @endif
                    @if($student->student_code)
                    <div class="detail-row">
                        <span class="detail-label">Student Code:</span>
                        <span class="detail-value">{{ $student->student_code }}</span>
                    </div>
                    @endif
                    @if($student->class)
                    <div class="detail-row">
                        <span class="detail-label">Class:</span>
                        <span class="detail-value">{{ $student->class }}</span>
                    </div>
                    @endif
                    @if($student->section)
                    <div class="detail-row">
                        <span class="detail-label">Section:</span>
                        <span class="detail-value">{{ $student->section }}</span>
                    </div>
                    @endif
                    @if($student->campus)
                    <div class="detail-row">
                        <span class="detail-label">Campus:</span>
                        <span class="detail-value">{{ $student->campus }}</span>
                    </div>
                    @endif
                </div>

                @if($student->date_of_birth)
                <div class="birth-info">
                    <p style="font-size: 18px; margin-bottom: 10px;"><strong>Date of Birth:</strong></p>
                    <div class="birth-date">{{ $student->date_of_birth->format('d F Y') }}</div>
                    <div class="birth-date-text">({{ $student->date_of_birth->format('d/m/Y') }})</div>
                    @if($student->place_of_birth)
                    <p style="margin-top: 20px; font-size: 16px;"><strong>Place of Birth:</strong> {{ $student->place_of_birth }}</p>
                    @endif
                </div>
                @endif

                <p style="margin-top: 30px;">This certificate is issued on the basis of school records and is authentic. This certificate is issued on the request of the student/parent for official purposes.</p>
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

