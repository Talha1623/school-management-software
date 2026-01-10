<!-- Start Sidebar Area -->
<div class="sidebar-area" id="sidebar-area">
    <div class="logo position-relative d-flex align-items-center justify-content-between">
        <a href="{{ route('accountant.dashboard') }}" class="d-block text-decoration-none position-relative" id="accountant-logo-link">
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
                <span class="menu-title-text">MENU</span>
            </li>
            
            {{-- Dashboard --}}
            <li class="menu-item {{ request()->routeIs('accountant.dashboard') ? 'active' : '' }}">
                <a href="{{ route('accountant.dashboard') }}" class="menu-link {{ request()->routeIs('accountant.dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">dashboard</span>
                    <span class="title">Dashboard</span>
                </a>
            </li>
            
            {{-- Task Management --}}
            <li class="menu-item {{ request()->routeIs('accountant.task-management*') ? 'active' : '' }}">
                <a href="{{ route('accountant.task-management') }}" class="menu-link {{ request()->routeIs('accountant.task-management*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">task</span>
                    <span class="title">Task Management</span>
                </a>
            </li>
            
            {{-- Fee Payment --}}
            <li class="menu-item {{ request()->routeIs('accountant.fee-payment*') ? 'active' : '' }}">
                <a href="{{ route('accountant.fee-payment') }}" class="menu-link {{ request()->routeIs('accountant.fee-payment*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">payments</span>
                    <span class="title">Fee Payment</span>
                </a>
            </li>
            
            {{-- Family Fee Calculator --}}
            <li class="menu-item {{ request()->routeIs('accountant.family-fee-calculator*') ? 'active' : '' }}">
                <a href="{{ route('accountant.family-fee-calculator') }}" class="menu-link {{ request()->routeIs('accountant.family-fee-calculator*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">calculate</span>
                    <span class="title">Family Fee Calculator</span>
                </a>
            </li>
            
            {{-- Generate Monthly Fee --}}
            <li class="menu-item {{ request()->routeIs('accountant.generate-monthly-fee*') ? 'active' : '' }}">
                <a href="{{ route('accountant.generate-monthly-fee') }}" class="menu-link {{ request()->routeIs('accountant.generate-monthly-fee*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">calendar_month</span>
                    <span class="title">Generate Monthly Fee</span>
                </a>
            </li>
            
            {{-- Generate Custom Fee --}}
            <li class="menu-item {{ request()->routeIs('accountant.generate-custom-fee*') ? 'active' : '' }}">
                <a href="{{ route('accountant.generate-custom-fee') }}" class="menu-link {{ request()->routeIs('accountant.generate-custom-fee*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">receipt_long</span>
                    <span class="title">Generate Custom Fee</span>
                </a>
            </li>
            
            {{-- Generate Transport Fee --}}
            <li class="menu-item {{ request()->routeIs('accountant.generate-transport-fee*') ? 'active' : '' }}">
                <a href="{{ route('accountant.generate-transport-fee') }}" class="menu-link {{ request()->routeIs('accountant.generate-transport-fee*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">directions_bus</span>
                    <span class="title">Generate Transport Fee</span>
                </a>
            </li>
            
            {{-- Fee Type / Heads --}}
            <li class="menu-item {{ request()->routeIs('accountant.fee-type*') ? 'active' : '' }}">
                <a href="{{ route('accountant.fee-type') }}" class="menu-link {{ request()->routeIs('accountant.fee-type*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">category</span>
                    <span class="title">Fee Type / Heads</span>
                </a>
            </li>
            
            {{-- Parents Credit System --}}
            <li class="menu-item {{ request()->routeIs('accountant.parents-credit-system*') ? 'active' : '' }}">
                <a href="{{ route('accountant.parents-credit-system') }}" class="menu-link {{ request()->routeIs('accountant.parents-credit-system*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">account_balance_wallet</span>
                    <span class="title">Parents Credit System</span>
                </a>
            </li>
            
            {{-- Direct Payment --}}
            <li class="menu-item {{ request()->routeIs('accountant.direct-payment*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accountant.direct-payment*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">credit_card</span>
                    <span class="title">Direct Payment</span>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('accountant.direct-payment.student') }}" class="menu-link {{ request()->routeIs('accountant.direct-payment.student*') ? 'active' : '' }}">
                            Student Payment
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('accountant.direct-payment.custom') }}" class="menu-link {{ request()->routeIs('accountant.direct-payment.custom*') ? 'active' : '' }}">
                            Custom Payment
                        </a>
                    </li>
                </ul>
            </li>
            
            {{-- SMS to Fee Defaulters --}}
            <li class="menu-item {{ request()->routeIs('accountant.sms-fee-defaulters*') ? 'active' : '' }}">
                <a href="{{ route('accountant.sms-fee-defaulters') }}" class="menu-link {{ request()->routeIs('accountant.sms-fee-defaulters*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">sms</span>
                    <span class="title">SMS to Fee Defaulters</span>
                </a>
            </li>
            
            {{-- Deleted Fees --}}
            <li class="menu-item {{ request()->routeIs('accountant.deleted-fees*') ? 'active' : '' }}">
                <a href="{{ route('accountant.deleted-fees') }}" class="menu-link {{ request()->routeIs('accountant.deleted-fees*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">delete</span>
                    <span class="title">Deleted Fees</span>
                </a>
            </li>
            
            {{-- Print Fee Vouchers --}}
            <li class="menu-item {{ request()->routeIs('accountant.print-fee-vouchers*') ? 'active' : '' }}">
                <a href="{{ route('accountant.print-fee-vouchers') }}" class="menu-link {{ request()->routeIs('accountant.print-fee-vouchers*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">print</span>
                    <span class="title">Print Fee Vouchers</span>
                </a>
            </li>
            
            {{-- Print Balance Sheet --}}
            <li class="menu-item {{ request()->routeIs('accountant.print-balance-sheet*') ? 'active' : '' }}">
                <a href="{{ route('accountant.print-balance-sheet') }}" class="menu-link {{ request()->routeIs('accountant.print-balance-sheet*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">description</span>
                    <span class="title">Print Balance Sheet</span>
                </a>
            </li>
            
            {{-- Expense Management --}}
            <li class="menu-item {{ request()->routeIs('accountant.expense-management*') || request()->routeIs('accountant.add-manage-expense*') || request()->routeIs('accountant.expense-categories*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accountant.expense-management*') || request()->routeIs('accountant.add-manage-expense*') || request()->routeIs('accountant.expense-categories*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">shopping_cart</span>
                    <span class="title">Expense Management</span>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('accountant.add-manage-expense') }}" class="menu-link {{ request()->routeIs('accountant.add-manage-expense*') ? 'active' : '' }}">
                            Add / Manage Expense
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('accountant.expense-categories') }}" class="menu-link {{ request()->routeIs('accountant.expense-categories*') ? 'active' : '' }}">
                            Expense Categories
                        </a>
                    </li>
                </ul>
            </li>
            
            {{-- Reporting Area --}}
            <li class="menu-item {{ request()->routeIs('accountant.reporting-area*') ? 'active' : '' }}">
                <a href="{{ route('accountant.reporting-area') }}" class="menu-link {{ request()->routeIs('accountant.reporting-area*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">assessment</span>
                    <span class="title">Reporting Area</span>
                </a>
            </li>
            
            {{-- Academic / Holiday Calendar --}}
            <li class="menu-item {{ request()->routeIs('accountant.academic-calendar*') ? 'active' : '' }}">
                <a href="{{ route('accountant.academic-calendar') }}" class="menu-link {{ request()->routeIs('accountant.academic-calendar*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">event</span>
                    <span class="title">Academic / Holiday Calendar</span>
                </a>
            </li>
            
            {{-- Stock & Inventory --}}
            <li class="menu-item {{ request()->routeIs('accountant.stock-inventory*') || request()->routeIs('accountant.point-of-sale*') || request()->routeIs('accountant.manage-categories*') || request()->routeIs('accountant.product-and-stock*') || request()->routeIs('accountant.manage-all-sales*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accountant.stock-inventory*') || request()->routeIs('accountant.point-of-sale*') || request()->routeIs('accountant.manage-categories*') || request()->routeIs('accountant.product-and-stock*') || request()->routeIs('accountant.manage-all-sales*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">inventory</span>
                    <span class="title">Stock & Inventory</span>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('accountant.point-of-sale') }}" class="menu-link {{ request()->routeIs('accountant.point-of-sale*') ? 'active' : '' }}">
                            Point of Sale
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('accountant.manage-categories') }}" class="menu-link {{ request()->routeIs('accountant.manage-categories*') ? 'active' : '' }}">
                            Manage Categories
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('accountant.product-and-stock') }}" class="menu-link {{ request()->routeIs('accountant.product-and-stock*') ? 'active' : '' }}">
                            Product and Stock
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('accountant.manage-all-sales') }}" class="menu-link {{ request()->routeIs('accountant.manage-all-sales*') ? 'active' : '' }}">
                            Manage All Sales
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </aside>
</div>
<!-- End Sidebar Area -->

