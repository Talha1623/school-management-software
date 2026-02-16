<!-- Start Preloader Area -->
<div class="preloader" id="preloader">
    <div class="preloader">
        <div class="text-center">
            @php
                $settings = \App\Models\GeneralSetting::getSettings();
                $logoUrl = $settings->logo ? asset('storage/' . $settings->logo) : asset('assets/images/Full Logo_SMS.png');
                $systemName = $settings->system_name ?? 'ICMS';
            @endphp
            <img id="preloaderLogo" src="{{ $logoUrl }}" alt="logo-icon" style="max-width: 200px; max-height: 80px; margin-bottom: 20px;">
            <div class="waviy position-relative" id="preloaderText">
                @foreach(str_split($systemName) as $char)
                    <span class="d-inline-block">{{ $char }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>
<!-- End Preloader Area -->

<script>
// Update preloader logo and text from localStorage (for dynamic updates)
document.addEventListener('DOMContentLoaded', function() {
    const savedLogo = localStorage.getItem('schoolLogo');
    const savedName = localStorage.getItem('systemName');
    
    if (savedLogo) {
        const preloaderLogo = document.getElementById('preloaderLogo');
        if (preloaderLogo) {
            preloaderLogo.src = savedLogo;
        }
    }
    
    if (savedName) {
        const preloaderText = document.getElementById('preloaderText');
        if (preloaderText) {
            // Clear existing text
            preloaderText.innerHTML = '';
            // Add each letter as a span
            for (let i = 0; i < savedName.length; i++) {
                const span = document.createElement('span');
                span.className = 'd-inline-block';
                span.textContent = savedName[i];
                preloaderText.appendChild(span);
            }
        }
    }
});
</script>

