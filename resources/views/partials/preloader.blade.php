<!-- Start Preloader Area -->
<div class="preloader" id="preloader">
    <div class="preloader">
        <div class="text-center">
            <img id="preloaderLogo" src="{{ asset('assets/images/logo-icon.png') }}" alt="logo-icon" style="max-width: 80px; max-height: 80px; margin-bottom: 20px;">
            <div class="waviy position-relative" id="preloaderText">
                <span class="d-inline-block">I</span>
                <span class="d-inline-block">C</span>
                <span class="d-inline-block">M</span>
                <span class="d-inline-block">S</span>
            </div>
        </div>
    </div>
</div>
<!-- End Preloader Area -->

<script>
// Update preloader logo and text from localStorage
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

