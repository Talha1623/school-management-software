<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Student Cards</title>
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp
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

            .student-card {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }

        /* Student Card Styling */
        .student-card {
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

        .student-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #003471 0%, #004a9f 50%, #003471 100%);
        }

        .card-header {
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

        .card-title {
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

        .student-photo-section {
            text-align: center;
            margin-bottom: 15px;
        }

        .student-photo {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid #003471;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            background: #f8f9fa;
        }

        .photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #003471;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .photo-placeholder-icon {
            font-size: 50px;
            color: rgba(255, 255, 255, 0.8);
        }

        .card-details {
            position: relative;
            margin-bottom: 15px;
            flex: 1;
            padding-right: 10px;
        }

        .card-details::before {
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
            margin-bottom: 10px;
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

        .card-qr {
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

        .card-footer {
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
        @if($students->isEmpty())
            <div style="text-align: center; padding: 50px; color: #666;">
                <h3>No students found matching the selected filters.</h3>
                <p>Please adjust your filter criteria and try again.</p>
            </div>
        @else
        <div class="cards-grid">
            @foreach($students as $student)
            <div class="student-card">
                <!-- Header -->
                <div class="card-header">
                    <div class="header-content">
                        <div class="school-logo">
                            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="School Logo" class="logo-img" onerror="this.style.display='none'">
                        </div>
                        <div class="school-info">
                            <h6 class="school-name">{{ config('app.name', 'ICMS Management System') }}</h6>
                            <p class="campus-name">{{ $student->campus ?? 'All Campuses' }}</p>
                            @if(config('app.contact'))
                            <p class="contact-info">Contact: {{ config('app.contact') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Title Bar -->
                <div class="card-title">
                    Student ID Card
                </div>
                
                <!-- Student Photo -->
                <div class="student-photo-section">
                    @if($student->photo)
                        <img src="{{ Storage::url($student->photo) }}" alt="Student Photo" class="student-photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                        <div class="photo-placeholder" style="display: none;">
                            <span class="photo-placeholder-icon">👤</span>
                        </div>
                    @else
                        <div class="photo-placeholder">
                            <span class="photo-placeholder-icon">👤</span>
                        </div>
                    @endif
                </div>
                
                <!-- Details -->
                <div class="card-details">
                    <div class="detail-item">
                        <span class="detail-label">Student Name:</span>
                        <span class="detail-value">{{ $student->student_name }}</span>
                    </div>
                    @if($student->student_code)
                    <div class="detail-item">
                        <span class="detail-label">Student Code:</span>
                        <span class="detail-value">{{ $student->student_code }}</span>
                    </div>
                    @endif
                    @if($student->gr_number)
                    <div class="detail-item">
                        <span class="detail-label">GR Number:</span>
                        <span class="detail-value">{{ $student->gr_number }}</span>
                    </div>
                    @endif
                    <div class="detail-item">
                        <span class="detail-label">Class:</span>
                        <span class="detail-value">{{ $student->class ?? 'N/A' }}</span>
                    </div>
                    @if($student->section)
                    <div class="detail-item">
                        <span class="detail-label">Section:</span>
                        <span class="detail-value">{{ $student->section }}</span>
                    </div>
                    @endif
                    <div class="detail-item">
                        <span class="detail-label">Campus:</span>
                        <span class="detail-value">{{ $student->campus ?? 'N/A' }}</span>
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="card-qr">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode('StudentID:' . $student->id . ':' . $student->student_code . ':' . $student->student_name) }}" alt="QR Code" class="qr-code-img">
                </div>
                
                <!-- Footer -->
                <div class="card-footer">
                    This is an official student ID card. Please carry it at all times while on campus.
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <script>
        // Auto print when page loads (only if students exist)
        window.onload = function() {
            @if(!$students->isEmpty())
            window.print();
            @endif
        };
    </script>
</body>
</html>

