<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Character Certificate - {{ $student->student_name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Montserrat:wght@300;400;500;600;700&family=Open+Sans:wght@400;600&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8edff 100%);
            padding: 30px 20px;
            min-height: 100vh;
            color: #333;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .certificate-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                border: none;
            }
            
            .certificate-border {
                border: none;
            }
            
            @page {
                size: A4 landscape;
                margin: 0;
            }
        }

        .certificate-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            position: relative;
            box-shadow: 0 15px 50px rgba(0, 52, 113, 0.15);
            border-radius: 8px;
            overflow: hidden;
        }

        /* Decorative Border Pattern */
        .certificate-border {
            border: 20px solid transparent;
            padding: 40px 50px;
            position: relative;
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(135deg, #003471 0%, #0056b3 50%, #003471 100%) border-box;
            min-height: 100%;
        }

        /* Corner Decorations */
        .corner-decoration {
            position: absolute;
            width: 50px;
            height: 50px;
            z-index: 1;
        }
         
        .corner-top-left {
            top: 0;
            left: 0;
            border-top: 4px solid #ffd700;
            border-left: 4px solid #ffd700;
        }

        .corner-top-right {
            top: 0;
            right: 0;
            border-top: 4px solid #ffd700;
            border-right: 4px solid #ffd700;
        }

        .corner-bottom-left {
            bottom: 0;
            left: 0;
            border-bottom: 4px solid #ffd700;
            border-left: 4px solid #ffd700;
        }

        .corner-bottom-right {
            bottom: 0;
            right: 0;
            border-bottom: 4px solid #ffd700;
            border-right: 4px solid #ffd700;
        }

        /* Header Section */
        .certificate-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 20px;
        }

        .certificate-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #003471, transparent);
        }

        .school-logo {
            margin-bottom: 15px;
        }

        .logo-img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .school-name {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            color: #003471;
            margin-bottom: 8px;
            letter-spacing: 1.5px;
        }

        .school-address {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .school-address i {
            color: #003471;
            font-size: 12px;
        }

        .school-contact {
            font-size: 13px;
            color: #666;
            font-weight: 400;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .contact-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .contact-item i {
            color: #003471;
            font-size: 12px;
        }

        .contact-separator {
            color: #ccc;
            font-weight: 300;
        }

        /* Certificate Title */
        .certificate-title {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-weight: 700;
            color: #003471;
            margin: 40px 0 50px;
            position: relative;
            padding-bottom: 25px;
        }

        .certificate-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 4px;
            background: linear-gradient(90deg, #003471, #ffd700, #003471);
            border-radius: 2px;
        }

        /* Body Content */
        .certificate-body {
            font-size: 17px;
            line-height: 1.7;
            text-align: justify;
            margin: 40px 0;
            color: #333;
            position: relative;
            z-index: 1;
        }

        .certificate-body p {
            margin-bottom: 20px;
        }

        .certificate-body strong {
            color: #003471;
            font-weight: 600;
        }

        /* Student Details Box */
        .student-details {
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f8faff 0%, #f0f5ff 100%);
            border: 1px solid #d1ddff;
            border-left: 5px solid #003471;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 52, 113, 0.08);
        }

        .student-details-title {
            font-size: 20px;
            color: #003471;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-details-title i {
            color: #ffd700;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px 40px;
        }

        .detail-row {
            display: flex;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px dashed #d1ddff;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            min-width: 180px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-label i {
            color: #003471;
            font-size: 14px;
        }

        .detail-value {
            color: #003471;
            font-weight: 600;
            flex: 1;
            font-size: 16px;
        }

        /* Footer Section */
        .certificate-footer {
            margin-top: 70px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            position: relative;
            z-index: 1;
        }

        .signature-section {
            text-align: center;
            width: 40%;
        }

        .signature-box {
            display: inline-block;
            text-align: center;
        }

        .signature-line {
            border-top: 2px solid #003471;
            width: 250px;
            margin: 0 auto 15px;
            position: relative;
            height: 1px;
        }

        .signature-name {
            font-weight: 700;
            font-size: 18px;
            color: #003471;
            margin-bottom: 5px;
            margin-top: 60px;
        }

        .signature-title {
            font-size: 14px;
            color: #666;
            font-style: italic;
        }

        .date-section {
            text-align: right;
            font-size: 16px;
            width: 40%;
        }

        .date-section p {
            margin-bottom: 10px;
            color: #333;
        }

        .date-section strong {
            color: #003471;
            font-weight: 600;
        }

        .certificate-number {
            background: #003471;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            margin-top: 15px;
            display: inline-block;
        }

        /* Official Seal Area */
        .seal-area {
            position: absolute;
            top: 50%;
            right: 50px;
            transform: translateY(-50%);
            width: 140px;
            height: 140px;
            border: 3px solid #003471;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.95);
            z-index: 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .seal-inner {
            width: 120px;
            height: 120px;
            border: 2px dashed #003471;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .seal-text {
            font-size: 12px;
            color: #003471;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .seal-text div:first-child {
            font-size: 14px;
            margin-bottom: 3px;
        }

        /* Print Button */
        .print-btn {
            position: fixed;
            top: 30px;
            right: 30px;
            background: linear-gradient(135deg, #003471 0%, #0056b3 100%);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 5px 20px rgba(0, 52, 113, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Montserrat', sans-serif;
        }

        .print-btn:hover {
            background: linear-gradient(135deg, #0056b3 0%, #003471 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 52, 113, 0.4);
        }

        .print-btn:active {
            transform: translateY(0);
        }

        /* Background Pattern */
        .bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.03;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23003471' fill-opacity='1' fill-rule='evenodd'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .certificate-border {
                padding: 30px 25px;
            }
            
            .school-name {
                font-size: 28px;
            }
            
            .certificate-title {
                font-size: 32px;
                margin: 30px 0 40px;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .seal-area {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .certificate-footer {
                flex-direction: column;
                gap: 40px;
                align-items: center;
            }
            
            .signature-section,
            .date-section {
                width: 100%;
                text-align: center;
            }
            
            .date-section {
                order: -1;
            }
            
            .print-btn {
                padding: 12px 20px;
                font-size: 14px;
                top: 20px;
                right: 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px 10px;
            }
            
            .certificate-border {
                padding: 20px 15px;
            }
            
            .school-name {
                font-size: 24px;
            }
            
            .certificate-title {
                font-size: 28px;
                margin: 20px 0 30px;
            }
            
            .student-details {
                padding: 20px 15px;
            }
            
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .detail-label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-btn no-print">
        <i class="fas fa-print"></i>
        <span>Print Certificate</span>
    </button>

    <div class="certificate-container">
        <div class="certificate-border">
            <!-- Background Pattern -->
            <div class="bg-pattern"></div>
            
            <!-- Corner Decorations -->
            <div class="corner-decoration corner-top-left"></div>
            <div class="corner-decoration corner-top-right"></div>
            <div class="corner-decoration corner-bottom-left"></div>
            <div class="corner-decoration corner-bottom-right"></div>

            <!-- Official Seal -->
            <div class="seal-area">
                <div class="seal-inner">
                    <div class="seal-text">
                        <div>OFFICIAL</div>
                        <div>SEAL</div>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="certificate-header">
                <div class="school-logo">
                    @if($schoolLogo)
                        <img src="{{ $schoolLogo }}" alt="School Logo" class="logo-img" onerror="this.onerror=null; this.src='{{ asset('assets/images/logo-icon.png') }}';">
                    @else
                        <img src="{{ asset('assets/images/logo-icon.png') }}" alt="School Logo" class="logo-img">
                    @endif
                </div>
                <h1 class="school-name">{{ $schoolName }}</h1>
                @if($schoolAddress)
                <p class="school-address">
                    <i class="fas fa-map-marker-alt"></i> {{ $schoolAddress }}
                </p>
                @endif
                @if($schoolPhone || $schoolEmail)
                <div class="school-contact">
                    @if($schoolPhone)
                    <span class="contact-item">
                        <i class="fas fa-phone"></i> {{ $schoolPhone }}
                    </span>
                    @endif
                    @if($schoolPhone && $schoolEmail)
                    <span class="contact-separator">|</span>
                    @endif
                    @if($schoolEmail)
                    <span class="contact-item">
                        <i class="fas fa-envelope"></i> {{ $schoolEmail }}
                    </span>
                    @endif
                </div>
                @endif
            </div>

            <!-- Title -->
            <h2 class="certificate-title">Character Certificate</h2>

            <!-- Body -->
            <div class="certificate-body">
                <p style="margin-bottom: 25px; text-align: center;">
                    <i>This certificate confirms the good character and conduct of the following student during their period of study at this institution</i>
                </p>

                <div class="student-details">
                    <h3 class="student-details-title">
                        <i class="fas fa-user-graduate"></i>
                        Student Information
                    </h3>
                    <div class="details-grid">
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-user"></i>Student Name:
                            </span>
                            <span class="detail-value">{{ $student->student_name }}</span>
                        </div>
                        
                        @if($student->student_code)
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-id-card"></i>Student Code:
                            </span>
                            <span class="detail-value">{{ $student->student_code }}</span>
                        </div>
                        @endif
                        
                        @if($student->class)
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-school"></i>Class:
                            </span>
                            <span class="detail-value">{{ $student->class }}</span>
                        </div>
                        @endif
                        
                        @if($student->section)
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-users"></i>Section:
                            </span>
                            <span class="detail-value">{{ $student->section }}</span>
                        </div>
                        @endif
                        
                        @if($student->campus)
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-building"></i>Campus:
                            </span>
                            <span class="detail-value">{{ $student->campus }}</span>
                        </div>
                        @endif
                        
                        @if($student->date_of_birth)
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-birthday-cake"></i>Date of Birth:
                            </span>
                            <span class="detail-value">{{ $student->date_of_birth->format('d F Y') }}</span>
                        </div>
                        @endif
                        
                        @if($student->admission_date)
                        <div class="detail-row">
                            <span class="detail-label">
                                <i class="fas fa-calendar-check"></i>Admission Date:
                            </span>
                            <span class="detail-value">{{ $student->admission_date->format('d F Y') }}</span>
                        </div>
                        @endif
                        
                        @if($student->father_name || $student->mother_name)
                        <div class="detail-row" style="grid-column: 1 / -1;">
                            <span class="detail-label">
                                <i class="fas fa-users"></i>Parent/Guardian:
                            </span>
                            <span class="detail-value">
                                @if($student->father_name)
                                    Father: {{ $student->father_name }}
                                @endif
                                @if($student->mother_name)
                                    @if($student->father_name)
                                        <br>
                                    @endif
                                    Mother: {{ $student->mother_name }}
                                @endif
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <p style="margin-top: 30px; margin-bottom: 20px; text-align: center;">
                    During the period of study at this institution, the student named above has shown <strong>good conduct and character</strong>. He/She has been <strong>regular in attendance</strong> and has shown <strong>satisfactory progress</strong> in studies.
                </p>

                <p style="margin-top: 20px; text-align: center; font-style: italic;">
                    This certificate is issued on request and we wish the student success in all future endeavors.
                </p>
            </div>

            <!-- Footer -->
            <div class="certificate-footer">
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line"></div>
                        <div class="signature-name">Principal's Signature</div>
                        <div class="signature-title">{{ $schoolName }}</div>
                    </div>
                </div>
                <div class="date-section">
                    <p><strong>Date of Issue:</strong> {{ $currentDate }}</p>
                    <div class="certificate-number">
                        Certificate No: {{ strtoupper(substr(md5($student->id . $student->student_code . $currentDate), 0, 10)) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation to print button
            const printBtn = document.querySelector('.print-btn');
            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Printing...</span>';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-print"></i><span>Print Certificate</span>';
                    }, 1500);
                });
            }
            
            // Auto print option (uncomment if needed)
            // setTimeout(() => window.print(), 1000);
        });
    </script>
</body>
</html>
