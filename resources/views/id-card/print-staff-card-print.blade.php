<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp
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
        .staff-card{
            background:{{ $designSettings['gradient_color1'] ?? '#FFFFFF' }};
            border-radius:{{ ($designSettings['border_style'] ?? 'rounded') == 'rounded' ? '12px' : (($designSettings['border_style'] ?? 'rounded') == 'square' ? '0px' : '12px') }};
            border:{{ ($designSettings['border_style'] ?? 'rounded') == 'none' ? 'none' : '2px solid ' . ($designSettings['accent_color'] ?? '#003471') }};
            height:220px;
            padding:12px;
            position:relative;
            overflow:hidden;
        }

        /* HEADER */
        .card-header{
            display:flex;
            align-items:center;
            gap:8px;
            border-bottom:2px solid {{ $designSettings['accent_color'] ?? '#003471' }};
            padding-bottom:6px;
        }

        .school-logo{
            width:40px;
            height:40px;
            border-radius:50%;
            background:#fff;
            border:2px solid {{ $designSettings['accent_color'] ?? '#003471' }};
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
            color:{{ $designSettings['accent_color'] ?? '#003471' }};
            line-height:1.2;
        }

        /* BODY */
        .card-body{
            display:flex;
            gap:10px;
            margin-top:10px;
        }

        /* PHOTO */
        .photo-box{
            text-align:center;
        }

        .photo-box img{
            width:70px;
            height:70px;
            border-radius:50%;
            border:2px solid {{ $designSettings['accent_color'] ?? '#003471' }};
            object-fit:cover;
        }

        .staff-name{
            font-size:11px;
            font-weight:700;
            margin-top:4px;
            color:{{ $designSettings['staff_name_color'] ?? '#000000' }};
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

        .color-row {
            display: none;
        }

        .color-row .color-group {
            margin-bottom: 8px;
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
                size:A4 {{ ($designSettings['orientation'] ?? 'portrait') == 'landscape' ? 'landscape' : 'portrait' }};
                margin:10mm;
            }
        }
    </style>
</head>

<body>

<!-- Card Design Customization Panel -->
<div class="design-panel">
    <h3>Card Design Customization</h3>
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
                <label>Staff Name Color:</label>
                <input type="color" id="staffNameColor" value="{{ $designSettings['staff_name_color'] ?? '#000000' }}">
                <input type="text" id="staffNameColorText" value="{{ $designSettings['staff_name_color'] ?? '#000000' }}">
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
                        <option value="landscape" {{ ($designSettings['orientation'] ?? 'portrait') == 'landscape' ? 'selected' : '' }}>Landscape</option>
                        <option value="portrait" {{ ($designSettings['orientation'] ?? 'portrait') == 'portrait' ? 'selected' : '' }}>Portrait</option>
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
        params.set('staff_name_color', document.getElementById('staffNameColor').value);
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

@foreach($staff as $member)
<div class="staff-card">

    <!-- HEADER -->
    <div class="card-header">
        <div class="school-logo">
            <img src="{{ asset('assets/images/Full Logo_SMS.png') }}" alt="School Logo" onerror="this.src='{{ asset('assets/images/logo-icon.png') }}'">
        </div>
        <div class="school-name">
            {{ config('app.name','School Name') }}<br>
            Staff ID Card
        </div>
    </div>

    <!-- BODY -->
    <div class="card-body">

        <!-- PHOTO -->
        <div class="photo-box">
            @if($member->photo)
                <img src="{{ Storage::url($member->photo) }}" alt="Staff Photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="photo-placeholder" style="display: none; width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, {{ $designSettings['accent_color'] ?? '#003471' }} 0%, {{ $designSettings['secondary_color'] ?? '#F08080' }} 100%); border: 2px solid {{ $designSettings['accent_color'] ?? '#003471' }}; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <span style="font-size: 35px; color: rgba(255, 255, 255, 0.8);">👤</span>
                </div>
            @else
                <div class="photo-placeholder" style="width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, {{ $designSettings['accent_color'] ?? '#003471' }} 0%, {{ $designSettings['secondary_color'] ?? '#F08080' }} 100%); border: 2px solid {{ $designSettings['accent_color'] ?? '#003471' }}; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <span style="font-size: 35px; color: rgba(255, 255, 255, 0.8);">👤</span>
                </div>
            @endif
            <div class="staff-name">{{ $member->name }}</div>
        </div>

        <!-- DETAILS -->
        <div class="details">
            @if($member->emp_id)
            <div class="detail"><span>ID:</span> {{ $member->emp_id }}</div>
            @endif
            @if($member->designation)
            <div class="detail"><span>Designation:</span> {{ $member->designation }}</div>
            @endif
            <div class="detail"><span>Campus:</span> {{ $member->campus ?? 'N/A' }}</div>
            @if($member->phone)
            <div class="detail"><span>Phone:</span> {{ $member->phone }}</div>
            @endif

            <div class="qr">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode($member->emp_id ?? $member->id) }}">
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="footer">
        Official Staff ID Card • Valid for Academic Use
    </div>

</div>
@endforeach

</div>

</body>
</html>
