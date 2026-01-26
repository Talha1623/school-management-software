<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provisional Certificate - {{ $student->student_name }}</title>
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
            width: 180px;
        }

        .detail-value {
            color: #003471;
            font-weight: 600;
        }

        .certificate-footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 2px solid #003471;
            margin-bottom: 8px;
        }

        .signature-title {
            font-size: 14px;
            font-weight: bold;
            color: #003471;
        }

        .certificate-date {
            text-align: right;
            font-size: 14px;
            color: #555;
        }

        .print-button {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            background: #003471;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <button class="print-button no-print" onclick="window.print()">Print Certificate</button>
        <div class="certificate-border">
            <div class="certificate-header">
                <div class="school-logo">
                    @if(config('app.logo'))
                        <img src="{{ asset(config('app.logo')) }}" alt="School Logo" class="logo-img">
                    @else
                        <div class="logo-img" style="background: #003471; border-radius: 50%; display: inline-block;"></div>
                    @endif
                </div>
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-address">{{ $schoolAddress }}</div>
            </div>

            <h1 class="certificate-title">Provisional Certificate</h1>

            <div class="certificate-body">
                This is to certify that the following student has been enrolled in our institution. This provisional
                certificate is issued for temporary record purposes until formal documentation is completed.
            </div>

            <div class="student-details">
                <div class="detail-row">
                    <span class="detail-label">Student Name:</span>
                    <span class="detail-value">{{ $student->student_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Father Name:</span>
                    <span class="detail-value">{{ $student->father_name ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Student Code:</span>
                    <span class="detail-value">{{ $student->student_code ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Class / Section:</span>
                    <span class="detail-value">{{ $student->class ?? 'N/A' }} {{ $student->section ? ' - ' . $student->section : '' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value">{{ $student->date_of_birth ? $student->date_of_birth->format('d F Y') : 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Admission Date:</span>
                    <span class="detail-value">{{ $student->admission_date ? $student->admission_date->format('d F Y') : 'N/A' }}</span>
                </div>
            </div>

            <div class="certificate-footer">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">Principal</div>
                </div>
                <div class="certificate-date">
                    Date: {{ $currentDate }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
