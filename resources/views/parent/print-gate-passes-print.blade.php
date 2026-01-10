<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Gate Passes</title>

    <style>
        *{
            box-sizing:border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body{
            background:#f2f2f2;
            padding:20px;
        }

        /* GRID */
        .cards-grid{
            display:grid;
            grid-template-columns: repeat(4, 1fr);
            gap:15px;
        }

        /* CARD */
        .gate-pass-card{
            background:{{ $designSettings['gradient_color1'] ?? '#FFFFFF' }};
            border-radius:{{ ($designSettings['border_style'] ?? 'rounded') == 'rounded' ? '12px' : (($designSettings['border_style'] ?? 'rounded') == 'square' ? '0px' : '12px') }};
            border:{{ ($designSettings['border_style'] ?? 'rounded') == 'none' ? 'none' : '2px solid ' . ($designSettings['accent_color'] ?? '#003471') }};
            height:220px;
            padding:12px;
            position:relative;
            overflow:hidden;
        }

        /* HEADER - Colors on top */
        .card-header{
            display:flex;
            align-items:center;
            gap:8px;
            background:linear-gradient(135deg, {{ $designSettings['accent_color'] ?? '#003471' }} 0%, {{ $designSettings['secondary_color'] ?? '#F08080' }} 100%);
            padding:8px 10px;
            margin:-12px -12px 8px -12px;
            border-radius:{{ ($designSettings['border_style'] ?? 'rounded') == 'rounded' ? '10px 10px 0 0' : '0' }};
        }

        .school-logo{
            width:40px;
            height:40px;
            border-radius:50%;
            background:#fff;
            border:2px solid rgba(255,255,255,0.8);
            padding:3px;
        }

        .school-logo img{
            width:100%;
            height:100%;
            object-fit:contain;
        }

        .school-name{
            font-size:13px;
            font-weight:700;
            color:{{ $designSettings['footer_text_color'] ?? '#FFFFFF' }};
            line-height:1.2;
        }

        /* BODY */
        .card-body{
            display:flex;
            gap:10px;
            margin-top:10px;
        }

        /* DETAILS */
        .details{
            flex:1;
            font-size:10px;
        }

        .detail{
            margin-bottom:4px;
            color:{{ $designSettings['details_text_color'] ?? '#000000' }};
        }

        .detail span{
            font-weight:700;
            color:{{ $designSettings['accent_color'] ?? '#003471' }};
        }

        /* Parent Name */
        .parent-name{
            font-size:12px;
            font-weight:700;
            margin-bottom:8px;
            color:{{ $designSettings['parent_name_color'] ?? '#000000' }};
            text-align:center;
            padding:6px;
            background:rgba(0,52,113,0.05);
            border-radius:6px;
        }

        /* QR */
        .qr{
            text-align:center;
            margin-top:6px;
        }

        .qr img{
            width:55px;
            height:55px;
        }

        /* FOOTER */
        .footer{
            position:absolute;
            bottom:0;
            left:0;
            right:0;
            background:{{ $designSettings['accent_color'] ?? '#003471' }};
            color:{{ $designSettings['footer_text_color'] ?? '#FFFFFF' }};
            font-size:8px;
            text-align:center;
            padding:4px;
        }

        /* DESIGN PANEL */
        .design-panel {
            background: white;
            border: 2px solid #003471;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .design-panel-content {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 10px;
            align-items: flex-start;
        }
        
        .design-panel-section {
            grid-column: span 1;
        }

        .design-panel h3 {
            font-size: 14px;
            font-weight: 700;
            color: #003471;
            margin-bottom: 12px;
            border-bottom: 2px solid #003471;
            padding-bottom: 8px;
        }

        .color-group {
            margin-bottom: 8px;
        }

        .color-group label {
            display: block;
            font-size: 10px;
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }

        .color-group input[type="color"] {
            width: 40px;
            height: 28px;
            border: 2px solid #003471;
            border-radius: 4px;
            cursor: pointer;
            vertical-align: middle;
        }

        .color-group input[type="text"] {
            width: calc(100% - 50px);
            height: 28px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 3px 6px;
            font-size: 10px;
            margin-left: 6px;
            vertical-align: middle;
        }

        .design-options {
            margin-top: 0;
        }

        .design-options > div {
            margin-bottom: 8px;
        }

        .design-options label {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            color: #333;
            margin-right: 6px;
            min-width: 90px;
        }

        .design-options select {
            width: calc(100% - 100px);
            height: 28px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 3px 6px;
            font-size: 10px;
        }

        .apply-btn {
            background: #003471;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .apply-btn:hover {
            background: #004a9a;
        }

        /* PRINT */
        @media print{
            .design-panel {
                display: none !important;
            }
            body{
                background:white;
            }
            .cards-grid{
                grid-template-columns: repeat(4,1fr);
                gap:8px;
            }
            @page{
                size:A4 {{ ($designSettings['orientation'] ?? 'landscape') == 'landscape' ? 'landscape' : 'portrait' }};
                margin:10mm;
            }
        }
    </style>
</head>

<body>

<!-- Card Design Customization Panel -->
<div class="design-panel">
    <h3>Gate Pass Design Customization</h3>
    <div class="design-panel-content">
        <div class="design-panel-section">
            <div class="color-group">
                <label>Accent Color:</label>
                <input type="color" id="accentColor" value="{{ $designSettings['accent_color'] ?? '#003471' }}">
                <input type="text" id="accentColorText" value="{{ $designSettings['accent_color'] ?? '#003471' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Secondary Color:</label>
                <input type="color" id="secondaryColor" value="{{ $designSettings['secondary_color'] ?? '#F08080' }}">
                <input type="text" id="secondaryColorText" value="{{ $designSettings['secondary_color'] ?? '#F08080' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Gradient Color 1:</label>
                <input type="color" id="gradientColor1" value="{{ $designSettings['gradient_color1'] ?? '#FFFFFF' }}">
                <input type="text" id="gradientColor1Text" value="{{ $designSettings['gradient_color1'] ?? '#FFFFFF' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Gradient Color 2:</label>
                <input type="color" id="gradientColor2" value="{{ $designSettings['gradient_color2'] ?? '#F8F9FA' }}">
                <input type="text" id="gradientColor2Text" value="{{ $designSettings['gradient_color2'] ?? '#F8F9FA' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Parent Name Color:</label>
                <input type="color" id="parentNameColor" value="{{ $designSettings['parent_name_color'] ?? '#000000' }}">
                <input type="text" id="parentNameColorText" value="{{ $designSettings['parent_name_color'] ?? '#000000' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Details Text Color:</label>
                <input type="color" id="detailsTextColor" value="{{ $designSettings['details_text_color'] ?? '#000000' }}">
                <input type="text" id="detailsTextColorText" value="{{ $designSettings['details_text_color'] ?? '#000000' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="color-group">
                <label>Footer Text Color:</label>
                <input type="color" id="footerTextColor" value="{{ $designSettings['footer_text_color'] ?? '#FFFFFF' }}">
                <input type="text" id="footerTextColorText" value="{{ $designSettings['footer_text_color'] ?? '#FFFFFF' }}">
            </div>
        </div>
        
        <div class="design-panel-section">
            <div class="design-options">
                <div>
                    <label>Orientation:</label>
                    <select id="orientation">
                        <option value="landscape" {{ ($designSettings['orientation'] ?? 'landscape') == 'landscape' ? 'selected' : '' }}>Landscape</option>
                        <option value="portrait" {{ ($designSettings['orientation'] ?? 'landscape') == 'portrait' ? 'selected' : '' }}>Portrait</option>
                    </select>
                </div>
                <div>
                    <label>Show Monogram:</label>
                    <select id="showMonogram">
                        <option value="yes" {{ ($designSettings['show_monogram'] ?? 'yes') == 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ ($designSettings['show_monogram'] ?? 'yes') == 'no' ? 'selected' : '' }}>No</option>
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
        params.set('parent_name_color', document.getElementById('parentNameColor').value);
        params.set('details_text_color', document.getElementById('detailsTextColor').value);
        params.set('footer_text_color', document.getElementById('footerTextColor').value);
        
        // Get design options
        params.set('orientation', document.getElementById('orientation').value);
        params.set('show_monogram', document.getElementById('showMonogram').value);
        params.set('card_style', document.getElementById('cardStyle').value);
        params.set('border_style', document.getElementById('borderStyle').value);
        
        // Reload page with new parameters
        window.location.search = params.toString();
    }
