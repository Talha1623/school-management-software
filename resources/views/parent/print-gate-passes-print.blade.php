<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Passes - {{ $settings->school_name ?? 'School' }}</title>
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

        :root {
            --accent-color: {{ $designSettings['accent_color'] ?? '#003471' }};
            --secondary-color: {{ $designSettings['secondary_color'] ?? '#004a9e' }};
            --gradient-color1: {{ $designSettings['gradient_color1'] ?? '#FFFFFF' }};
            --gradient-color2: {{ $designSettings['gradient_color2'] ?? '#F8F9FA' }};
            --parent-name-color: {{ $designSettings['parent_name_color'] ?? '#1f2a44' }};
            --details-text-color: {{ $designSettings['details_text_color'] ?? '#333333' }};
            --footer-text-color: {{ $designSettings['footer_text_color'] ?? '#FFFFFF' }};
        }

        .cards-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 18px;
            max-width: 100%;
        }

        .gate-pass-card {
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--accent-color) 8%, transparent) 0%, rgba(255, 255, 255, 0) 36%),
                linear-gradient(180deg, var(--gradient-color1) 0%, var(--gradient-color2) 100%);
            border-radius: 16px;
            border: 1.5px solid var(--accent-color);
            width: 370px;
            height: 240px;
            padding: 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.16);
            transition: transform 0.2s ease;
        }

        .gate-pass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            height: 58px;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 8px 13px;
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--secondary-color) 100%);
            color: #fff;
        }

        .school-logo {
            width: 39px;
            height: 39px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid rgba(255,255,255,0.75);
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
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .school-name .main-name {
            display: block;
            font-size: 13px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .school-name .card-type {
            display: block;
            font-size: 9px;
            color: rgba(255,255,255,0.86);
            font-weight: 600;
            letter-spacing: 1.8px;
        }

        .card-body {
            display: flex;
            gap: 11px;
            margin-top: 0;
            height: calc(100% - 92px);
            padding: 12px 86px 20px 12px;
        }

        .photo-box {
            text-align: center;
            flex-shrink: 0;
            width: 94px;
            margin-left: 0;
        }

        .photo-placeholder {
            width: 82px;
            height: 92px;
            border-radius: 12px;
            background: var(--accent-color);
            border: 3px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .photo-placeholder span {
            font-size: 40px;
            color: #fff;
        }

        .edu-box {
            margin-top: 6px;
            display: flex;
            gap: 5px;
            align-items: center;
            justify-content: center;
        }

        .edu-logo {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid var(--accent-color);
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .edu-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .edu-logo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            color: #fff;
            font-size: 8px;
            background: var(--accent-color);
        }

        .edu-text {
            text-align: center;
            font-size: 6.5px;
            font-weight: 900;
            line-height: 1.1;
            color: var(--accent-color);
        }

        .details {
            flex: 1;
            min-width: 0;
            font-size: 9px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding-top: 0;
        }

        .parent-role {
            font-size: 10px;
            font-weight: 900;
            color: var(--accent-color);
            letter-spacing: 1.2px;
            margin-bottom: 3px;
        }

        .parent-fullname {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.3px;
            color: var(--parent-name-color);
            line-height: 1.1;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .detail-row {
            margin: 1px 0;
            color: var(--details-text-color);
            line-height: 1.15;
            font-size: 9.5px;
        }

        .detail-row span {
            font-weight: 900;
            color: var(--accent-color);
            display: inline-block;
            min-width: 68px;
        }

        .card-bottom-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 2px;
            margin-bottom: 14px;
        }

        .qr-block {
            position: absolute;
            right: 12px;
            bottom: 31px;
            z-index: 3;
            background: #fff;
            border: 2px solid #fff;
            border-radius: 10px;
            padding: 5px;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.2);
        }

        .qr-block img {
            display: block;
            width: 62px;
            height: 62px;
            object-fit: contain;
            image-rendering: auto;
        }

        .id-block {
            text-align: left;
            font-size: 10px;
            font-weight: 900;
            color: #1f2937;
            line-height: 1.25;
            min-width: 55px;
        }

        .id-block span {
            display: block;
            font-size: 7px;
            letter-spacing: .9px;
            color: var(--accent-color);
        }

        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--accent-color);
            color: var(--footer-text-color);
            font-size: 8px;
            text-align: center;
            padding: 5px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .gate-pass-card::before {
            content: '';
            display: block !important;
            position: absolute;
            right: -44px;
            top: 48px;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--accent-color) 8%, transparent);
            z-index: 0;
        }

        .gate-pass-card > * {
            position: relative;
            z-index: 1;
        }

        .accent-bg {
            background: var(--accent-color) !important;
        }

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

        .design-panel-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .design-panel-actions .apply-btn {
            margin-top: 0;
        }

        .print-btn {
            background: #fff;
            color: #003471;
            border: 2px solid #003471;
            border-radius: 6px;
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 52, 113, 0.1);
        }

        .print-btn:hover {
            background: #003471;
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 52, 113, 0.25);
        }

        @media print {
            .no-print,
            .design-panel {
                display: none !important;
            }
            body {
                background: white;
                padding: 10px;
            }
            .cards-grid {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }
            .gate-pass-card {
                page-break-inside: avoid;
                break-inside: avoid;
                box-shadow: none;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>

<body>
@php
    $displaySchoolName = trim((string) ($settings->school_name ?? ''))
        ?: trim((string) ($settings->system_name ?? ''))
        ?: 'Education Management System';
    $schoolInitials = strtoupper(substr($displaySchoolName, 0, 3));
    $schoolLogoUrl = !empty($settings->logo)
        ? (str_starts_with((string) $settings->logo, 'http')
            ? (string) $settings->logo
            : asset('storage/' . ltrim((string) $settings->logo, '/')))
        : asset('assets/images/logo-icon.png');
@endphp

<div class="design-panel no-print">
    <h3>Gate Pass Design Customization</h3>
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
                <label>Parent Name Color:</label>
                <input type="color" id="parentNameColor" value="{{ $designSettings['parent_name_color'] ?? '#1f2a44' }}">
                <input type="text" id="parentNameColorText" value="{{ $designSettings['parent_name_color'] ?? '#1f2a44' }}">
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
            <div class="design-panel-actions">
                <button type="button" class="apply-btn" onclick="applyDesignSettings()">Apply Design</button>
                <button type="button" class="print-btn" onclick="printGatePasses()">Print Gate Passes</button>
            </div>
        </div>
    </div>
</div>

<script>
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
        const accent = document.getElementById('accentColor').value;
        const secondary = document.getElementById('secondaryColor').value;
        const gradient1 = document.getElementById('gradientColor1').value;
        const gradient2 = document.getElementById('gradientColor2').value;
        const parentName = document.getElementById('parentNameColor').value;
        const detailsText = document.getElementById('detailsTextColor').value;
        const footerText = document.getElementById('footerTextColor').value;

        const root = document.documentElement;
        root.style.setProperty('--accent-color', accent);
        root.style.setProperty('--secondary-color', secondary);
        root.style.setProperty('--gradient-color1', gradient1);
        root.style.setProperty('--gradient-color2', gradient2);
        root.style.setProperty('--parent-name-color', parentName);
        root.style.setProperty('--details-text-color', detailsText);
        root.style.setProperty('--footer-text-color', footerText);

        const params = new URLSearchParams(window.location.search);
        params.set('accent_color', accent);
        params.set('secondary_color', secondary);
        params.set('gradient_color1', gradient1);
        params.set('gradient_color2', gradient2);
        params.set('parent_name_color', parentName);
        params.set('details_text_color', detailsText);
        params.set('footer_text_color', footerText);
        window.history.replaceState({}, '', '?' + params.toString());
    }

    function printGatePasses() {
        window.print();
    }
</script>

<div class="cards-grid" id="gatePassCards">

@foreach($parents as $parent)
@php
    $qrTarget = implode('|', array_filter([
        'GatePass:' . ($parent['id'] ?? ''),
        'Name:' . ($parent['name'] ?? 'N/A'),
        'Campus:' . ($parent['campus'] ?? 'N/A'),
        'Type:' . ($parent['parent_type'] ?? 'N/A'),
        'Issue:' . ($parent['issue_date'] ?? 'N/A'),
        'Valid:' . ($parent['pass_validity'] ?? 'N/A'),
    ]));
    $qrPayload = urlencode($qrTarget);
    $passId = 'GP-' . str_pad((string) ($parent['id'] ?? '0'), 4, '0', STR_PAD_LEFT);
@endphp
<div class="gate-pass-card">

    <div class="card-header">
        <div class="school-logo">
            @if($schoolLogoUrl)
                <img src="{{ $schoolLogoUrl }}" alt="School Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="accent-bg" style="display: none; width: 100%; height: 100%; border-radius: 50%; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                    {{ $schoolInitials }}
                </div>
            @else
                <div class="accent-bg" style="width: 100%; height: 100%; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;">
                    {{ $schoolInitials }}
                </div>
            @endif
        </div>
        <div class="school-name">
            <span class="main-name">{{ $displaySchoolName }}</span>
            <span class="card-type">Gate Pass</span>
        </div>
    </div>

    <div class="card-body">
        <div class="photo-box">
            <div class="photo-placeholder">
                <span>{{ strtoupper(substr($parent['name'] ?? 'P', 0, 1)) }}</span>
            </div>
            <div class="edu-box">
                <div class="edu-logo">
                    @if($schoolLogoUrl)
                        <img src="{{ $schoolLogoUrl }}" alt="Education Logo"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="edu-logo-placeholder" style="display: none;">
                            {{ $schoolInitials }}
                        </div>
                    @else
                        <div class="edu-logo-placeholder">
                            {{ $schoolInitials }}
                        </div>
                    @endif
                </div>
                <div class="edu-text">
                    {{ strtoupper(substr($displaySchoolName, 0, 18)) }}<br>
                    GATE PASS
                </div>
            </div>
        </div>

        <div class="details">
            <div>
                <div class="parent-role">PARENT</div>
                <div class="parent-fullname">{{ strtoupper($parent['name'] ?? 'N/A') }}</div>
                <div class="detail-row"><span>CAMPUS:</span> {{ $parent['campus'] ?? 'N/A' }}</div>
                <div class="detail-row"><span>TYPE:</span> {{ $parent['parent_type'] ?? 'N/A' }}</div>
                <div class="detail-row"><span>ISSUE:</span> {{ $parent['issue_date'] ?? 'N/A' }}</div>
                <div class="detail-row"><span>VALID:</span> {{ $parent['pass_validity'] ?? 'N/A' }}</div>
            </div>

            <div class="card-bottom-row">
                <div class="id-block">
                    <span>GATE PASS ID</span>
                    {{ $passId }}
                </div>
                <div class="qr-block">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=14&ecc=H&data={{ $qrPayload }}"
                        alt="Gate Pass QR Code"
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        {{ $displaySchoolName }} • Official Gate Pass
    </div>

</div>
@endforeach

</div>

</body>
</html>
