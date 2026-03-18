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
    @php
        $settings = \App\Models\GeneralSetting::getSettings();
        $systemName = $settings->system_name ?? 'ICMS';
    @endphp
    <title>@yield('title', $systemName . ' Management System')</title>

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
    
    <!-- AJAX Navigation - Prevent Page Reload and Preloader -->
    <script>
    (function() {
        'use strict';
        
        // Function to handle AJAX navigation
        function handleAjaxNavigation(href, e) {
            // Skip if no href or external link
            if (!href || (href.startsWith('http') && !href.includes(window.location.hostname))) {
                return false;
            }
            
            // Skip if it's a form submission link or special action
            if (href.includes('logout') || href.includes('export') || href.includes('print') || href.includes('download') || 
                href.includes('pdf') || href.includes('excel') || href.includes('csv') || href.includes('logout')) {
                return false; // Allow normal navigation for these
            }
            
            // Skip anchor links, javascript links, and blank targets
            if (href.startsWith('#') || href.startsWith('javascript:') || href === '') {
                return false;
            }
            
            // Prevent default navigation
            if (e) {
                e.preventDefault();
            }
            
            // Don't show preloader for AJAX navigation (only show on initial page load/login)
            // Ensure preloader is hidden
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
            
            // Fetch the new page content via AJAX
            fetch(href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Create a temporary DOM element to parse the HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Extract the main content
                const newContent = doc.querySelector('.main-content-container');
                const newTitle = doc.querySelector('title');
                
                if (newContent) {
                    // Update the main content area
                    const currentContent = document.querySelector('.main-content-container');
                    if (currentContent) {
                        currentContent.innerHTML = newContent.innerHTML;
                    }
                    
                    // Update page title
                    if (newTitle) {
                        document.title = newTitle.textContent;
                    }
                    
                    // Update URL without reload
                    window.history.pushState({path: href}, '', href);
                    
                    // Re-initialize any scripts that need to run on page load
                    // Trigger custom event for page change
                    window.dispatchEvent(new Event('pagechange'));
                    
                    // Re-run any inline scripts in the new content
                    const scripts = newContent.querySelectorAll('script');
                    scripts.forEach(function(oldScript) {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => {
                            newScript.setAttribute(attr.name, attr.value);
                        });
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        document.body.appendChild(newScript);
                        oldScript.parentNode.removeChild(oldScript);
                    });
                } else {
                    // Fallback to normal navigation if content not found
                    window.location.href = href;
                }
            })
            .catch(error => {
                console.error('Navigation error:', error);
                // Fallback to normal navigation on error
                window.location.href = href;
            });
            
            return true;
        }
        
        // Intercept all navigation links when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Function to attach listeners to links
            function attachLinkListeners() {
                // Intercept all internal navigation links (sidebar, header, content area)
                const allLinks = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="javascript:"]):not([target="_blank"]):not([href^="mailto:"]):not([href^="tel:"])');
                
                allLinks.forEach(function(link) {
                    // Skip if already has AJAX listener
                    if (link.dataset.ajaxListener === 'true') {
                        return;
                    }
                    
                    link.dataset.ajaxListener = 'true';
                    
                    link.addEventListener('click', function(e) {
                        const href = this.getAttribute('href');
                        if (handleAjaxNavigation(href, e)) {
                            // Navigation handled by AJAX
                        }
                    });
                });
            }
            
            // Attach listeners initially
            attachLinkListeners();
            
            // Re-attach listeners after AJAX navigation (for dynamically added links)
            window.addEventListener('pagechange', function() {
                setTimeout(attachLinkListeners, 100);
            });
        });
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.path) {
                handleAjaxNavigation(e.state.path, null);
            }
        });
        
        // Ensure preloader is hidden after initial page load
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
        });
        
        // Prevent preloader from showing on any navigation
        // Override any code that tries to show preloader
        const originalPreloaderShow = function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
        };
        
        // Monitor and hide preloader continuously (only for navigation, not initial load)
        let isInitialLoad = true;
        window.addEventListener('load', function() {
            setTimeout(function() {
                isInitialLoad = false;
            }, 1000);
        });
        
        // Hide preloader on any navigation event
        document.addEventListener('click', function(e) {
            if (!isInitialLoad) {
                const preloader = document.getElementById('preloader');
                if (preloader) {
                    preloader.style.display = 'none';
                }
            }
        }, true);
        
        // Also hide preloader when AJAX navigation completes
        window.addEventListener('pagechange', function() {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
        });
    })();
    </script>
</body>
</html>