</script>

<div class="cards-grid">

@foreach($parents as $parent)
<div class="gate-pass-card">

    <!-- HEADER - Colors on top -->
    <div class="card-header">
        <div class="school-logo">
            <img src="{{ asset('assets/images/Full Logo_SMS.png') }}" alt="School Logo" onerror="this.src='{{ asset('assets/images/logo-icon.png') }}'">
        </div>
        <div class="school-name">
            {{ config('app.name','School Name') }}<br>
            Gate Pass
        </div>
    </div>

    <!-- BODY -->
    <div class="card-body">

        <!-- PARENT NAME -->
        <div class="parent-name">{{ $parent['name'] }}</div>

        <!-- DETAILS -->
        <div class="details">
            <div class="detail"><span>Campus:</span> {{ $parent['campus'] }}</div>
            <div class="detail"><span>Type:</span> {{ $parent['parent_type'] }}</div>
            <div class="detail"><span>Issue Date:</span> {{ $parent['issue_date'] }}</div>
            <div class="detail"><span>Valid Till:</span> {{ $parent['pass_validity'] }}</div>
            <div class="detail"><span>Card Type:</span> {{ $parent['card_type'] }}</div>

            <div class="qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode('GatePass:' . $parent['id'] . ':' . $parent['name'] . ':' . $parent['issue_date']) }}" alt="QR Code">
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        Please carry this card when visiting the school
    </div>
</div>
@endforeach
</div>

</body>
</html>
