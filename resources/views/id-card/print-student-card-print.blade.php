<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Cards - {{ $settings->school_name ?? 'School' }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        /* GRID */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            max-width: 100%;
        }

        /* CARD */
        .student-card {
            background: {{ $designSettings['gradient_color1'] ?? '#FFFFFF' }};
            border-radius: {{ ($designSettings['border_style'] ?? 'rounded') == 'rounded' ? '12px' : (($designSettings['border_style'] ?? 'rounded') == 'square' ? '0px' : '12px') }};
            border: {{ ($designSettings['border_style'] ?? 'rounded') == 'none' ? 'none' : '2px solid ' . ($designSettings['accent_color'] ?? '#003471') }};
            height: 240px;
            padding: 12px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* HEADER */
        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid {{ $designSettings['accent_color'] ?? '#003471' }};
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .school-logo {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid {{ $designSettings['accent_color'] ?? '#003471' }};
            padding: 3px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }

        .school-name {
            flex: 1;
            font-size: 12px;
            font-weight: 700;
            color: {{ $designSettings['accent_color'] ?? '#003471' }};
            line-height: 1.3;
        }

        .school-name .main-name {
            display: block;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .school-name .card-type {
            display: block;
            font-size: 9px;
            color: {{ $designSettings['secondary_color'] ?? '#004a9e' }};
            font-weight: 600;
        }

        /* BODY */
        .card-body {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            height: calc(100% - 80px);
        }

        /* PHOTO */
        .photo-box {
            text-align: center;
            flex-shrink: 0;
        }

        .photo-box img {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            border: 3px solid {{ $designSettings['accent_color'] ?? '#003471' }};
            object-fit: cover;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .photo-placeholder {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background: linear-gradient(135deg, {{ $designSettings['accent_color'] ?? '#003471' }} 0%, {{ $designSettings['secondary_color'] ?? '#004a9e' }} 100%);
            border: 3px solid {{ $designSettings['accent_color'] ?? '#003471' }};
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .photo-placeholder span {
            font-size: 38px;
            color: rgba(255, 255, 255, 0.9);
        }

        .student-name {
            font-size: 11px;
            font-weight: 700;
            margin-top: 6px;
            color: {{ $designSettings['student_name_color'] ?? '#000000' }};
            line-height: 1.2;
            word-wrap: break-word;
            max-width: 75px;
        }

        /* DETAILS */
        .details {
            flex: 1;
            font-size: 9px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .detail {
            margin-bottom: 5px;
            color: {{ $designSettings['details_text_color'] ?? '#333333' }};
            line-height: 1.4;
        }

        .detail span {
            font-weight: 700;
            color: {{ $designSettings['accent_color'] ?? '#003471' }};
            display: inline-block;
            min-width: 50px;
        }

        /* QR */
        .qr {
            text-align: center;
            margin-top: auto;
            padding-top: 5px;
        }

        .qr img {
            width: 60px;
            height: 60px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            padding: 3px;
        }

        /* FOOTER */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, {{ $designSettings['accent_color'] ?? '#003471' }} 0%, {{ $designSettings['secondary_color'] ?? '#004a9e' }} 100%);
            color: {{ $designSettings['footer_text_color'] ?? '#FFFFFF' }};
            font-size: 8px;
            text-align: center;
            padding: 5px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* DESIGN PANEL */
        .design-panel {
            background: white;
            border: 2px solid #003471;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .design-panel-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: flex-start;
        }
        
        .design-panel-section {
            min-width: 180px;
        }

        .design-panel h3 {
            font-size: 16px;
            font-weight: 700;
            color: #003471;
            margin-bottom: 15px;
            border-bottom: 2px solid #003471;
            padding-bottom: 10px;
        }

        .color-group {
            margin-bottom: 10px;
        }

        .color-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .color-group input[type="color"] {
            width: 50px;
            height: 32px;
            border: 2px solid #003471;
            border-radius: 4px;
            cursor: pointer;
            vertical-align: middle;
        }

        .color-group input[type="text"] {
            width: calc(100% - 60px);
            height: 32px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 11px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .design-options {
            margin-top: 10px;
        }

        .design-options > div {
            margin-bottom: 10px;
        }

        .design-options label {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin-right: 8px;
            min-width: 100px;
        }

        .design-options select {
            width: calc(100% - 110px);
            height: 32px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 11px;
        }

        .apply-btn {
            background: linear-gradient(135deg, #003471 0%, #004a9e 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
        }

        .apply-btn:hover {
            background: linear-gradient(135deg, #004a9e 0%, #003471 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        }

        /* PRINT */
        @media print {
            .design-panel {
                display: none !important;
            }
            body {
                background: white;
                padding: 10px;
            }
            .cards-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
            }
            .student-card {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            @page {
                size: A4 {{ ($designSettings['orientation'] ?? 'portrait') == 'landscape' ? 'landscape' : 'portrait' }};
                margin: 10mm;
            }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
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

<!-- Card Design Customization Panel -->
<div class="design-panel no-print">
    <h3>Card Design Customization</h3>
    <div class="design-panel-content">
        <div class="design-panel-section">
            <div class="color-group">
                <label>Accent Color:</label>
                <input type="color" id="accentColor" value="{{ $designSettings['accent_color'] ?? '#003471' }}">
                <input type="text" id="accentColorText" value="{{ $designSettings['accent_color'] ?? '#003471' }}">
            </div>
            <div class="color-group">
                <label>Secondary Color:</label>
                <input type="color" id="secondaryColor" value="{{ $designSettings['secondary_color'] ?? '#004a9e' }}">
                <input type="text" id="secondaryColorText" value="{{ $designSettings['secondary_color'] ?? '#004a9e' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Gradient Color 1:</label>
                <input type="color" id="gradientColor1" value="{{ $designSettings['gradient_color1'] ?? '#FFFFFF' }}">
                <input type="text" id="gradientColor1Text" value="{{ $designSettings['gradient_color1'] ?? '#FFFFFF' }}">
            </div>
            <div class="color-group">
                <label>Gradient Color 2:</label>
                <input type="color" id="gradientColor2" value="{{ $designSettings['gradient_color2'] ?? '#F8F9FA' }}">
                <input type="text" id="gradientColor2Text" value="{{ $designSettings['gradient_color2'] ?? '#F8F9FA' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Student Name Color:</label>
                <input type="color" id="studentNameColor" value="{{ $designSettings['student_name_color'] ?? '#000000' }}">
                <input type="text" id="studentNameColorText" value="{{ $designSettings['student_name_color'] ?? '#000000' }}">
            </div>
            <div class="color-group">
                <label>Details Text Color:</label>
                <input type="color" id="detailsTextColor" value="{{ $designSettings['details_text_color'] ?? '#333333' }}">
                <input type="text" id="detailsTextColorText" value="{{ $designSettings['details_text_color'] ?? '#333333' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Footer Text Color:</label>
                <input type="color" id="footerTextColor" value="{{ $designSettings['footer_text_color'] ?? '#FFFFFF' }}">
                <input type="text" id="footerTextColorText" value="{{ $designSettings['footer_text_color'] ?? '#FFFFFF' }}">
            </div>
            <div class="design-options">
                <div>
                    <label>Orientation:</label>
                    <select id="orientation">
                        <option value="portrait" {{ ($designSettings['orientation'] ?? 'portrait') == 'portrait' ? 'selected' : '' }}>Portrait</option>
                        <option value="landscape" {{ ($designSettings['orientation'] ?? 'portrait') == 'landscape' ? 'selected' : '' }}>Landscape</option>
                    </select>
                </div>
                <div>
                    <label>Card Style:</label>
                    <select id="cardStyle">
                        <option value="modern" {{ ($designSettings['card_style'] ?? 'modern') == 'modern' ? 'selected' : '' }}>Modern</option>
                        <option value="classic" {{ ($designSettings['card_style'] ?? 'modern') == 'classic' ? 'selected' : '' }}>Classic</option>
                        <option value="minimal" {{ ($designSettings['card_style'] ?? 'modern') == 'minimal' ? 'selected' : '' }}>Minimal</option>
                    </select>
                </div>
                <div>
                    <label>Border Style:</label>
                    <select id="borderStyle">
                        <option value="rounded" {{ ($designSettings['border_style'] ?? 'rounded') == 'rounded' ? 'selected' : '' }}>Rounded</option>
                        <option value="square" {{ ($designSettings['border_style'] ?? 'rounded') == 'square' ? 'selected' : '' }}>Square</option>
                        <option value="none" {{ ($designSettings['border_style'] ?? 'rounded') == 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
            </div>
            <button class="apply-btn" onclick="applyDesignSettings()">Apply Design</button>
        </div>
    </div>
</div>

<script>
    // Sync color picker with text input
    document.querySelectorAll('input[type="color"]').forEach(colorInput => {
        const textInput = document.getElementById(colorInput.id + 'Text');
        if (textInput) {
            colorInput.addEventListener('input', function() {
                textInput.value = this.value;
            });
            textInput.addEventListener('input', function() {
                if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                    colorInput.value = this.value;
                }
            });
        }
    });

    function applyDesignSettings() {
        const params = new URLSearchParams(window.location.search);
        
        // Get all color values
        params.set('accent_color', document.getElementById('accentColor').value);
        params.set('secondary_color', document.getElementById('secondaryColor').value);
        params.set('gradient_color1', document.getElementById('gradientColor1').value);
        params.set('gradient_color2', document.getElementById('gradientColor2').value);
        params.set('student_name_color', document.getElementById('studentNameColor').value);
        params.set('details_text_color', document.getElementById('detailsTextColor').value);
        params.set('footer_text_color', document.getElementById('footerTextColor').value);
        
        // Get design options
        params.set('orientation', document.getElementById('orientation').value);
        params.set('card_style', document.getElementById('cardStyle').value);
        params.set('border_style', document.getElementById('borderStyle').value);
        
        // Reload page with new parameters
        window.location.search = params.toString();
    }
</script>

<div class="cards-grid">

@foreach($students as $student)
<div class="student-card">

    <!-- HEADER -->
    <div class="card-header">
        <div class="school-logo">
            @if($settings->logo)
                <img src="{{ Storage::url($settings->logo) }}" alt="School Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display: none; width: 100%; height: 100%; background: {{ $designSettings['accent_color'] ?? '#003471' }}; border-radius: 50%; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                    {{ strtoupper(substr($settings->school_name ?? 'SCH', 0, 3)) }}
                </div>
            @else
                <div style="width: 100%; height: 100%; background: {{ $designSettings['accent_color'] ?? '#003471' }}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                    {{ strtoupper(substr($settings->school_name ?? 'SCH', 0, 3)) }}
                </div>
            @endif
        </div>
        <div class="school-name">
            <span class="main-name">{{ $settings->school_name ?? 'School Name' }}</span>
            <span class="card-type">Student ID Card</span>
        </div>
    </div>

    <!-- BODY -->
    <div class="card-body">

        <!-- PHOTO -->
        <div class="photo-box">
            @if($student->photo)
                <img src="{{ Storage::url($student->photo) }}" alt="Student Photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="photo-placeholder" style="display: none;">
                    <span>{{ strtoupper(substr($student->student_name ?? 'S', 0, 1)) }}</span>
                </div>
            @else
                <div class="photo-placeholder">
                    <span>{{ strtoupper(substr($student->student_name ?? 'S', 0, 1)) }}</span>
                </div>
            @endif
            <div class="student-name">{{ $student->student_name }}</div>
        </div>

        <!-- DETAILS -->
        <div class="details">
            <div>
                <div class="detail"><span>ID:</span> {{ $student->student_code ?? 'N/A' }}</div>
                <div class="detail"><span>Class:</span> {{ $student->class ?? 'N/A' }}</div>
                <div class="detail"><span>Section:</span> {{ $student->section ?? 'N/A' }}</div>
                <div class="detail"><span>Campus:</span> {{ $student->campus ?? 'N/A' }}</div>
            </div>

            <div class="qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ $student->student_code ?? $student->id }}" alt="QR Code">
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="footer">
        @if($settings->school_name)
            {{ $settings->school_name }} • Official Student ID Card
        @else
            Official Student ID Card • Valid for Academic Use
        @endif
    </div>

</div>
@endforeach

</div>

</body>
</html>
