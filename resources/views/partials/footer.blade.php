<!-- Start Footer Area -->
<footer class="footer-area bg-white text-center rounded-10 rounded-bottom-0">
    @php
        $settings = \App\Models\GeneralSetting::getSettings();
        $systemName = $settings->system_name ?? 'ICMS';
    @endphp
    <p class="fs-16 text-body">© <span class="text-secondary">{{ $systemName }} Management System</span></p>
</footer>
<!-- End Footer Area -->

