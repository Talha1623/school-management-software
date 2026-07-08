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
                                        <a href="javascript:void(0);" class="dropdown-item">
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
                                    <div class="notification-menu">
                                        <a href="javascript:void(0);" class="dropdown-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('assets/images/australia.png') }}" class="wh-30 rounded-circle" alt="australia">
                                                </div>
                                                <div class="flex-grow-1 ms-10">
                                                    <span class="text-secondary fw-medium fs-15">Australia</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="notification-menu">
                                        <a href="javascript:void(0);" class="dropdown-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('assets/images/spain.png') }}" class="wh-30 rounded-circle" alt="spain">
                                                </div>
                                                <div class="flex-grow-1 ms-10">
                                                    <span class="text-secondary fw-medium fs-15">Spanish</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="notification-menu">
                                        <a href="javascript:void(0);" class="dropdown-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('assets/images/france.png') }}" class="wh-30 rounded-circle" alt="portugal">
                                                </div>
                                                <div class="flex-grow-1 ms-10">
                                                    <span class="text-secondary fw-medium fs-15">France</span>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="notification-menu mb-0">
                                        <a href="javascript:void(0);" class="dropdown-item">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('assets/images/germany.png') }}" class="wh-30 rounded-circle" alt="Germany">
                                                </div>
                                                <div class="flex-grow-1 ms-10">
                                                    <span class="text-secondary fw-medium fs-15">Germany</span>
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
                    <li class="header-right-item calendar-item">
                        <div class="dropdown notifications">
                            <a href="{{ route('calendar') }}" class="btn btn-secondary border-0 p-0 position-relative">
                                <span class="material-symbols-outlined" style="font-size: 19px;">calendar_today</span>
                            </a>
                        </div>
                    </li>
                    <li class="header-right-item messages-item">
                        <div class="dropdown notifications noti messages">
                            @php
                                $accountantMailUnread = 0;
                                $accountantMailMessages = collect();

                                if (Auth::guard('accountant')->check()) {
                                    $accountantUser = Auth::guard('accountant')->user();
                                    if ($accountantUser) {
                                        $accountantMailUnread = \App\Models\Message::where('from_type', 'accountant')
                                            ->where('to_type', 'accountant')
                                            ->where('to_id', $accountantUser->id)
                                            ->whereNull('read_at')
                                            ->count();

                                        $accountantMailMessages = \App\Models\Message::where('from_type', 'accountant')
                                            ->where('to_type', 'accountant')
                                            ->where('to_id', $accountantUser->id)
                                            ->whereNull('read_at')
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get()
                                            ->map(function ($message) {
                                                $accountant = \App\Models\Accountant::find($message->from_id);

                                                return [
                                                    'recipient_type' => 'accountant',
                                                    'recipient_id' => $message->from_id,
                                                    'sender_name' => $accountant?->name ?? 'Accountant',
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
                                @if($accountantMailUnread > 0)
                                    <span class="count bg-primary">{{ $accountantMailUnread > 99 ? '99+' : $accountantMailUnread }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-lg p-0 border-0 p-0 dropdown-menu-end">
                                <div class="d-flex justify-content-between align-items-center title">
                                    <span class="fw-medium fs-16 text-secondary">Messages <span class="fw-normal text-body fs-16">({{ $accountantMailUnread }})</span></span>
                                    @if($accountantMailUnread > 0)
                                        <form action="{{ route('accountant.notifications.mark-all-read') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="p-0 m-0 bg-transparent border-0 fs-15 text-primary fw-medium">Mark all as read</button>
                                        </form>
                                    @endif
                                </div>

                                <div style="max-height: 300px;" data-simplebar>
                                    @if($accountantMailMessages->count() > 0)
                                        @foreach($accountantMailMessages as $message)
                                            <div class="notification-menu unseen">
                                                <a href="{{ route('accountant.chat', ['recipient_type' => $message['recipient_type'], 'recipient_id' => $message['recipient_id']]) }}" class="dropdown-item">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <i class="material-symbols-outlined text-primary">mail</i>
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

                                <a href="{{ route('accountant.chat') }}" class="dropdown-item text-center text-primary d-block view-all fw-medium rounded-bottom-3">
                                    <span>See All Messages</span>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="header-right-item">
                        <div class="dropdown notifications noti">
                            @php
                                $accountantNotifUnread = 0;
                                $accountantNotifications = collect();

                                if (Auth::guard('accountant')->check()) {
                                    $accountantUser = Auth::guard('accountant')->user();
                                    if ($accountantUser) {
                                        $accountantIncomingFromTypes = ['admin', 'super_admin', 'staff_notification', 'accountant'];

                                        $accountantNotifUnread = \App\Models\Message::whereIn('from_type', $accountantIncomingFromTypes)
                                            ->where('to_type', 'accountant')
                                            ->where('to_id', $accountantUser->id)
                                            ->whereNull('read_at')
                                            ->count();

                                        $accountantNotifications = \App\Models\Message::whereIn('from_type', $accountantIncomingFromTypes)
                                            ->where('to_type', 'accountant')
                                            ->where('to_id', $accountantUser->id)
                                            ->whereNull('read_at')
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get()
                                            ->map(function ($message) {
                                                if (in_array($message->from_type, ['admin', 'super_admin', 'staff_notification'], true)) {
                                                    $admin = \App\Models\AdminRole::find($message->from_id);
                                                    $senderName = $admin?->name ?? 'Admin';
                                                    $recipientType = 'admin';
                                                    $recipientId = $message->from_id;
                                                } else {
                                                    $accountant = \App\Models\Accountant::find($message->from_id);
                                                    $senderName = $accountant?->name ?? 'Accountant';
                                                    $recipientType = 'accountant';
                                                    $recipientId = $message->from_id;
                                                }

                                                $messageText = strtolower((string) $message->text);
                                                if (str_contains($messageText, 'noticeboard')) {
                                                    $href = route('school.noticeboard');
                                                } elseif (str_contains($messageText, 'task')) {
                                                    $href = route('accountant.task-management');
                                                } else {
                                                    $href = route('accountant.chat', [
                                                        'recipient_type' => $recipientType,
                                                        'recipient_id' => $recipientId,
                                                    ]);
                                                }

                                                return [
                                                    'href' => $href,
                                                    'sender_name' => $senderName,
                                                    'text' => $message->text
                                                        ? (strlen($message->text) > 80 ? substr($message->text, 0, 80) . '...' : $message->text)
                                                        : 'Notification',
                                                    'time_ago' => $message->created_at->diffForHumans(),
                                                ];
                                            });
                                    }
                                }
                            @endphp
                            <button class="btn btn-secondary border-0 p-0 position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="material-symbols-outlined">notifications</span>
                                @if($accountantNotifUnread > 0)
                                    <span class="count">{{ $accountantNotifUnread > 99 ? '99+' : $accountantNotifUnread }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-lg p-0 border-0 p-0 dropdown-menu-end">
                                <div class="d-flex justify-content-between align-items-center title">
                                    <span class="fw-medium fs-16 text-secondary">Notifications <span class="fw-normal text-body fs-16">({{ $accountantNotifUnread }})</span></span>
                                    @if($accountantNotifUnread > 0)
                                        <form action="{{ route('accountant.notifications.mark-all-read') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="p-0 m-0 bg-transparent border-0 fs-15 text-primary fw-medium">Mark all as read</button>
                                        </form>
                                    @endif
                                </div>
                                <div style="max-height: 300px;" data-simplebar>
                                    @if($accountantNotifications->count() > 0)
                                        @foreach($accountantNotifications as $notification)
                                            <div class="notification-menu unseen">
                                                <a href="{{ $notification['href'] }}" class="dropdown-item">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <i class="material-symbols-outlined text-primary">notifications</i>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <p class="fs-16 fw-medium text-secondary mb-1">{{ $notification['sender_name'] }}</p>
                                                            <p class="fs-14 fw-normal text-body mb-1">{{ $notification['text'] }}</p>
                                                            <span class="fs-14 fw-medium text-muted">{{ $notification['time_ago'] }}</span>
                                                        </div>
                                                    </div>
                                                </a>
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
                                <a href="{{ route('accountant.chat') }}" class="dropdown-item text-center text-primary d-block view-all fw-medium rounded-bottom-3">
                                    <span>See All Notifications</span>
                                </a>
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
                                        @if(Auth::guard('accountant')->check())
                                            <h3 class="fw-medium fs-17 mb-0">{{ Auth::guard('accountant')->user()->name }}</h3>
                                            <span class="fs-15 fw-medium">Accountant</span>
                                        @endif
                                    </div>
                                </div>
                                <ul class="admin-link mb-0 list-unstyled">
                                    <li>
                                        <a class="dropdown-item admin-item-link d-flex align-items-center text-body" href="{{ route('accountant.dashboard') }}">
                                            <i class="material-symbols-outlined">person</i>
                                            <span class="ms-2">My Profile</span>
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

@if(Auth::guard('accountant')->check())
    <form id="accountant-logout-form" action="{{ route('accountant.logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
@endif

<script>
function handleLogout() {
    // Check for accountant logout form
    var accountantForm = document.getElementById('accountant-logout-form');
    if (accountantForm) {
        accountantForm.submit();
        return;
    }
}
</script>

