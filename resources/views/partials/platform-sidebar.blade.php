<!-- Start Platform Sidebar Area -->
<div class="sidebar-area" id="sidebar-area">
    <div class="logo position-relative d-flex align-items-center justify-content-between">
        <a href="{{ route('platform-admin.dashboard') }}" class="d-block text-decoration-none position-relative">
            <img src="{{ asset('assets/images/Full Logo_SMS.png') }}" alt="logo-icon" style="max-width: 180px; max-height: 50px; object-fit: contain;">
        </a>
        <button class="sidebar-burger-menu-close bg-transparent py-3 border-0 opacity-0 z-n1 position-absolute top-50 end-0 translate-middle-y" id="sidebar-burger-menu-close">
            <span class="border-1 d-block for-dark-burger" style="border-bottom: 1px solid #475569; height: 1px; width: 25px; transform: rotate(45deg);"></span>
            <span class="border-1 d-block for-dark-burger" style="border-bottom: 1px solid #475569; height: 1px; width: 25px; transform: rotate(-45deg);"></span>
        </button>
        <button class="sidebar-burger-menu bg-transparent p-0 border-0" id="sidebar-burger-menu">
            <span class="border-1 d-block for-dark-burger" style="border-bottom: 1px solid #475569; height: 1px; width: 25px;"></span>
            <span class="border-1 d-block for-dark-burger" style="border-bottom: 1px solid #475569; height: 1px; width: 25px; margin: 6px 0;"></span>
            <span class="border-1 d-block for-dark-burger" style="border-bottom: 1px solid #475569; height: 1px; width: 25px;"></span>
        </button>
    </div>

    <aside id="layout-menu" class="layout-menu menu-vertical menu active" data-simplebar>
        <ul class="menu-inner">
            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">Platform</span>
            </li>

            <li class="menu-item {{ request()->routeIs('platform-admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('platform-admin.dashboard') }}" class="menu-link {{ request()->routeIs('platform-admin.dashboard') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ri-dashboard-line"></i></span>
                    <span class="title">Dashboard</span>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('platform-admin.schools.*') ? 'active' : '' }}">
                <a href="{{ route('platform-admin.schools.index') }}" class="menu-link {{ request()->routeIs('platform-admin.schools.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ri-school-line"></i></span>
                    <span class="title">Schools & Limits</span>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('platform-admin.setup-guide') ? 'active' : '' }}">
                <a href="{{ route('platform-admin.setup-guide') }}" class="menu-link {{ request()->routeIs('platform-admin.setup-guide') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ri-tools-line"></i></span>
                    <span class="title">Provisioning Setup</span>
                </a>
            </li>

            <li class="menu-item">
                <form action="{{ route('platform-admin.logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="menu-link bg-transparent border-0 w-100 text-start">
                        <span class="menu-icon"><i class="ri-logout-box-r-line"></i></span>
                        <span class="title">Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </aside>
</div>
<!-- End Platform Sidebar Area -->
