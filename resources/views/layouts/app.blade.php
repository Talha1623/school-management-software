<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Links Of CSS File -->
    <link rel="stylesheet" href="{{ asset('assets/css/sidebar-menu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/prism.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/jsvectormap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    
    <!-- Title -->
    <title>@yield('title', 'ICMS Management System')</title>

    @stack('styles')
</head>
<body class="bg-body-bg">
    
    @include('partials.preloader')

    @include('partials.sidebar')

    <!-- Start Main Content Area -->
    <div class="container-fluid">
        <div class="main-content d-flex flex-column">
            @include('partials.header')

            <div class="main-content-container overflow-hidden">
                @yield('content')
            </div>

            <div class="flex-grow-1"></div>

            @include('partials.footer')
        </div>
    </div>
    <!-- End Main Content Area -->

    @include('partials.theme-settings')

    @include('partials.scripts')

    @stack('scripts')
    
    <!-- Dynamic Logo and System Name Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load saved logo and system name from localStorage
        const savedLogo = localStorage.getItem('schoolLogo');
        const savedName = localStorage.getItem('systemName');
        
        if (savedLogo) {
            const sidebarLogo = document.querySelector('.sidebar-area .logo img');
            if (sidebarLogo) sidebarLogo.src = savedLogo;
            
            const headerLogo = document.querySelector('#header-area .logo img, .header-area .logo img');
            if (headerLogo) headerLogo.src = savedLogo;
            
            const preloaderLogo = document.getElementById('preloaderLogo');
            if (preloaderLogo) preloaderLogo.src = savedLogo;
        }
       
        if (savedName) {
            const sidebarText = document.querySelector('.sidebar-area .logo-text');
            if (sidebarText) sidebarText.textContent = savedName;
            
            const headerText = document.querySelector('#header-area .logo-text, .header-area .logo-text');
            if (headerText) headerText.textContent = savedName;
            
            // Update preloader text
            const preloaderText = document.getElementById('preloaderText');
            if (preloaderText) {
                preloaderText.innerHTML = '';
                for (let i = 0; i < savedName.length; i++) {
                    const span = document.createElement('span');
                    span.className = 'd-inline-block';
                    span.textContent = savedName[i];
                    preloaderText.appendChild(span);
                }
            }
            
            // Update page title
            const currentTitle = document.title;
            if (currentTitle.includes('ICMS')) {
                document.title = currentTitle.replace('ICMS', savedName);
            }
        }
    });
    </script>
</body>
</html>

