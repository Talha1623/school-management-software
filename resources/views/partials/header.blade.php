<!-- Start Header Area -->
<header class="header-area bg-white mb-4 rounded-10 border border-white" id="header-area">
    <div class="row align-items-center">
        <div class="col-md-6">
            <div class="left-header-content">
                <ul class="d-flex align-items-center ps-0 mb-0 list-unstyled justify-content-center justify-content-md-start">
                    <li class="d-xl-none">
                        <button class="header-burger-menu bg-transparent p-0 border-0 position-relative top-3" id="header-burger-menu">
                            <span class="border-1 d-block for-dark-burger" style="border-bottom: 1px solid #475569; height: 1px; width: 25px;"></span>
                            <span class="border-1 d-block for-dark-burger" style="border-bottom: 1px solid #475569; height: 1px; width: 25px; margin: 6px 0;"></span>
                            <span class="border-1 d-block for-dark-burger" style="border-bottom: 1px solid #475569; height: 1px; width: 25px;"></span>
                        </button>
                    </li>
                    <li>
                        <form class="src-form position-relative">
                            <input type="text" class="form-control" placeholder="Search here...">
                            <div class="src-btn position-absolute top-50 start-0 translate-middle-y bg-transparent p-0 border-0">
                                <span class="material-symbols-outlined">search</span>
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-6">
            <div class="right-header-content mt-3 mt-md-0">
                <ul class="d-flex align-items-center justify-content-center justify-content-md-end ps-0 mb-0 list-unstyled">
                    <li class="header-right-item language-item">
                        <div class="dropdown notifications language">
                            <button class="btn btn-secondary dropdown-toggle border-0 p-0 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="material-symbols-outlined" style="font-size: 19px;">translate</span>
                            </button>
                            <div class="dropdown-menu dropdown-lg p-0 border-0 dropdown-menu-end">
                                <span class="fw-medium fs-16 text-secondary d-block title" style="padding-top: 20px; padding-bottom: 20px;">Choose Language</span>
                                <div class="max-h-275" data-simplebar>
                                    <div class="notification-menu">
                                        <a href="{{ route('language.switch', ['locale' => 'en']) }}" class="dropdown-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('assets/images/usa.png') }}" class="wh-30 rounded-circle" alt="usa">
                                                </div>
                                                <div class="flex-grow-1 ms-10">
                                                    <span class="text-secondary fw-medium fs-15">English</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="notification-menu mb-0">
                                        <a href="{{ route('language.switch', ['locale' => 'ur']) }}" class="dropdown-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="wh-30 rounded-circle d-flex align-items-center justify-content-center fw-bold text-white" style="width: 30px; height: 30px; background-color: #01411C; font-size: 12px;">
                                                        اردو
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-10">
                                                    <span class="text-secondary fw-medium fs-15">Urdu</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="header-right-item light-dark-item">
                        <div class="light-dark">
                            <button class="switch-toggle dark-btn p-0 bg-transparent lh-0 border-0" id="switch-toggle">
                                <span class="dark"><i class="material-symbols-outlined">dark_mode</i></span> 
                                <span class="light"><i class="material-symbols-outlined">light_mode</i></span>
                            </button>
                        </div>
                    </li>
                    <li class="header-right-item messages-item">
                        <div class="dropdown notifications noti messages">
                            @php
                                $mailUnreadCount = 0;
                                $mailMessages = collect();

                                if (Auth::guard('admin')->check()) {
                                    $admin = Auth::guard('admin')->user();
                                    if ($admin) {
                                        $mailUnreadCount = \App\Models\Message::query()
                                            ->unreadChatToAdmin((int) $admin->id)
                                            ->count();

                                        $mailMessages = \App\Models\Message::query()
                                            ->unreadChatToAdmin((int) $admin->id)
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get()
                                            ->map(function ($message) {
                                                $name = 'Unknown';
                                                if ($message->from_type === 'teacher') {
                                                    $sender = \App\Models\Staff::find($message->from_id);
                                                    $name = $sender?->name ?? 'Teacher';
                                                } elseif ($message->from_type === 'student') {
                                                    $sender = \App\Models\Student::find($message->from_id);
                                                    $name = $sender?->student_name ?? 'Student';
                                                } elseif ($message->from_type === 'parent') {
                                                    $sender = \App\Models\ParentAccount::find($message->from_id);
                                                    $name = $sender?->name ?? 'Parent';
                                                } elseif ($message->from_type === 'accountant') {
                                                    $sender = \App\Models\Accountant::find($message->from_id);
                                                    $name = $sender?->name ?? 'Accountant';
                                                }

                                                return [
                                                    'from_type' => $message->from_type,
                                                    'from_id' => $message->from_id,
                                                    'sender_name' => $name,
                                                    'href' => $message->liveChatUrl(),
                                                    'text' => $message->text
                                                        ? (strlen($message->text) > 50 ? substr($message->text, 0, 50) . '...' : $message->text)
                                                        : 'Attachment sent',
                                                    'time_ago' => $message->created_at->diffForHumans(),
                                                ];
                                            });
                                    }
                                }
                            @endphp
                            <button class="btn btn-secondary border-0 p-0 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="material-symbols-outlined">mail</span>
                                @if($mailUnreadCount > 0)
                                    <span class="count bg-primary">{{ $mailUnreadCount > 99 ? '99+' : $mailUnreadCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-lg p-0 border-0 p-0 dropdown-menu-end">
                                <div class="d-flex justify-content-between align-items-center title">
                                    <span class="fw-medium fs-16 text-secondary">Messages <span class="fw-normal text-body fs-16">({{ $mailUnreadCount }})</span></span>
                                    @if($mailUnreadCount > 0)
                                        <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="p-0 m-0 bg-transparent border-0 fs-15 text-primary fw-medium">Mark all as read</button>
                                        </form>
                                    @endif
                                </div> 

                                <div style="max-height: 300px;" data-simplebar>
                                    @if($mailMessages->count() > 0)
                                        @foreach($mailMessages as $message)
                                            <div class="notification-menu unseen">
                                                <a href="{{ $message['href'] }}" class="dropdown-item">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <i class="material-symbols-outlined text-primary">sms</i>
                                                        </div>
                                                        <div class="flex-grow-1 ms-10">
                                                            <p class="fs-16 fw-medium text-secondary mb-2">
                                                                {{ $message['sender_name'] }}
                                                                <span class="fs-14 fw-normal text-body ms-2">{{ $message['time_ago'] }}</span>
                                                            </p>
                                                            <span class="fs-14-5 fw-medium d-inline-block" style="line-height: 1.4;">{{ $message['text'] }}</span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="notification-menu">
                                            <div class="dropdown-item text-center py-3">
                                                <span class="fs-14 text-muted">No new messages</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <a href="{{ route('live-chat') }}" class="dropdown-item text-center text-primary d-block view-all fw-medium rounded-bottom-3">
                                    <span>See All Messages</span>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="header-right-item">
                        <div class="dropdown notifications noti">
                            @php
                                $unreadChatCount = 0;
                                $recentChatMessages = collect();

                                $canMarkAdminNotifications = false;

                                if (Auth::guard('admin')->check()) {
                                    $admin = Auth::guard('admin')->user();
                                    if ($admin) {
                                        $canMarkAdminNotifications = true;
                                        $notificationSourceTypes = ['teacher', 'accountant', 'accountant_notification', 'staff_notification'];

                                        $unreadChatCount = \App\Models\Message::whereIn('from_type', $notificationSourceTypes)
                                            ->where('to_type', 'admin')
                                            ->where('to_id', $admin->id)
                                            ->whereNull('read_at')
                                            ->count();

                                        $recentChatMessages = \App\Models\Message::whereIn('from_type', $notificationSourceTypes)
                                            ->where('to_type', 'admin')
                                            ->where('to_id', $admin->id)
                                            ->whereNull('read_at')
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get()
                                            ->map(function ($message) {
                                                if ($message->from_type === 'accountant' || $message->from_type === 'accountant_notification') {
                                                    $sender = \App\Models\Accountant::find($message->from_id);
                                                    $senderName = $sender?->name ?? 'Accountant';
                                                    $recipientType = 'accountant';
                                                } elseif ($message->from_type === 'staff_notification') {
                                                    $sender = \App\Models\Staff::find($message->from_id);
                                                    $senderName = $sender?->name ?? 'Staff';
                                                    $recipientType = 'staff_notification';
                                                } else {
                                                    $sender = \App\Models\Staff::find($message->from_id);
                                                    $senderName = $sender?->name ?? 'Teacher';
                                                    $recipientType = 'teacher';
                                                }

                                                return [
                                                    'id' => $message->id,
                                                    'recipient_type' => $recipientType,
                                                    'recipient_id' => $message->from_id,
                                                    'sender_name' => $senderName,
                                                    'href' => in_array($message->from_type, ['accountant', 'accountant_notification'], true)
                                                        ? (str_contains(strtolower((string) $message->text), 'transport fee')
                                                            ? route('accounting.generate-transport-fee')
                                                            : (str_contains(strtolower((string) $message->text), 'fee payment')
                                                                ? route('fee-payment')
                                                                : (str_contains(strtolower((string) $message->text), 'task')
                                                                    ? route('task-management')
                                                                    : (str_contains(strtolower((string) $message->text), 'balance sheet settlement')
                                                                        ? route('reports.balance-sheet')
                                                                        : route('accounting.generate-custom-fee')))))
                                                        : route('live-chat', ['recipient_type' => $recipientType, 'recipient_id' => $message->from_id]),
                                                    'full_text' => $message->text ?: 'Attachment sent',
                                                    'text' => $message->text
                                                        ? (strlen($message->text) > 80 ? substr($message->text, 0, 80) . '...' : $message->text)
                                                        : 'Attachment sent',
                                                    'time_ago' => $message->created_at->diffForHumans(),
                                                ];
                                            });
                                    }
                                } elseif (Auth::guard('staff')->check()) {
                                    $staffUser = Auth::guard('staff')->user();
                                    if ($staffUser) {
                                        $unreadChatCount = \App\Models\Message::whereIn('from_type', ['admin', 'super_admin'])
                                            ->where('to_type', 'teacher')
                                            ->where('to_id', $staffUser->id)
                                            ->whereNull('read_at')
                                            ->count();

                                        $recentChatMessages = \App\Models\Message::whereIn('from_type', ['admin', 'super_admin'])
                                            ->where('to_type', 'teacher')
                                            ->where('to_id', $staffUser->id)
                                            ->whereNull('read_at')
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get()
                                            ->map(function ($message) {
                                                $admin = \App\Models\AdminRole::find($message->from_id);

                                                return [
                                                    'id' => $message->id,
                                                    'recipient_type' => 'admin',
                                                    'recipient_id' => $message->from_id,
                                                    'sender_name' => $admin?->name ?? 'Admin',
                                                    'href' => '#',
                                                    'full_text' => $message->text ?: 'Attachment sent',
                                                    'text' => $message->text
                                                        ? (strlen($message->text) > 80 ? substr($message->text, 0, 80) . '...' : $message->text)
                                                        : 'Attachment sent',
                                                    'time_ago' => $message->created_at->diffForHumans(),
                                                ];
                                            });
                                    }
                                }
                            @endphp
                            <button class="btn btn-secondary border-0 p-0 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="material-symbols-outlined">notifications</span>
                                @if($unreadChatCount > 0)
                                    <span class="count">{{ $unreadChatCount > 99 ? '99+' : $unreadChatCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-lg p-0 border-0 p-0 dropdown-menu-end">
                                <div class="d-flex justify-content-between align-items-center title">
                                    <span class="fw-medium fs-16 text-secondary">Notifications <span class="fw-normal text-body fs-16">({{ $unreadChatCount }})</span></span>
                                    @if($unreadChatCount > 0 && $canMarkAdminNotifications)
                                        <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="p-0 m-0 bg-transparent border-0 fs-15 text-primary fw-medium">Mark all as read</button>
                                        </form>
                                    @endif
                                </div> 
                                <div style="max-height: 300px;" data-simplebar>
                                    @if($recentChatMessages->count() > 0)
                                        @foreach($recentChatMessages as $message)
                                            <div class="notification-menu unseen">
                                                <div class="dropdown-item admin-notification-toggle" role="button" style="cursor: pointer;" onclick="toggleAdminNotificationText(this, event)">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <i class="material-symbols-outlined text-primary">notifications</i>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <p class="fs-16 fw-medium text-secondary mb-1">{{ $message['sender_name'] }}</p>
                                                            <p class="fs-14 fw-normal text-body mb-1 admin-notification-preview">{{ $message['text'] }}</p>
                                                            <p class="fs-14 fw-normal text-body mb-1 admin-notification-full d-none">{{ $message['full_text'] }}</p>
                                                            <span class="fs-14 fw-medium text-muted">{{ $message['time_ago'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="notification-menu">
                                            <div class="dropdown-item text-center py-3">
                                                <span class="fs-14 text-muted">No new notifications</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="header-right-item">
                        <div class="dropdown admin-profile">
                            <div class="d-xxl-flex align-items-center bg-transparent border-0 text-start p-0 cursor dropdown-toggle" data-bs-toggle="dropdown">
                                <div class="flex-shrink-0 position-relative">
                                    <img class="rounded-circle admin-img-width-for-mobile" style="width: 40px; height: 40px;" src="{{ asset('assets/images/admin.png') }}" alt="admin">
                                    <span class="d-block bg-success-60 border border-2 border-white rounded-circle position-absolute end-0 bottom-0" style="width: 11px; height: 11px;"></span>
                                </div>
                            </div>

                            <div class="dropdown-menu border-0 bg-white dropdown-menu-end">
                                <div class="d-flex align-items-center info">
                                    <div class="flex-shrink-0">
                                        <img class="rounded-circle admin-img-width-for-mobile" style="width: 40px; height: 40px;" src="{{ asset('assets/images/admin.png') }}" alt="admin">
                                    </div>
                                    <div class="flex-grow-1 ms-10">
                                        @if(Auth::guard('admin')->check())
                                            <h3 class="fw-medium fs-17 mb-0">{{ Auth::guard('admin')->user()->name }}</h3>
                                            <span class="fs-15 fw-medium">
                                                @if(Auth::guard('admin')->user()->isSuperAdmin())
                                                    Super Admin
                                                @else
                                                    Admin
                                                @endif
                                            </span>
                                        @elseif(Auth::guard('staff')->check())
                                            <h3 class="fw-medium fs-17 mb-0">{{ Auth::guard('staff')->user()->name }}</h3>
                                            <span class="fs-15 fw-medium">{{ Auth::guard('staff')->user()->designation ?? 'Staff' }}</span>
                                        @elseif(auth()->check())
                                            <h3 class="fw-medium fs-17 mb-0">{{ auth()->user()->name ?? 'User' }}</h3>
                                            <span class="fs-15 fw-medium">User</span>
                                        @else
                                            <h3 class="fw-medium fs-17 mb-0">Guest</h3>
                                            <span class="fs-15 fw-medium">Guest</span>
                                        @endif
                                    </div>
                                </div>
                                <ul class="admin-link mb-0 list-unstyled">
                                    <li>
                                        <a class="dropdown-item admin-item-link d-flex align-items-center text-body" href="{{ route('profile') }}">
                                            <i class="material-symbols-outlined">person</i>
                                            <span class="ms-2">My Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item admin-item-link d-flex align-items-center text-body" href="{{ route('settings') }}">
                                            <i class="material-symbols-outlined">settings</i>
                                            <span class="ms-2">Settings</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item admin-item-link d-flex align-items-center text-body" href="#" onclick="event.preventDefault(); handleLogout();">
                                            <i class="material-symbols-outlined">logout</i>
                                            <span class="ms-2">Logout</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
<!-- End Header Area -->

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
function toggleAdminNotificationText(element, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    var preview = element.querySelector('.admin-notification-preview');
    var full = element.querySelector('.admin-notification-full');
    if (!preview || !full) {
        return;
    }

    preview.classList.toggle('d-none');
    full.classList.toggle('d-none');
}

function handleLogout() {
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
}
</script>

