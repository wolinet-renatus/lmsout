@php
    $portalType = $portalType ?? (auth()->user()?->hasRole('instructor') ? 'instructor' : 'student');
    $user = auth()->user();
    $homeRoute = $portalType === 'instructor' ? route('instructor.dashboard') : route('student.dashboard');
    $notificationsRoute = $portalType === 'instructor' && \Illuminate\Support\Facades\Route::has('instructor.notifications.index')
        ? route('instructor.notifications.index')
        : (\Illuminate\Support\Facades\Route::has('student.notifications.index') ? route('student.notifications.index') : '#');
    $messagesRoute = $portalType === 'instructor' && \Illuminate\Support\Facades\Route::has('instructor.messages.index')
        ? route('instructor.messages.index')
        : (\Illuminate\Support\Facades\Route::has('student.messages.index') ? route('student.messages.index') : '#');
    $settingsRoute = $portalType === 'instructor' ? route('instructor.settings') : route('student.settings');
    $ticketsRoute = $portalType === 'instructor' ? route('instructor.tickets.index') : route('student.tickets.index');
    $userPhoto = $user?->profile_photo ? asset('storage/' . $user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png');
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
@endphp

<header class="out-portal-header">
    <div class="out-portal-header-left">
        <!-- Sidebar Drawer Toggle (Mobile & Desktop) -->
        <button type="button" class="out-portal-toggle-btn" id="outPortalSidebarToggle" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- OUT Brand Logo -->
        <a href="{{ $homeRoute }}" class="out-portal-brand">
            <img src="{{ branding_asset(['primary_logo_path', 'logos.primary'], 'assets/front/img/logo/logo.png') }}"
                 alt="{{ site_name() }} Logo"
                 onerror="this.src='{{ asset('assets/front/img/logo/brand-logo-v3.png') }}'">
        </a>

        <!-- Portal Role Pill Badge -->
        @if($portalType === 'instructor')
            <span class="out-portal-role-badge instructor d-none d-sm-inline-flex">
                <i class="fa-solid fa-chalkboard-user"></i> Instructor Studio
            </span>
        @else
            <span class="out-portal-role-badge student d-none d-sm-inline-flex">
                <i class="fa-solid fa-graduation-cap"></i> Student Campus
            </span>
        @endif
    </div>

    <div class="out-portal-header-right">
        <!-- Visit Public Website -->
        <a href="{{ route('home') }}" target="_blank" class="out-portal-visit-btn" title="View Public Website">
            <i class="fa-solid fa-globe"></i>
            <span class="d-none d-md-inline">Visit Website</span>
        </a>

        <!-- Messages Shortcut -->
        @if($messagesRoute !== '#')
            <a href="{{ $messagesRoute }}" class="out-portal-icon-btn d-none d-sm-inline-flex" title="Messages">
                <i class="fa-solid fa-envelope"></i>
                <span class="out-portal-icon-badge d-none {{ $portalType }}-chat-indicator"></span>
            </a>
        @endif

        <!-- Notifications Shortcut -->
        @if($notificationsRoute !== '#')
            <a href="{{ $notificationsRoute }}" class="out-portal-icon-btn d-none d-sm-inline-flex" title="Notifications">
                <i class="fa-solid fa-bell"></i>
                @if($unreadCount > 0)
                    <span class="out-portal-icon-badge"></span>
                @endif
            </a>
        @endif

        <!-- User Profile Dropdown Pill -->
        @if($user)
            <div class="dropdown out-portal-user-dropdown">
                <a href="#" class="out-portal-user-trigger" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ $userPhoto }}" alt="{{ $user->name }}" class="out-portal-user-avatar">
                    <div class="out-portal-user-info d-none d-md-block text-start">
                        <span class="out-portal-user-name">{{ $user->name }}</span>
                        <span class="out-portal-user-role">
                            <i class="fa-solid fa-circle-check text-success" style="font-size: 10px;"></i>
                            {{ $portalType === 'instructor' ? 'Verified Instructor' : 'Enrolled Student' }}
                        </span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-muted ms-1" style="font-size: 10px;"></i>
                </a>

                <ul class="dropdown-menu dropdown-menu-end out-portal-menu-dropdown shadow-sm">
                    <li class="px-3 py-2 border-bottom mb-1">
                        <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size: 10px; letter-spacing: 0.05em;">Account</div>
                        <div class="fw-bold text-dark text-truncate" style="font-size: 13.5px;">{{ $user->name }}</div>
                        <div class="text-muted text-truncate" style="font-size: 11.5px;">{{ $user->email }}</div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ $homeRoute }}">
                            <i class="fa-solid fa-gauge text-primary"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ $settingsRoute }}">
                            <i class="fa-solid fa-gear text-secondary"></i> Profile & Settings
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ $ticketsRoute }}">
                            <i class="fa-solid fa-headset text-info"></i> Support Desk
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="{{ route('logout') }}">
                            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        @endif
    </div>
</header>

