<!-- Start Sidebar Area -->
<div class="sidebar-area" id="sidebar-area">
    <div class="logo position-relative d-flex align-items-center justify-content-between">
        <a href="{{ route('dashboard') }}" class="d-block text-decoration-none position-relative">
            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="logo-icon">
            <span class="logo-text text-secondary fw-semibold">ICMS</span>
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
                <span class="menu-title-text">MAIN</span>
            </li>
                   <li class="menu-item {{ request()->routeIs('dashboard*') || request()->routeIs('admission*') || request()->routeIs('student*') || request()->routeIs('parent*') || request()->routeIs('staff*') || request()->routeIs('id-card*') || request()->routeIs('accountants') || request()->routeIs('classes*') || request()->routeIs('attendance*') || request()->routeIs('timetable*') || request()->routeIs('academic-calendar*') || request()->routeIs('accounting*') || request()->routeIs('reports*') || request()->routeIs('stock*') || request()->routeIs('inventory*') || request()->routeIs('student-behavior*') || request()->routeIs('question-paper*') || request()->routeIs('test*') || request()->routeIs('exam*') || request()->routeIs('quiz*') || request()->routeIs('certification*') || request()->routeIs('homework-diary*') || request()->routeIs('study-material*') || request()->routeIs('leave-management*') || request()->routeIs('sms*') || request()->routeIs('notification*') || request()->routeIs('whatsapp*') || request()->routeIs('robobuddy*') || request()->routeIs('email-alerts*') || request()->routeIs('school.noticeboard') || request()->routeIs('manage.campuses') || request()->routeIs('admin.roles-management') || request()->routeIs('transport*') || request()->routeIs('website-management*') || request()->routeIs('account-settings') || request()->routeIs('change-password') || request()->routeIs('connections') || request()->routeIs('privacy-policy') || request()->routeIs('terms-conditions') ? 'open' : '' }}">
                       <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('dashboard*') || request()->routeIs('admission*') || request()->routeIs('student*') || request()->routeIs('parent*') || request()->routeIs('staff*') || request()->routeIs('id-card*') || request()->routeIs('accountants') || request()->routeIs('classes*') || request()->routeIs('attendance*') || request()->routeIs('timetable*') || request()->routeIs('academic-calendar*') || request()->routeIs('accounting*') || request()->routeIs('reports*') || request()->routeIs('stock*') || request()->routeIs('inventory*') || request()->routeIs('student-behavior*') || request()->routeIs('question-paper*') || request()->routeIs('test*') || request()->routeIs('exam*') || request()->routeIs('quiz*') || request()->routeIs('certification*') || request()->routeIs('homework-diary*') || request()->routeIs('study-material*') || request()->routeIs('leave-management*') || request()->routeIs('sms*') || request()->routeIs('notification*') || request()->routeIs('whatsapp*') || request()->routeIs('robobuddy*') || request()->routeIs('email-alerts*') || request()->routeIs('school.noticeboard') || request()->routeIs('manage.campuses') || request()->routeIs('admin.roles-management') || request()->routeIs('transport*') || request()->routeIs('website-management*') || request()->routeIs('account-settings') || request()->routeIs('change-password') || request()->routeIs('connections') || request()->routeIs('privacy-policy') || request()->routeIs('terms-conditions') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">dashboard</span>
                    <span class="title">Dashboard</span>
                    <span class="count">11</span>
                </a>
            
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('admission*') ? 'active' : '' }}">
                            Admission Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('admission.admit-student') }}" class="menu-link {{ request()->routeIs('admission.admit-student') ? 'active' : '' }}">
                                    Admit Student
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('admission.admit-bulk-student') }}" class="menu-link {{ request()->routeIs('admission.admit-bulk-student') ? 'active' : '' }}">
                                    Admit Bulk Student
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('admission.request') }}" class="menu-link {{ request()->routeIs('admission.request') ? 'active' : '' }}">
                                    Admission Request
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('admission.report') }}" class="menu-link {{ request()->routeIs('admission.report') ? 'active' : '' }}">
                                    Admission Report
                                </a>
                            </li>
                            <li class="menu-item {{ request()->routeIs('admission.inquiry*') ? 'open' : '' }}">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('admission.inquiry*') ? 'active' : '' }}">
                                    Admission Inquiry
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('admission.inquiry.manage') }}" class="menu-link {{ request()->routeIs('admission.inquiry.manage') ? 'active' : '' }}">
                                            Manage Inquiry
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('admission.inquiry.send-sms') }}" class="menu-link {{ request()->routeIs('admission.inquiry.send-sms') ? 'active' : '' }}">
                                            Send SMS to Inquiry
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('student*') ? 'active' : '' }}">
                            Student Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('student.information') }}" class="menu-link {{ request()->routeIs('student.information') ? 'active' : '' }}">
                                    Student Information
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('student.promotion') }}" class="menu-link {{ request()->routeIs('student.promotion') ? 'active' : '' }}">
                                    Student Promotion
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('student.birthday') }}" class="menu-link {{ request()->routeIs('student.birthday') ? 'active' : '' }}">
                                    Student Birthday
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('student.transfer') }}" class="menu-link {{ request()->routeIs('student.transfer') ? 'active' : '' }}">
                                    Student Transfer
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('student.info-report') }}" class="menu-link {{ request()->routeIs('student.info-report') ? 'active' : '' }}">
                                    Student Info Report
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('parent*') ? 'active' : '' }}">
                            Parent Account
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('parent.manage-access') }}" class="menu-link {{ request()->routeIs('parent.manage-access') ? 'active' : '' }}">
                                    Manage Access
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('parent.account-request') }}" class="menu-link {{ request()->routeIs('parent.account-request') ? 'active' : '' }}">
                                    Account Request
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('parent.print-gate-passes') }}" class="menu-link {{ request()->routeIs('parent.print-gate-passes') ? 'active' : '' }}">
                                    Print Gate Passes
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('parent.info-request') }}" class="menu-link {{ request()->routeIs('parent.info-request') ? 'active' : '' }}">
                                    Parent Info Request
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('staff*') ? 'active' : '' }}">
                            Staff Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('staff.management') }}" class="menu-link {{ request()->routeIs('staff.management') ? 'active' : '' }}">
                                    Staff Management
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('staff.birthday') }}" class="menu-link {{ request()->routeIs('staff.birthday') ? 'active' : '' }}">
                                    Staff Birthday
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('staff.job-inquiry') }}" class="menu-link {{ request()->routeIs('staff.job-inquiry') ? 'active' : '' }}">
                                    Job Inquiry/CV Bank
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('task-management') }}" class="menu-link {{ request()->routeIs('task-management') ? 'active' : '' }}">
                            Task Management
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('id-card*') ? 'active' : '' }}">
                            ID Card Printing
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('id-card.print-student') }}" class="menu-link {{ request()->routeIs('id-card.print-student') ? 'active' : '' }}">
                                    Print Student Card
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('id-card.print-staff') }}" class="menu-link {{ request()->routeIs('id-card.print-staff') ? 'active' : '' }}">
                                    Print Staff Card
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('accountants') }}" class="menu-link {{ request()->routeIs('accountants') ? 'active' : '' }}">
                            Accountants
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accounting*') ? 'active' : '' }}">
                            Accounting
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('accounting.generate-monthly-fee') }}" class="menu-link {{ request()->routeIs('accounting.generate-monthly-fee') ? 'active' : '' }}">
                                    Generate Monthly Fee
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('accounting.generate-custom-fee') }}" class="menu-link {{ request()->routeIs('accounting.generate-custom-fee') ? 'active' : '' }}">
                                    Generate Custom Fee
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('accounting.generate-transport-fee') }}" class="menu-link {{ request()->routeIs('accounting.generate-transport-fee') ? 'active' : '' }}">
                                    Generate Transport Fee
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('accounting.fee-type') }}" class="menu-link {{ request()->routeIs('accounting.fee-type') ? 'active' : '' }}">
                                    Fee Type / Fee Head
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('accounting.family-fee-calculator') }}" class="menu-link {{ request()->routeIs('accounting.family-fee-calculator') ? 'active' : '' }}">
                                    Family Fee Calculator
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('accounting.manage-advance-fee.index') }}" class="menu-link {{ request()->routeIs('accounting.manage-advance-fee*') ? 'active' : '' }}">
                                    Manage Advance Fee
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accounting.parent-wallet*') ? 'active' : '' }}">
                                    Parent Wallet System
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.parent-wallet.installments') }}" class="menu-link {{ request()->routeIs('accounting.parent-wallet.installments') ? 'active' : '' }}">
                                            Fee Installments
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.parent-wallet.sms-fee-duration') }}" class="menu-link {{ request()->routeIs('accounting.parent-wallet.sms-fee-duration') ? 'active' : '' }}">
                                            SMS To Fee Duration
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.parent-wallet.print-balance-sheet') }}" class="menu-link {{ request()->routeIs('accounting.parent-wallet.print-balance-sheet') ? 'active' : '' }}">
                                            Print Balance Sheet
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.parent-wallet.deleted-fees') }}" class="menu-link {{ request()->routeIs('accounting.parent-wallet.deleted-fees') ? 'active' : '' }}">
                                            Deleted Fees
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.parent-wallet.bulk-fee-payment') }}" class="menu-link {{ request()->routeIs('accounting.parent-wallet.bulk-fee-payment') ? 'active' : '' }}">
                                            Bulk Fee Payment
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.parent-wallet.discount-student') }}" class="menu-link {{ request()->routeIs('accounting.parent-wallet.discount-student') ? 'active' : '' }}">
                                            Discount Student
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.parent-wallet.accounts-settlement') }}" class="menu-link {{ request()->routeIs('accounting.parent-wallet.accounts-settlement') ? 'active' : '' }}">
                                            Accounts Settlement
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.parent-wallet.online-rejected-payments') }}" class="menu-link {{ request()->routeIs('accounting.parent-wallet.online-rejected-payments') ? 'active' : '' }}">
                                            Online Rejected Payments
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accounting.direct-payment*') ? 'active' : '' }}">
                                    Direct Payment
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.direct-payment.student') }}" class="menu-link {{ request()->routeIs('accounting.direct-payment.student') ? 'active' : '' }}">
                                            Student Payment
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.direct-payment.custom') }}" class="menu-link {{ request()->routeIs('accounting.direct-payment.custom') ? 'active' : '' }}">
                                            Custom Payment
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accounting.fee-increment*') ? 'active' : '' }}">
                                    Generate Fee Increment
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.fee-increment.percentage') }}" class="menu-link {{ request()->routeIs('accounting.fee-increment.percentage') ? 'active' : '' }}">
                                            Increment By Percentage
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.fee-increment.amount') }}" class="menu-link {{ request()->routeIs('accounting.fee-increment.amount') ? 'active' : '' }}">
                                            Increment By Amount
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accounting.fee-document*') ? 'active' : '' }}">
                                    Generate Fee Document
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.fee-document.decrement-percentage') }}" class="menu-link {{ request()->routeIs('accounting.fee-document.decrement-percentage') ? 'active' : '' }}">
                                            Decrement By Percentage
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.fee-document.decrement-amount') }}" class="menu-link {{ request()->routeIs('accounting.fee-document.decrement-amount') ? 'active' : '' }}">
                                            Decrement By Amount
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accounting.fee-voucher*') ? 'active' : '' }}">
                                    Print Fee Voucher
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.fee-voucher.student') }}" class="menu-link {{ request()->routeIs('accounting.fee-voucher.student') ? 'active' : '' }}">
                                            Student Vouchers
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('accounting.fee-voucher.family') }}" class="menu-link {{ request()->routeIs('accounting.fee-voucher.family') ? 'active' : '' }}">
                                            Family Vouchers
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('parent-complain') }}" class="menu-link {{ request()->routeIs('parent-complain') ? 'active' : '' }}">
                            Parent Complain
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('classes*') ? 'active' : '' }}">
                            Classes and Section
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('classes.manage-classes') }}" class="menu-link {{ request()->routeIs('classes.manage-classes') ? 'active' : '' }}">
                                    Manage Classes
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('classes.manage-section') }}" class="menu-link {{ request()->routeIs('classes.manage-section') ? 'active' : '' }}">
                                    Manage Section
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('manage-subjects') }}" class="menu-link {{ request()->routeIs('manage-subjects') ? 'active' : '' }}">
                            Manage Subjects
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('attendance*') ? 'active' : '' }}">
                            Manage Attendance
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('attendance.student') }}" class="menu-link {{ request()->routeIs('attendance.student') ? 'active' : '' }}">
                                    Student Attendance
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('attendance.staff') }}" class="menu-link {{ request()->routeIs('attendance.staff') ? 'active' : '' }}">
                                    Staff Attendance
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('attendance.barcode') }}" class="menu-link {{ request()->routeIs('attendance.barcode') ? 'active' : '' }}">
                                    Barcode Attendance
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('attendance.biometric') }}" class="menu-link {{ request()->routeIs('attendance.biometric') ? 'active' : '' }}">
                                    Bio Metric Attendance
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('attendance.facial-record') }}" class="menu-link {{ request()->routeIs('attendance.facial-record') ? 'active' : '' }}">
                                    Facial Record Attendance
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('attendance.account') }}" class="menu-link {{ request()->routeIs('attendance.account') ? 'active' : '' }}">
                                    Attendance Account
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('attendance.report') }}" class="menu-link {{ request()->routeIs('attendance.report') ? 'active' : '' }}">
                                    Attendance Report
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('online-classes') }}" class="menu-link {{ request()->routeIs('online-classes') ? 'active' : '' }}">
                            Online Classes
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('timetable*') ? 'active' : '' }}">
                            Timetable Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('timetable.add') }}" class="menu-link {{ request()->routeIs('timetable.add') ? 'active' : '' }}">
                                    Add Timetable
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('timetable.manage') }}" class="menu-link {{ request()->routeIs('timetable.manage') ? 'active' : '' }}">
                                    Manage Timetable
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('academic-calendar*') ? 'active' : '' }}">
                            Academic Holiday Calendar
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('academic-calendar.manage-events') }}" class="menu-link {{ request()->routeIs('academic-calendar.manage-events') ? 'active' : '' }}">
                                    Add/Manage/Events
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('academic-calendar.view') }}" class="menu-link {{ request()->routeIs('academic-calendar.view') ? 'active' : '' }}">
                                    View Calendar
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('fee-management') }}" class="menu-link {{ request()->routeIs('fee-management') ? 'active' : '' }}">
                            Fee Management
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('fee-payment') }}" class="menu-link {{ request()->routeIs('fee-payment') ? 'active' : '' }}">
                            Fee Payment
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('expense-management*') ? 'active' : '' }}">
                            Expense Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('expense-management.add') }}" class="menu-link {{ request()->routeIs('expense-management.add') ? 'active' : '' }}">
                                    Add Management Expense
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('expense-management.categories') }}" class="menu-link {{ request()->routeIs('expense-management.categories') ? 'active' : '' }}">
                                    Expense Categories
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('salary-loan*') ? 'active' : '' }}">
                            Salary and Loan Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('salary-loan.generate-salary') }}" class="menu-link {{ request()->routeIs('salary-loan.generate-salary') ? 'active' : '' }}">
                                    Generate Salary
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('salary-loan.manage-salaries') }}" class="menu-link {{ request()->routeIs('salary-loan.manage-salaries') ? 'active' : '' }}">
                                    Manage Salaries
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('salary-loan.loan-management') }}" class="menu-link {{ request()->routeIs('salary-loan.loan-management') ? 'active' : '' }}">
                                    Loan Management
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('salary-loan.salary-setting') }}" class="menu-link {{ request()->routeIs('salary-loan.salary-setting') ? 'active' : '' }}">
                                    Salary Setting
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('salary-loan.report') }}" class="menu-link {{ request()->routeIs('salary-loan.report') ? 'active' : '' }}">
                                    Salary and Loan Report
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('salary-loan.increment*') ? 'active' : '' }}">
                                    Generate Salary Increment
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('salary-loan.increment.percentage') }}" class="menu-link {{ request()->routeIs('salary-loan.increment.percentage') ? 'active' : '' }}">
                                            Increment By Percentage
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('salary-loan.increment.amount') }}" class="menu-link {{ request()->routeIs('salary-loan.increment.amount') ? 'active' : '' }}">
                                            Increment By Amount
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('salary-loan.decrement*') ? 'active' : '' }}">
                                    Generate Salary Decrement
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('salary-loan.decrement.percentage') }}" class="menu-link {{ request()->routeIs('salary-loan.decrement.percentage') ? 'active' : '' }}">
                                            Decrement By Percentage
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('salary-loan.decrement.amount') }}" class="menu-link {{ request()->routeIs('salary-loan.decrement.amount') ? 'active' : '' }}">
                                            Decrement By Amount
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('reports*') ? 'active' : '' }}">
                            Reporting
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('reports.fee-default') }}" class="menu-link {{ request()->routeIs('reports.fee-default') ? 'active' : '' }}">
                                    Fee Default Reports
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.head-wise-dues') }}" class="menu-link {{ request()->routeIs('reports.head-wise-dues') ? 'active' : '' }}">
                                    Head Wise Dues Summary
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.income-expense') }}" class="menu-link {{ request()->routeIs('reports.income-expense') ? 'active' : '' }}">
                                    Income & Expense Reports
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.debit-credit') }}" class="menu-link {{ request()->routeIs('reports.debit-credit') ? 'active' : '' }}">
                                    Debit & Credit Statement
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.unpaid-invoices') }}" class="menu-link {{ request()->routeIs('reports.unpaid-invoices') ? 'active' : '' }}">
                                    List of Unpaid Invoices
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.fee-discount') }}" class="menu-link {{ request()->routeIs('reports.fee-discount') ? 'active' : '' }}">
                                    Fee Discount
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.accounts-summary') }}" class="menu-link {{ request()->routeIs('reports.accounts-summary') ? 'active' : '' }}">
                                    Accounts Summary Reports
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.detailed-income') }}" class="menu-link {{ request()->routeIs('reports.detailed-income') ? 'active' : '' }}">
                                    Detailed Income Reports
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.detailed-expense') }}" class="menu-link {{ request()->routeIs('reports.detailed-expense') ? 'active' : '' }}">
                                    Detailed Expense Reports
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.staff-salary') }}" class="menu-link {{ request()->routeIs('reports.staff-salary') ? 'active' : '' }}">
                                    Staff Salary Reports
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.balance-sheet') }}" class="menu-link {{ request()->routeIs('reports.balance-sheet') ? 'active' : '' }}">
                                    Balance Sheet
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('reports.admission-data') }}" class="menu-link {{ request()->routeIs('reports.admission-data') ? 'active' : '' }}">
                                    Admission Data Reports
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('stock*') || request()->routeIs('inventory*') ? 'active' : '' }}">
                            Stock & Inventory
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('stock.point-of-sale') }}" class="menu-link {{ request()->routeIs('stock.point-of-sale') ? 'active' : '' }}">
                                    Point of Sale
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('stock.manage-categories') }}" class="menu-link {{ request()->routeIs('stock.manage-categories') ? 'active' : '' }}">
                                    Manage Categories
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('stock.products') }}" class="menu-link {{ request()->routeIs('stock.products*') ? 'active' : '' }}">
                                    Products & Stock
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('stock.add-bulk-products') }}" class="menu-link {{ request()->routeIs('stock.add-bulk-products') ? 'active' : '' }}">
                                    Add Bulk Products
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('stock.manage-sale-records') }}" class="menu-link {{ request()->routeIs('stock.manage-sale-records') ? 'active' : '' }}">
                                    Manage Sale Records
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('stock.sale-reports') }}" class="menu-link {{ request()->routeIs('stock.sale-reports') ? 'active' : '' }}">
                                    Stock and Sale Reports
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('student-behavior*') ? 'active' : '' }}">
                            Manage Student Behavior
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('student-behavior.recording') }}" class="menu-link {{ request()->routeIs('student-behavior.recording') ? 'active' : '' }}">
                                    Behavior Recording
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('student-behavior.categories') }}" class="menu-link {{ request()->routeIs('student-behavior.categories') ? 'active' : '' }}">
                                    Behavior Categories
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('student-behavior.progress-tracking') }}" class="menu-link {{ request()->routeIs('student-behavior.progress-tracking') ? 'active' : '' }}">
                                    Progress Tracking
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('student-behavior.reporting-analysis') }}" class="menu-link {{ request()->routeIs('student-behavior.reporting-analysis') ? 'active' : '' }}">
                                    Reporting and Analysis
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('question-paper*') ? 'active' : '' }}">
                            Question Paper
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('question-paper.manage-book') }}" class="menu-link {{ request()->routeIs('question-paper.manage-book') ? 'active' : '' }}">
                                    Manage Book
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('question-paper.question-bank') }}" class="menu-link {{ request()->routeIs('question-paper.question-bank') ? 'active' : '' }}">
                                    Question Bank
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('question-paper.generate') }}" class="menu-link {{ request()->routeIs('question-paper.generate') ? 'active' : '' }}">
                                    Generate Question Paper
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test*') ? 'active' : '' }}">
                            Test Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('test.list') }}" class="menu-link {{ request()->routeIs('test.list') ? 'active' : '' }}">
                                    Test List
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('test.marks-entry') }}" class="menu-link {{ request()->routeIs('test.marks-entry') ? 'active' : '' }}">
                                    Marks Entry
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('test.schedule') }}" class="menu-link {{ request()->routeIs('test.schedule') ? 'active' : '' }}">
                                    Test Schedule
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.assign-grades*') ? 'active' : '' }}">
                                    Assign Grades
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('test.assign-grades.particular') }}" class="menu-link {{ request()->routeIs('test.assign-grades.particular') ? 'active' : '' }}">
                                            For Particular Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('test.assign-grades.combined') }}" class="menu-link {{ request()->routeIs('test.assign-grades.combined') ? 'active' : '' }}">
                                            For Combined Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.teacher-remarks*') ? 'active' : '' }}">
                                    Teacher Remarks
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('test.teacher-remarks.practical') }}" class="menu-link {{ request()->routeIs('test.teacher-remarks.practical') ? 'active' : '' }}">
                                            For Practical Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('test.teacher-remarks.combined') }}" class="menu-link {{ request()->routeIs('test.teacher-remarks.combined') ? 'active' : '' }}">
                                            For Combine Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.tabulation-sheet*') ? 'active' : '' }}">
                                    Tabulation Sheet
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('test.tabulation-sheet.practical') }}" class="menu-link {{ request()->routeIs('test.tabulation-sheet.practical') ? 'active' : '' }}">
                                            For Practical Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('test.tabulation-sheet.combine') }}" class="menu-link {{ request()->routeIs('test.tabulation-sheet.combine') ? 'active' : '' }}">
                                            For Combine Test
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.position-holder*') ? 'active' : '' }}">
                                    Position Holder
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('test.position-holder.practical') }}" class="menu-link {{ request()->routeIs('test.position-holder.practical') ? 'active' : '' }}">
                                            For Practical Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('test.position-holder.combine') }}" class="menu-link {{ request()->routeIs('test.position-holder.combine') ? 'active' : '' }}">
                                            For Combine Test
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.send-marks*') ? 'active' : '' }}">
                                    Send Marks to Parents
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('test.send-marks.practical') }}" class="menu-link {{ request()->routeIs('test.send-marks.practical') ? 'active' : '' }}">
                                            For Practical Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('test.send-marks.combined') }}" class="menu-link {{ request()->routeIs('test.send-marks.combined') ? 'active' : '' }}">
                                            Combine Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.send-marksheet*') ? 'active' : '' }}">
                                    Send Marksheet via WA
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('test.send-marksheet.practical') }}" class="menu-link {{ request()->routeIs('test.send-marksheet.practical') ? 'active' : '' }}">
                                            For Practical Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('test.send-marksheet.combine') }}" class="menu-link {{ request()->routeIs('test.send-marksheet.combine') ? 'active' : '' }}">
                                            Combine Test Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.print-marksheets*') ? 'active' : '' }}">
                                    Print Marksheets
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('test.print-marksheets.practical') }}" class="menu-link {{ request()->routeIs('test.print-marksheets.practical') ? 'active' : '' }}">
                                            For Practical Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('test.print-marksheets.combine') }}" class="menu-link {{ request()->routeIs('test.print-marksheets.combine') ? 'active' : '' }}">
                                            Combine Test
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam*') ? 'active' : '' }}">
                            Exam Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('exam.list') }}" class="menu-link {{ request()->routeIs('exam.list') ? 'active' : '' }}">
                                    Exam List
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('exam.marks-entry') }}" class="menu-link {{ request()->routeIs('exam.marks-entry') ? 'active' : '' }}">
                                    Marks Entry
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('exam.print-admit-cards') }}" class="menu-link {{ request()->routeIs('exam.print-admit-cards') ? 'active' : '' }}">
                                    Print Admit Cards / Slip
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('exam.send-admit-cards') }}" class="menu-link {{ request()->routeIs('exam.send-admit-cards') ? 'active' : '' }}">
                                    Send Admit Cards via WA
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.grades*') ? 'active' : '' }}">
                                    Exam Grades
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('exam.grades.particular') }}" class="menu-link {{ request()->routeIs('exam.grades.particular') ? 'active' : '' }}">
                                            For Particular Exam
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('exam.grades.final') }}" class="menu-link {{ request()->routeIs('exam.grades.final') ? 'active' : '' }}">
                                            For Final Exam
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.teacher-remarks*') ? 'active' : '' }}">
                                    Teacher Remarks
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('exam.teacher-remarks.particular') }}" class="menu-link {{ request()->routeIs('exam.teacher-remarks.particular') ? 'active' : '' }}">
                                            For Particular Exam
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('exam.teacher-remarks.final') }}" class="menu-link {{ request()->routeIs('exam.teacher-remarks.final') ? 'active' : '' }}">
                                            For Final Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.timetable*') ? 'active' : '' }}">
                                    Exam Timetable
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('exam.timetable.add') }}" class="menu-link {{ request()->routeIs('exam.timetable.add') ? 'active' : '' }}">
                                            Add Timetable
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('exam.timetable.manage') }}" class="menu-link {{ request()->routeIs('exam.timetable.manage') ? 'active' : '' }}">
                                            Manage Timetable
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.tabulation-sheet*') ? 'active' : '' }}">
                                    Tabulation Sheet
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('exam.tabulation-sheet.particular') }}" class="menu-link {{ request()->routeIs('exam.tabulation-sheet.particular') ? 'active' : '' }}">
                                            For Particular Exam
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('exam.tabulation-sheet.final') }}" class="menu-link {{ request()->routeIs('exam.tabulation-sheet.final') ? 'active' : '' }}">
                                            For Final Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.position-holders*') ? 'active' : '' }}">
                                    Position Holders
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('exam.position-holders.particular') }}" class="menu-link {{ request()->routeIs('exam.position-holders.particular') ? 'active' : '' }}">
                                            For Particular Exam
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('exam.position-holders.final') }}" class="menu-link {{ request()->routeIs('exam.position-holders.final') ? 'active' : '' }}">
                                            For Final Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.send-marks*') ? 'active' : '' }}">
                                    Send Marks to Parents
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('exam.send-marks.particular') }}" class="menu-link {{ request()->routeIs('exam.send-marks.particular') ? 'active' : '' }}">
                                            For Particular Exam
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('exam.send-marks.final') }}" class="menu-link {{ request()->routeIs('exam.send-marks.final') ? 'active' : '' }}">
                                            For Final Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.print-marksheet*') ? 'active' : '' }}">
                                    Print Marksheet
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('exam.print-marksheet.particular') }}" class="menu-link {{ request()->routeIs('exam.print-marksheet.particular') ? 'active' : '' }}">
                                            For Particular Exam
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('exam.print-marksheet.final') }}" class="menu-link {{ request()->routeIs('exam.print-marksheet.final') ? 'active' : '' }}">
                                            For Final Result
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('quiz*') ? 'active' : '' }}">
                            Quiz Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('quiz.manage') }}" class="menu-link {{ request()->routeIs('quiz.manage') ? 'active' : '' }}">
                                    Manage Quizzes
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('certification*') ? 'active' : '' }}">
                            Certifications
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('certification.student') }}" class="menu-link {{ request()->routeIs('certification.student') ? 'active' : '' }}">
                                    Student Certification
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('certification.staff') }}" class="menu-link {{ request()->routeIs('certification.staff') ? 'active' : '' }}">
                                    Staff Certification
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('homework-diary*') ? 'active' : '' }}">
                            Daily Homework Diary
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('homework-diary.manage') }}" class="menu-link {{ request()->routeIs('homework-diary.manage') ? 'active' : '' }}">
                                    Add & Manage Diaries
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('homework-diary.send-sms') }}" class="menu-link {{ request()->routeIs('homework-diary.send-sms') ? 'active' : '' }}">
                                    Send Diary via SMS
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('study-material.lms') }}" class="menu-link {{ request()->routeIs('study-material.lms') ? 'active' : '' }}">
                            Study Material - LMS
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('leave-management') }}" class="menu-link {{ request()->routeIs('leave-management') ? 'active' : '' }}">
                            Leave Management
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('sms*') ? 'active' : '' }}">
                            SMS Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('sms.parent') }}" class="menu-link {{ request()->routeIs('sms.parent') ? 'active' : '' }}">
                                    SMS to Parent
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('sms.staff') }}" class="menu-link {{ request()->routeIs('sms.staff') ? 'active' : '' }}">
                                    SMS to Staff
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('sms.specific-number') }}" class="menu-link {{ request()->routeIs('sms.specific-number') ? 'active' : '' }}">
                                    SMS to Specific Number
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('sms.history') }}" class="menu-link {{ request()->routeIs('sms.history') ? 'active' : '' }}">
                                    Send SMS History
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('notification*') ? 'active' : '' }}">
                            Mobile App Notification
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('notification.parent') }}" class="menu-link {{ request()->routeIs('notification.parent') ? 'active' : '' }}">
                                    Notification to Parents
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('notification.staff') }}" class="menu-link {{ request()->routeIs('notification.staff') ? 'active' : '' }}">
                                    Notification to Staff
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('notification.student') }}" class="menu-link {{ request()->routeIs('notification.student') ? 'active' : '' }}">
                                    Notification to Student
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('notification.history') }}" class="menu-link {{ request()->routeIs('notification.history') ? 'active' : '' }}">
                                    Send Notification History
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('whatsapp*') ? 'active' : '' }}">
                            WhatsApp Notification
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('whatsapp.parent') }}" class="menu-link {{ request()->routeIs('whatsapp.parent') ? 'active' : '' }}">
                                    Message to Parents
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('whatsapp.staff') }}" class="menu-link {{ request()->routeIs('whatsapp.staff') ? 'active' : '' }}">
                                    Message to Staff
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('whatsapp.history') }}" class="menu-link {{ request()->routeIs('whatsapp.history') ? 'active' : '' }}">
                                    Send Message History
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('whatsapp.template') }}" class="menu-link {{ request()->routeIs('whatsapp.template') ? 'active' : '' }}">
                            Send/WhatsApp Template
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('email-alerts*') ? 'active' : '' }}">
                            Email Alerts
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('email-alerts.specific') }}" class="menu-link {{ request()->routeIs('email-alerts.specific') ? 'active' : '' }}">
                                    Message to Specific Email
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('email-alerts.history') }}" class="menu-link {{ request()->routeIs('email-alerts.history') ? 'active' : '' }}">
                                    Send Email History
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('school.noticeboard') }}" class="menu-link {{ request()->routeIs('school.noticeboard') ? 'active' : '' }}">
                            School Noticeboard
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('manage.campuses') }}" class="menu-link {{ request()->routeIs('manage.campuses') ? 'active' : '' }}">
                            Manage Campuses
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('admin.roles-management') }}" class="menu-link {{ request()->routeIs('admin.roles-management') ? 'active' : '' }}">
                            Admin Roles Management
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('transport*') ? 'active' : '' }}">
                            Transport
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('transport.manage') }}" class="menu-link {{ request()->routeIs('transport.manage') ? 'active' : '' }}">
                                    Manage Transport
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('transport.reports') }}" class="menu-link {{ request()->routeIs('transport.reports') ? 'active' : '' }}">
                                    Transport Reports
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('website-management*') ? 'active' : '' }}">
                            Website Management
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('website-management.general-gallery') }}" class="menu-link {{ request()->routeIs('website-management.general-gallery') ? 'active' : '' }}">
                                    General & Gallery Setting
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('website-management.classes-show') }}" class="menu-link {{ request()->routeIs('website-management.classes-show') ? 'active' : '' }}">
                                    Classes to Show
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('account-settings') || request()->routeIs('change-password') || request()->routeIs('connections') || request()->routeIs('privacy-policy') || request()->routeIs('terms-conditions') ? 'active' : '' }}">
                            Settings
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('account-settings') }}" class="menu-link {{ request()->routeIs('account-settings') ? 'active' : '' }}">
                                    Account Settings
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('change-password') }}" class="menu-link {{ request()->routeIs('change-password') ? 'active' : '' }}">
                                    Change Password
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('connections') }}" class="menu-link {{ request()->routeIs('connections') ? 'active' : '' }}">
                                    Connections
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('privacy-policy') }}" class="menu-link {{ request()->routeIs('privacy-policy') ? 'active' : '' }}">
                                    Privacy Policy
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('terms-conditions') }}" class="menu-link {{ request()->routeIs('terms-conditions') ? 'active' : '' }}">
                                    Terms & Conditions
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="menu-link">
                            <span class="material-symbols-outlined menu-icon">logout</span>
                            <span class="title">Logout</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </aside>
</div>
<!-- End Sidebar Area -->

<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
</form>
