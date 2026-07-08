<!-- Link Of JS File -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/sidebar-menu.js') }}"></script>
<script src="{{ asset('assets/js/quill.min.js') }}"></script>
<script src="{{ asset('assets/js/data-table.js') }}"></script>
<script src="{{ asset('assets/js/prism.js') }}"></script>
<script src="{{ asset('assets/js/clipboard.min.js') }}"></script>
<script src="{{ asset('assets/js/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/js/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/js/echarts.min.js') }}"></script>
<script src="{{ asset('assets/js/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/fullcalendar.main.js') }}"></script>
<script src="{{ asset('assets/js/jsvectormap.min.js') }}"></script>
<script src="{{ asset('assets/js/world-merc.js') }}"></script>
<script src="{{ asset('assets/js/custom/apexcharts.js') }}"></script>
<script src="{{ asset('assets/js/custom/echarts.js') }}"></script>
<script src="{{ asset('assets/js/custom/maps.js') }}"></script>
<script src="{{ asset('assets/js/custom/custom.js') }}"></script>

@php
    $liveChatUnreadUrl = null;
    if (Auth::guard('admin')->check()) {
        $liveChatUnreadUrl = route('live-chat.unread-count');
    } elseif (Auth::guard('staff')->check()) {
        $liveChatUnreadUrl = route('staff.chat.unread-count');
    } elseif (Auth::guard('accountant')->check()) {
        $liveChatUnreadUrl = route('accountant.chat.unread-count');
    }
@endphp
@if($liveChatUnreadUrl)
<script>
(function () {
    const unreadUrl = @json($liveChatUnreadUrl);

    function refreshLiveChatBadge() {
        fetch(unreadUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (response) {
                return response.ok ? response.json() : { count: 0 };
            })
            .then(function (data) {
                const count = parseInt(data.count, 10) || 0;
                document.querySelectorAll('[data-live-chat-badge]').forEach(function (badge) {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : String(count);
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }
                });
            })
            .catch(function () {});
    }

    refreshLiveChatBadge();
    setInterval(refreshLiveChatBadge, 30000);
})();
</script>
@endif

