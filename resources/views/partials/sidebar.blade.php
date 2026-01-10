<!-- Start Sidebar Area -->
<div class="sidebar-area" id="sidebar-area">
    <div class="logo position-relative d-flex align-items-center justify-content-between">
        <a href="{{ route('dashboard') }}" class="d-block text-decoration-none position-relative">
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
            @php
                $isStaff = Auth::guard('staff')->check();
                $isAdmin = Auth::guard('admin')->check();

                $staffUnreadChatCount = 0;
                $adminUnreadChatCount = 0;

                if ($isStaff) {
                    $staffUser = Auth::guard('staff')->user();
                    if ($staffUser) {
                        $staffUnreadChatCount = \App\Models\Message::where('to_type', 'teacher')
                            ->where('to_id', $staffUser->id)
                            ->whereNull('read_at')
                            ->count();
                    }
                }

                if ($isAdmin) {
                    $adminUser = Auth::guard('admin')->user();
                    if ($adminUser) {
                        $adminUnreadChatCount = \App\Models\Message::where('to_type', 'admin')
                            ->where('to_id', $adminUser->id)
                            ->whereNull('read_at')
                            ->count();
                    }
                }
            @endphp
            
            @if($isStaff)
                {{-- Staff Menu (Limited Access) --}}
                <li class="menu-title small text-uppercase">
                    <span class="menu-title-text">MAIN</span>
                </li>
                
                {{-- Dashboard --}}
                <li class="menu-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('staff.dashboard') }}" class="menu-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">dashboard</span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                
                {{-- Student List --}}
                <li class="menu-item {{ request()->routeIs('student-list') ? 'active' : '' }}">
                    <a href="{{ route('student-list') }}" class="menu-link {{ request()->routeIs('student-list') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">people</span>
                        <span class="title">Student List</span>
                    </a>
                </li>
                
                {{-- Manage Attendance --}}
                <li class="menu-item {{ request()->routeIs('attendance.student') ? 'active' : '' }}">
                    <a href="{{ route('attendance.student') }}" class="menu-link {{ request()->routeIs('attendance.student') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">event_available</span>
                        <span class="title">Manage Attendance</span>
                    </a>
                </li>
                
                {{-- Manage Student Behavior --}}
                <li class="menu-item {{ request()->routeIs('student-behavior.recording') ? 'active' : '' }}">
                    <a href="{{ route('student-behavior.recording') }}" class="menu-link {{ request()->routeIs('student-behavior.recording') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">psychology</span>
                        <span class="title">Manage Student Behavior</span>
                    </a>
                </li>
                    
                {{-- Test Management --}}
                <li class="menu-item {{ request()->routeIs('test*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">quiz</span>
                        <span class="title">Test Management</span>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="{{ route('test.list') }}" class="menu-link {{ request()->routeIs('test.list') ? 'active' : '' }}">
                                Create a Test
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('test.marks-entry') }}" class="menu-link {{ request()->routeIs('test.marks-entry') ? 'active' : '' }}">
                                Marks Entry
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('test.teacher-remarks*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.teacher-remarks*') ? 'active' : '' }}">
                                Test Remarks
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item">
                                    <a href="{{ route('test.teacher-remarks.practical') }}" class="menu-link {{ request()->routeIs('test.teacher-remarks.practical') ? 'active' : '' }}">
                                        For Particular Test
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="{{ route('test.teacher-remarks.combined') }}" class="menu-link {{ request()->routeIs('test.teacher-remarks.combined') ? 'active' : '' }}">
                                        For Combine Test
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                
                {{-- Exam Management --}}
                <li class="menu-item {{ request()->routeIs('exam*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">assignment</span>
                        <span class="title">Exam Management</span>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="{{ route('exam.marks-entry') }}" class="menu-link {{ request()->routeIs('exam.marks-entry') ? 'active' : '' }}">
                                Marks Entry
                            </a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('exam.teacher-remarks*') ? 'open' : '' }}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.teacher-remarks*') ? 'active' : '' }}">
                                <span class="material-symbols-outlined menu-icon">comment</span>
                                <span class="title">Teacher Remarks</span>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item">
                                    <a href="{{ route('exam.teacher-remarks.particular') }}" class="menu-link {{ request()->routeIs('exam.teacher-remarks.particular') ? 'active' : '' }}">
                                        For Particular Test
                                    </a>
                                </li>
                                <li class="menu-item">
                                    <a href="{{ route('exam.teacher-remarks.final') }}" class="menu-link {{ request()->routeIs('exam.teacher-remarks.final') ? 'active' : '' }}">
                                        For Combine Test
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                
                {{-- Task Management --}}
                <li class="menu-item {{ request()->routeIs('task-management*') ? 'active' : '' }}">
                    <a href="{{ route('task-management') }}" class="menu-link {{ request()->routeIs('task-management*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">task</span>
                        <span class="title">Task Management</span>
                    </a>
                </li>
                
                {{-- Study Materials - Lectures --}}
                <li class="menu-item {{ request()->routeIs('study-material*') ? 'active' : '' }}">
                    <a href="{{ route('study-material.lms') }}" class="menu-link {{ request()->routeIs('study-material*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">menu_book</span>
                        <span class="title">Study Materials - Lectures</span>
                    </a>
                </li>
                
                {{-- Daily Diary --}}
                <li class="menu-item {{ request()->routeIs('homework-diary*') ? 'active' : '' }}">
                    <a href="{{ route('homework-diary.manage') }}" class="menu-link {{ request()->routeIs('homework-diary*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">book</span>
                        <span class="title">Daily Diary</span>
                    </a>
                </li>
                
                {{-- Online Class --}}
                <li class="menu-item {{ request()->routeIs('online-classes*') ? 'active' : '' }}">
                    <a href="{{ route('online-classes') }}" class="menu-link {{ request()->routeIs('online-classes*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">video_call</span>
                        <span class="title">Online Class</span>
                    </a>
                </li>

                {{-- Live Chat --}}
                <li class="menu-item {{ request()->routeIs('staff.chat*') ? 'active' : '' }}">
                    <a href="{{ route('staff.chat') }}" class="menu-link {{ request()->routeIs('staff.chat*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">chat</span>
                        <span class="title">Live Chat</span>
                        @if($staffUnreadChatCount > 0)
                            <span class="badge bg-danger ms-auto" style="font-size: 11px; min-width: 20px;">{{ $staffUnreadChatCount }}</span>
                        @endif
                    </a>
                </li>
                
                {{-- Attendance Report --}}
                <li class="menu-item {{ request()->routeIs('attendance.report') ? 'active' : '' }}">
                    <a href="{{ route('attendance.report') }}" class="menu-link {{ request()->routeIs('attendance.report') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">assessment</span>
                        <span class="title">Attendance Report</span>
                    </a>
                </li>
                
                {{-- Academic/Holiday Calendar --}}
                <li class="menu-item {{ request()->routeIs('academic-calendar*') ? 'active' : '' }}">
                    <a href="{{ route('academic-calendar.view') }}" class="menu-link {{ request()->routeIs('academic-calendar*') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">calendar_month</span>
                        <span class="title">Academic/Holiday Calendar</span>
                    </a>
                </li>
                
                {{-- School Noticeboard --}}
                <li class="menu-item {{ request()->routeIs('school.noticeboard') ? 'active' : '' }}">
                    <a href="{{ route('school.noticeboard') }}" class="menu-link {{ request()->routeIs('school.noticeboard') ? 'active' : '' }}">
                        <span class="material-symbols-outlined menu-icon">campaign</span>
                        <span class="title">School Noticeboard</span>
                    </a>
                </li>
            @else
                {{-- Admin Menu (Full Access) --}}
                <li class="menu-title small text-uppercase">
                    <span class="menu-title-text">MAIN</span>
                </li>
                   <li class="menu-item {{ request()->routeIs('dashboard*') || request()->routeIs('admission*') || (request()->routeIs('student*') && !request()->routeIs('student-behavior*')) || request()->routeIs('parent*') || request()->routeIs('staff*') || request()->routeIs('id-card*') || request()->routeIs('accountants') || request()->routeIs('classes*') || request()->routeIs('attendance*') || request()->routeIs('timetable*') || request()->routeIs('academic-calendar*') || request()->routeIs('accounting*') || request()->routeIs('reports*') || request()->routeIs('stock*') || request()->routeIs('inventory*') || request()->routeIs('student-behavior*') || request()->routeIs('test*') || request()->routeIs('exam*') || request()->routeIs('quiz*') || request()->routeIs('certification*') || request()->routeIs('homework-diary*') || request()->routeIs('study-material*') || request()->routeIs('leave-management*') || request()->routeIs('sms*') || request()->routeIs('notification*') || request()->routeIs('whatsapp*') || request()->routeIs('robobuddy*') || request()->routeIs('email-alerts*') || request()->routeIs('school.noticeboard') || request()->routeIs('manage.campuses') || request()->routeIs('admin.roles-management') || request()->routeIs('transport*') || request()->routeIs('website-management*') || request()->routeIs('account-settings') || request()->routeIs('connections') || request()->routeIs('privacy-policy') || request()->routeIs('terms-conditions') || request()->routeIs('thermal-printer*') || request()->routeIs('change-password*') || request()->routeIs('live-chat') || request()->routeIs('task-management*') || request()->routeIs('manage-subjects*') || request()->routeIs('online-classes*') || request()->routeIs('fee-payment*') || request()->routeIs('expense-management*') || request()->routeIs('salary-loan*') ? 'open' : '' }}">
                       <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('dashboard*') || request()->routeIs('admission*') || (request()->routeIs('student*') && !request()->routeIs('student-behavior*')) || request()->routeIs('parent*') || request()->routeIs('staff*') || request()->routeIs('id-card*') || request()->routeIs('accountants') || request()->routeIs('classes*') || request()->routeIs('attendance*') || request()->routeIs('timetable*') || request()->routeIs('academic-calendar*') || request()->routeIs('accounting*') || request()->routeIs('reports*') || request()->routeIs('stock*') || request()->routeIs('inventory*') || request()->routeIs('student-behavior*') || request()->routeIs('test*') || request()->routeIs('exam*') || request()->routeIs('quiz*') || request()->routeIs('certification*') || request()->routeIs('homework-diary*') || request()->routeIs('study-material*') || request()->routeIs('leave-management*') || request()->routeIs('sms*') || request()->routeIs('notification*') || request()->routeIs('whatsapp*') || request()->routeIs('robobuddy*') || request()->routeIs('email-alerts*') || request()->routeIs('school.noticeboard') || request()->routeIs('manage.campuses') || request()->routeIs('admin.roles-management') || request()->routeIs('transport*') || request()->routeIs('website-management*') || request()->routeIs('account-settings') || request()->routeIs('connections') || request()->routeIs('privacy-policy') || request()->routeIs('terms-conditions') || request()->routeIs('thermal-printer*') || request()->routeIs('change-password*') || request()->routeIs('live-chat') || request()->routeIs('task-management*') || request()->routeIs('manage-subjects*') || request()->routeIs('online-classes*') || request()->routeIs('fee-payment*') || request()->routeIs('expense-management*') || request()->routeIs('salary-loan*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">dashboard</span>
                    <span class="title">Dashboard</span>
                </a>
            
                <ul class="menu-sub">
                    <li class="menu-item admission-management-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle admission-management-link {{ request()->routeIs('admission*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">how_to_reg</span>
                            <span class="title">Admission Management</span>
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
                                    <span class="material-symbols-outlined menu-icon">contact_support</span>
                                    <span class="title">Admission Inquiry</span>
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
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ (request()->routeIs('student*') && !request()->routeIs('student-behavior*')) ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">people</span>
                            <span class="title">Student Management</span>
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
                            <span class="material-symbols-outlined menu-icon">family_restroom</span>
                            <span class="title">Parent Account</span>
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
                            <span class="material-symbols-outlined menu-icon">groups</span>
                            <span class="title">Staff Management</span>
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
                            <span class="material-symbols-outlined menu-icon">task</span>
                            <span class="title">Task Management</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('id-card*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">badge</span>
                            <span class="title">ID Card Printing</span>
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
                            <span class="material-symbols-outlined menu-icon">account_circle</span>
                            <span class="title">Accountants</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('accounting*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">account_balance_wallet</span>
                            <span class="title">Accounting</span>
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
                                    <span class="material-symbols-outlined menu-icon">account_balance_wallet</span>
                                    <span class="title">Parent Wallet System</span>
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
                                    <span class="material-symbols-outlined menu-icon">payments</span>
                                    <span class="title">Direct Payment</span>
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
                                    <span class="material-symbols-outlined menu-icon">trending_up</span>
                                    <span class="title">Generate Fee Increment</span>
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
                                    <span class="material-symbols-outlined menu-icon">description</span>
                                    <span class="title">Generate Fee Document</span>
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
                                    <span class="material-symbols-outlined menu-icon">receipt</span>
                                    <span class="title">Print Fee Voucher</span>
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
                            <span class="material-symbols-outlined menu-icon">feedback</span>
                            <span class="title">Parent Complain</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('classes*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">class</span>
                            <span class="title">Classes and Section</span>
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
                            <span class="material-symbols-outlined menu-icon">subject</span>
                            <span class="title">Manage Subjects</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('attendance*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">event_available</span>
                            <span class="title">Manage Attendance</span>
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
                        <a href="{{ route('online-classes') }}" class="menu-link {{ request()->routeIs('online-classes*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">video_call</span>
                            <span class="title">Online Classes</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('timetable*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">schedule</span>
                            <span class="title">Timetable Management</span>
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
                            <span class="material-symbols-outlined menu-icon">calendar_month</span>
                            <span class="title">Academic Holiday Calendar</span>
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
                        <a href="{{ route('fee-payment') }}" class="menu-link {{ request()->routeIs('fee-payment') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">payment</span>
                            <span class="title">Fee Payment</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('expense-management*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">receipt_long</span>
                            <span class="title">Expense Management</span>
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
                            <span class="material-symbols-outlined menu-icon">payments</span>
                            <span class="title">Salary and Loan Management</span>
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
                                    <span class="material-symbols-outlined menu-icon">trending_up</span>
                                    <span class="title">Generate Salary Increment</span>
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
                                    <span class="material-symbols-outlined menu-icon">trending_down</span>
                                    <span class="title">Generate Salary Decrement</span>
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
                            <span class="material-symbols-outlined menu-icon">assessment</span>
                            <span class="title">Reporting</span>
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
                            <span class="material-symbols-outlined menu-icon">inventory_2</span>
                            <span class="title">Stock & Inventory</span>
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
                            <span class="material-symbols-outlined menu-icon">psychology</span>
                            <span class="title">Manage Student Behavior</span>
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
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">quiz</span>
                            <span class="title">Test Management</span>
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
                            <li class="menu-item {{ request()->routeIs('test.assign-grades*') ? 'open' : '' }}">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.assign-grades*') ? 'active' : '' }}">
                                    <span class="material-symbols-outlined menu-icon">grade</span>
                                    <span class="title">Assign Grades</span>
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
                            <li class="menu-item {{ request()->routeIs('test.teacher-remarks*') ? 'open' : '' }}">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.teacher-remarks*') ? 'active' : '' }}">
                                    <span class="material-symbols-outlined menu-icon">comment</span>
                                    <span class="title">Test Remarks</span>
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('test.teacher-remarks.practical') }}" class="menu-link {{ request()->routeIs('test.teacher-remarks.practical') ? 'active' : '' }}">
                                            For Particular Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('test.teacher-remarks.combined') }}" class="menu-link {{ request()->routeIs('test.teacher-remarks.combined') ? 'active' : '' }}">
                                            For Combine Test
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('test.tabulation-sheet*') ? 'active' : '' }}">
                                    <span class="material-symbols-outlined menu-icon">table_chart</span>
                                    <span class="title">Tabulation Sheet</span>
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
                                    <span class="material-symbols-outlined menu-icon">emoji_events</span>
                                    <span class="title">Position Holder</span>
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
                                    <span class="material-symbols-outlined menu-icon">send</span>
                                    <span class="title">Send Marks to Parents</span>
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
                                    <span class="material-symbols-outlined menu-icon">forward_to_inbox</span>
                                    <span class="title">Send Marksheet via WA</span>
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
                                    <span class="material-symbols-outlined menu-icon">print</span>
                                    <span class="title">Print Marksheets</span>
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
                            <span class="material-symbols-outlined menu-icon">assignment</span>
                            <span class="title">Exam Management</span>
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
                            <li class="menu-item {{ request()->routeIs('exam.teacher-remarks*') ? 'open' : '' }}">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.teacher-remarks*') ? 'active' : '' }}">
                                    <span class="material-symbols-outlined menu-icon">comment</span>
                                    <span class="title">Teacher Remarks</span>
                                </a>
                                <ul class="menu-sub">
                                    <li class="menu-item">
                                        <a href="{{ route('exam.teacher-remarks.particular') }}" class="menu-link {{ request()->routeIs('exam.teacher-remarks.particular') ? 'active' : '' }}">
                                            For Particular Test
                                        </a>
                                    </li>
                                    <li class="menu-item">
                                        <a href="{{ route('exam.teacher-remarks.final') }}" class="menu-link {{ request()->routeIs('exam.teacher-remarks.final') ? 'active' : '' }}">
                                            For Combine Test
                                        </a>
                                    </li>
                                </ul>
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
                                    <span class="material-symbols-outlined menu-icon">grade</span>
                                    <span class="title">Exam Grades</span>
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
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('exam.timetable*') ? 'active' : '' }}">
                                    <span class="material-symbols-outlined menu-icon">schedule</span>
                                    <span class="title">Exam Timetable</span>
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
                                    <span class="material-symbols-outlined menu-icon">table_chart</span>
                                    <span class="title">Tabulation Sheet</span>
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
                                    <span class="material-symbols-outlined menu-icon">emoji_events</span>
                                    <span class="title">Position Holders</span>
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
                                    <span class="material-symbols-outlined menu-icon">send</span>
                                    <span class="title">Send Marks to Parents</span>
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
                                    <span class="material-symbols-outlined menu-icon">print</span>
                                    <span class="title">Print Marksheet</span>
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
                            <span class="material-symbols-outlined menu-icon">help</span>
                            <span class="title">Quiz Management</span>
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
                            <span class="material-symbols-outlined menu-icon">workspace_premium</span>
                            <span class="title">Certifications</span>
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
                            <span class="material-symbols-outlined menu-icon">book</span>
                            <span class="title">Daily Homework Diary</span>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('homework-diary.manage') }}" class="menu-link {{ request()->routeIs('homework-diary.manage') ? 'active' : '' }}">
                                    Add & Manage Diaries
                                </a>
                            </li>
                            {{-- <li class="menu-item">
                                <a href="{{ route('homework-diary.send-sms') }}" class="menu-link {{ request()->routeIs('homework-diary.send-sms') ? 'active' : '' }}">
                                    Send Diary via SMS
                                </a>
                            </li> --}}
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('study-material.lms') }}" class="menu-link {{ request()->routeIs('study-material.lms') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">menu_book</span>
                            <span class="title">Study Material - LMS</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('leave-management') }}" class="menu-link {{ request()->routeIs('leave-management') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">event_busy</span>
                            <span class="title">Leave Management</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('sms*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">sms</span>
                            <span class="title">SMS Management</span>
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
                            <span class="material-symbols-outlined menu-icon">notifications</span>
                            <span class="title">Mobile App Notification</span>
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
                            <span class="material-symbols-outlined menu-icon">chat</span>
                            <span class="title">WhatsApp Notification</span>
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
                            <span class="material-symbols-outlined menu-icon">description</span>
                            <span class="title">Send/WhatsApp Template</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('email-alerts*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">mail</span>
                            <span class="title">Email Alerts</span>
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
                            <span class="material-symbols-outlined menu-icon">campaign</span>
                            <span class="title">School Noticeboard</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('manage.campuses') }}" class="menu-link {{ request()->routeIs('manage.campuses') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">location_city</span>
                            <span class="title">Manage Campuses</span>
                        </a>
                    </li>
                    @if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->isSuperAdmin())
                    <li class="menu-item">
                        <a href="{{ route('admin.roles-management') }}" class="menu-link {{ request()->routeIs('admin.roles-management') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">admin_panel_settings</span>
                            <span class="title">Admin Roles Management</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('transport*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">directions_bus</span>
                            <span class="title">Transport</span>
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
                            <span class="material-symbols-outlined menu-icon">language</span>
                            <span class="title">Website Management</span>
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
                        <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('thermal-printer*') || request()->routeIs('settings.*') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">settings</span>
                            <span class="title">Settings</span>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item">
                                <a href="{{ route('settings.general') }}" class="menu-link {{ request()->routeIs('settings.general') ? 'active' : '' }}">
                                    General Setting
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('settings.automation') }}" class="menu-link {{ request()->routeIs('settings.automation') ? 'active' : '' }}">
                                    Automation Setting
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('settings.sms') }}" class="menu-link {{ request()->routeIs('settings.sms') ? 'active' : '' }}">
                                    SMS Setting
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('settings.email') }}" class="menu-link {{ request()->routeIs('settings.email') ? 'active' : '' }}">
                                    Email Setting
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('settings.payment') }}" class="menu-link {{ request()->routeIs('settings.payment') ? 'active' : '' }}">
                                    Payment Setting
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('settings.exam') }}" class="menu-link {{ request()->routeIs('settings.exam') ? 'active' : '' }}">
                                    Exam Setting
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ route('thermal-printer.setting') }}" class="menu-link {{ request()->routeIs('thermal-printer*') ? 'active' : '' }}">
                                    Thermal Printer Setting
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('live-chat') }}" class="menu-link {{ request()->routeIs('live-chat') ? 'active' : '' }}">
                            <span class="material-symbols-outlined menu-icon">chat</span>
                            <span class="title">Live Chat</span>
                            @if($adminUnreadChatCount > 0)
                                <span class="badge bg-danger ms-auto" style="font-size: 11px; min-width: 20px;">{{ $adminUnreadChatCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif
            
            {{-- IMPORTANT LINKS Section --}}
            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">IMPORTANT LINKS</span>
            </li>
            <li class="menu-item">
                <a href="{{ route('change-password') }}" class="menu-link {{ request()->routeIs('change-password*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">lock</span>
                    <span class="title">Change Password</span>
                </a>
            </li>
            
            {{-- Logout Button (for both Admin and Staff) --}}
            <li class="menu-item">
                <a href="#" onclick="event.preventDefault(); handleSidebarLogout();" class="menu-link">
                    <span class="material-symbols-outlined menu-icon">logout</span>
                    <span class="title">Logout</span>
                </a>
            </li>
        </ul>
    </aside>
</div>
<!-- End Sidebar Area -->

@if(Auth::guard('admin')->check())
    <form id="admin-logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
@endif
@if(Auth::guard('staff')->check())
    <form id="staff-logout-form" action="{{ route('staff.logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
@endif
@if(Auth::guard('web')->check())
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
@endif

<script>
function handleSidebarLogout() {
    // Check for admin logout form first
    var adminForm = document.getElementById('admin-logout-form');
    if (adminForm) {
        adminForm.submit();
        return;
    }
    
    // Check for staff logout form
    var staffForm = document.getElementById('staff-logout-form');
    if (staffForm) {
        staffForm.submit();
        return;
    }
    
    // Check for regular logout form
    var logoutForm = document.getElementById('logout-form');
    if (logoutForm) {
        logoutForm.submit();
        return;
    }
    
    // If no form found, try to redirect
    console.log('No logout form found');
}

// Keep Dashboard dropdown open when Task Management is active
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on Task Management page
    const isTaskManagement = window.location.pathname.includes('/task-management');
    
    if (isTaskManagement) {
        // Find Dashboard menu item (the one with menu-toggle that contains Task Management)
        const dashboardMenuItems = document.querySelectorAll('.menu-item');
        let dashboardMenuItem = null;
        
        // Find the Dashboard menu item that contains Task Management
        dashboardMenuItems.forEach(function(item) {
            const toggle = item.querySelector('a.menu-toggle');
            if (toggle && toggle.textContent.includes('Dashboard')) {
                const menuSub = item.querySelector('.menu-sub');
                if (menuSub) {
                    const taskLink = menuSub.querySelector('a[href*="task-management"]');
                    if (taskLink) {
                        dashboardMenuItem = item;
                    }
                }
            }
        });
        
        if (dashboardMenuItem) {
            // Ensure Dashboard dropdown is open
            if (!dashboardMenuItem.classList.contains('open')) {
                dashboardMenuItem.classList.add('open');
            }
            
            // Also try using the menu API if available
            setTimeout(function() {
                const dashboardToggle = dashboardMenuItem.querySelector('a.menu-toggle');
                if (dashboardToggle && window.Helpers && window.Helpers.mainMenu) {
                    try {
                        window.Helpers.mainMenu.open(dashboardToggle, false);
                    } catch(e) {
                        console.log('Menu API not available, using class toggle');
                    }
                }
            }, 100);
        }
    }
    
    // Prevent Dashboard dropdown from closing when Task Management link is clicked
    const taskManagementLinks = document.querySelectorAll('a[href*="task-management"]');
    taskManagementLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Find parent Dashboard menu item
            let parent = link.closest('.menu-item');
            while (parent) {
                const toggle = parent.querySelector('a.menu-toggle');
                if (toggle && toggle.textContent.includes('Dashboard')) {
                    // Keep it open
                    parent.classList.add('open');
                    break;
                }
                parent = parent.parentElement?.closest('.menu-item');
            }
        });
    });
});
</script>

<style>
/* Remove dots from all sub-menu items */
.menu-vertical .menu-sub .menu-item .menu-link::before {
    display: none !important;
    content: none !important;
}
</style>


