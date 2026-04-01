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
<body class="bg-body-bg" sidebar-data-theme="sidebar-show">
    
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

    <style>
        /* Fix: Laravel default (Tailwind) pagination SVG arrows become huge when Tailwind CSS isn't loaded */
        .pagination .page-link,
        nav[aria-label="Pagination Navigation"] a,
        nav[aria-label="Pagination Navigation"] span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            line-height: 1;
        }
        .pagination .page-link svg,
        nav[aria-label="Pagination Navigation"] svg {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            display: inline-block !important;
            flex: 0 0 auto;
        }
    </style>
    
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

        // Top navigation progress bar (for AJAX navigation)
        const TOP_BAR_ID = 'ajaxTopProgressBar';
        function ensureTopProgressBar() {
            let bar = document.getElementById(TOP_BAR_ID);
            if (bar) return bar;

            bar = document.createElement('div');
            bar.id = TOP_BAR_ID;
            bar.style.position = 'fixed';
            bar.style.top = '0';
            bar.style.left = '0';
            bar.style.height = '3px';
            bar.style.width = '0%';
            bar.style.opacity = '0';
            bar.style.zIndex = '999999';
            bar.style.background = '#277507';
            bar.style.boxShadow = '0 0 12px rgba(39, 117, 7, .35)';
            bar.style.transition = 'width 350ms ease, opacity 250ms ease';
            document.body.appendChild(bar);
            return bar;
        }

        let topBarTimer = null;
        function startTopProgress() {
            const bar = ensureTopProgressBar();
            if (topBarTimer) clearInterval(topBarTimer);
            bar.style.opacity = '1';
            bar.style.width = '10%';

            // Simulate progress while request is in-flight (never reaches 100% until done)
            let current = 10;
            topBarTimer = setInterval(function() {
                current = Math.min(current + Math.random() * 12, 85);
                bar.style.width = current.toFixed(0) + '%';
            }, 250);
        }

        function finishTopProgress() {
            const bar = ensureTopProgressBar();
            if (topBarTimer) {
                clearInterval(topBarTimer);
                topBarTimer = null;
            }
            bar.style.width = '100%';
            // Hide after it completes
            setTimeout(function() {
                bar.style.opacity = '0';
                setTimeout(function() {
                    bar.style.width = '0%';
                }, 250);
            }, 250);
        }
        
        // Function to update active menu items based on current URL
        function updateActiveMenuItems(currentUrl) {
            // Parse current URL to get pathname
            let currentPath = currentUrl;
            try {
                const urlObj = new URL(currentUrl, window.location.origin);
                currentPath = urlObj.pathname;
            } catch(e) {
                // If URL parsing fails, use as is
                if (currentUrl.startsWith('/')) {
                    currentPath = currentUrl.split('?')[0]; // Remove query string
                } else if (currentUrl.startsWith('http')) {
                    try {
                        const url = new URL(currentUrl);
                        currentPath = url.pathname;
                    } catch(e2) {
                        currentPath = currentUrl;
                    }
                }
            }
            
            // Normalize path (remove trailing slash except for root)
            if (currentPath !== '/' && currentPath.endsWith('/')) {
                currentPath = currentPath.slice(0, -1);
            }
            
            // Remove all active classes from menu items
            const allMenuItems = document.querySelectorAll('.menu-item');
            const allMenuLinks = document.querySelectorAll('.menu-link');
            
            allMenuItems.forEach(function(item) {
                item.classList.remove('active', 'open');
            });
            
            allMenuLinks.forEach(function(link) {
                link.classList.remove('active');
            });
            
            // Find and activate menu items that match current URL
            const menuLinks = document.querySelectorAll('.menu-link[href]');
            let bestMatch = null;
            let bestMatchLength = 0;
            
            menuLinks.forEach(function(link) {
                const linkHref = link.getAttribute('href');
                if (!linkHref || linkHref === '#' || linkHref.startsWith('javascript:') || linkHref.startsWith('mailto:') || linkHref.startsWith('tel:')) {
                    return;
                }
                
                // Parse link href
                let linkPath = linkHref;
                try {
                    const linkUrl = new URL(linkHref, window.location.origin);
                    linkPath = linkUrl.pathname;
                } catch(e) {
                    if (linkHref.startsWith('/')) {
                        linkPath = linkHref.split('?')[0]; // Remove query string
                    }
                }
                
                // Normalize link path
                if (linkPath !== '/' && linkPath.endsWith('/')) {
                    linkPath = linkPath.slice(0, -1);
                }
                
                // Check for exact match
                if (currentPath === linkPath) {
                    bestMatch = link;
                    bestMatchLength = linkPath.length;
                } else if (currentPath.startsWith(linkPath + '/') && linkPath !== '/' && linkPath.length > bestMatchLength) {
                    // Check for path prefix match (for nested routes)
                    bestMatch = link;
                    bestMatchLength = linkPath.length;
                }
            });
            
            // Activate the best matching menu item
            if (bestMatch) {
                bestMatch.classList.add('active');
                
                // Add active class to parent menu item
                const menuItem = bestMatch.closest('.menu-item');
                if (menuItem) {
                    menuItem.classList.add('active');
                    
                    // If it's a nested menu, also open parent menus
                    let parent = menuItem.parentElement;
                    while (parent && parent.classList.contains('menu-sub')) {
                        const parentMenuItem = parent.closest('.menu-item');
                        if (parentMenuItem) {
                            parentMenuItem.classList.add('open', 'active');
                            const parentToggle = parentMenuItem.querySelector('.menu-toggle');
                            if (parentToggle) {
                                parentToggle.classList.add('active');
                            }
                        }
                        parent = parent.parentElement?.closest('.menu-sub');
                    }
                }
            }
        }
        
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

            // Show top progress line so user feels navigation happened
            startTopProgress();
            
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
                    
                    // Update active menu items based on current URL
                    updateActiveMenuItems(href);

                    // Always scroll to top on navigation (so user lands at top of new page)
                    try {
                        window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
                    } catch (e) {
                        window.scrollTo(0, 0);
                    }

                    // Complete the top progress line
                    finishTopProgress();
                    
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
                    finishTopProgress();
                    // Fallback to normal navigation if content not found
                    window.location.href = href;
                }
            })
            .catch(error => {
                console.error('Navigation error:', error);
                finishTopProgress();
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
                startTopProgress();
                handleAjaxNavigation(e.state.path, null);
                try {
                    window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
                } catch (e2) {
                    window.scrollTo(0, 0);
                }
            } else {
                // Update active menu items on browser back/forward
                updateActiveMenuItems(window.location.pathname);
            }
        });
        
        // Update active menu items on initial page load
        document.addEventListener('DOMContentLoaded', function() {
            updateActiveMenuItems(window.location.pathname);
            
            // Ensure sidebar is open after login (on dashboard or any page)
            const body = document.body;
            const sidebarArea = document.getElementById('sidebar-area');
            
            // Check if sidebar is hidden, if so, open it
            if (body.getAttribute('sidebar-data-theme') === 'sidebar-hide') {
                body.setAttribute('sidebar-data-theme', 'sidebar-show');
            }
            
            // Also ensure sidebar area has correct classes
            if (sidebarArea) {
                sidebarArea.classList.remove('sidebar-hide');
                sidebarArea.classList.add('sidebar-show');
            }
        });
        
        // Also ensure sidebar is open on window load (after preloader)
        window.addEventListener('load', function() {
            setTimeout(function() {
                const body = document.body;
                const sidebarArea = document.getElementById('sidebar-area');
                
                // Ensure sidebar is open
                if (body.getAttribute('sidebar-data-theme') === 'sidebar-hide') {
                    body.setAttribute('sidebar-data-theme', 'sidebar-show');
                }
                
                if (sidebarArea) {
                    sidebarArea.classList.remove('sidebar-hide');
                    sidebarArea.classList.add('sidebar-show');
                }
            }, 100);
        });
        
        // Prevent sidebar from closing - disable hamburger menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            // Disable hamburger menu button functionality
            const hamburgerMenu = document.getElementById('sidebar-burger-menu');
            const hamburgerMenuClose = document.getElementById('sidebar-burger-menu-close');
            
            if (hamburgerMenu) {
                hamburgerMenu.style.display = 'none';
                hamburgerMenu.removeEventListener('click', function() {});
                hamburgerMenu.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Force sidebar to stay open
                    const body = document.body;
                    body.setAttribute('sidebar-data-theme', 'sidebar-show');
                    const sidebarArea = document.getElementById('sidebar-area');
                    if (sidebarArea) {
                        sidebarArea.classList.remove('sidebar-hide');
                        sidebarArea.classList.add('sidebar-show');
                    }
                    return false;
                });
            }
            
            if (hamburgerMenuClose) {
                hamburgerMenuClose.style.display = 'none';
                hamburgerMenuClose.removeEventListener('click', function() {});
                hamburgerMenuClose.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Force sidebar to stay open
                    const body = document.body;
                    body.setAttribute('sidebar-data-theme', 'sidebar-show');
                    const sidebarArea = document.getElementById('sidebar-area');
                    if (sidebarArea) {
                        sidebarArea.classList.remove('sidebar-hide');
                        sidebarArea.classList.add('sidebar-show');
                    }
                    return false;
                });
            }
            
            // Continuously ensure sidebar stays open
            setInterval(function() {
                const body = document.body;
                const sidebarArea = document.getElementById('sidebar-area');
                
                if (body.getAttribute('sidebar-data-theme') === 'sidebar-hide') {
                    body.setAttribute('sidebar-data-theme', 'sidebar-show');
                }
                
                if (sidebarArea) {
                    if (sidebarArea.classList.contains('sidebar-hide')) {
                        sidebarArea.classList.remove('sidebar-hide');
                        sidebarArea.classList.add('sidebar-show');
                    }
                }
            }, 500); // Check every 500ms
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

