@php
    $portalType = $portalType ?? (auth()->user()?->hasRole('instructor') ? 'instructor' : 'student');
    $readAllRoute = route($portalType . '.notifications.readAll');
    $readRouteName = $portalType . '.notifications.read';
    $homeRoute = route($portalType . '.dashboard');
    $unreadCount = auth()->user() ? auth()->user()->unreadNotifications()->count() : 0;
@endphp

<div class="out-hero-banner {{ $portalType === 'instructor' ? 'out-hero-instructor' : 'out-hero-student' }} mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="out-hero-badge">
                <i class="fa-solid fa-bell"></i> Notifications Center
            </span>
            <h1 class="out-hero-title mb-1">Notifications &amp; Internal Alerts</h1>
            <p class="out-hero-subtitle mb-0">
                Stay updated on course enrollments, invoices, payments, certificates, and direct messages.
            </p>
        </div>
        <div class="out-hero-actions d-flex align-items-center gap-2 flex-wrap">
            @if($unreadCount > 0)
                <a href="{{ $readAllRoute }}" class="out-pill-btn out-pill-yellow">
                    <i class="fa-solid fa-check-double"></i> Mark All Read ({{ $unreadCount }})
                </a>
            @endif
            <a href="{{ $homeRoute }}" class="out-pill-btn out-pill-outline">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<div class="out-card">
    <div class="out-card-header">
        <h3 class="out-card-title">
            <i class="fa-solid fa-inbox text-primary"></i> Activity Feed
            <span class="badge bg-light text-dark border ms-2">{{ $notifications->total() }}</span>
        </h3>
        @if($unreadCount > 0)
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                {{ $unreadCount }} Unread
            </span>
        @endif
    </div>
    <div class="out-card-body p-3 p-md-4">
        <div class="d-flex flex-column gap-3">
            @forelse($notifications as $notification)
                @php
                    $isUnread = $notification->read_at === null;
                    $data = is_array($notification->data) ? $notification->data : [];
                    $notifType = $data['type'] ?? 'info';

                    $palette = match ($notifType) {
                        'success' => ['accent' => '#059669', 'bg' => 'rgba(5, 150, 105, 0.08)', 'icon' => 'fa-circle-check'],
                        'warning' => ['accent' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.09)', 'icon' => 'fa-triangle-exclamation'],
                        'danger'  => ['accent' => '#dc2626', 'bg' => 'rgba(220, 38, 38, 0.08)', 'icon' => 'fa-circle-exclamation'],
                        default   => ['accent' => '#013ba6', 'bg' => 'rgba(1, 59, 166, 0.07)', 'icon' => 'fa-bell'],
                    };
                @endphp
                <div class="p-3 rounded-3 border d-flex justify-content-between align-items-start gap-3 flex-wrap {{ $isUnread ? 'shadow-sm' : 'opacity-85' }}"
                     style="background: {{ $isUnread ? '#f8fbff' : '#ffffff' }}; border-left: 4px solid {{ $isUnread ? $palette['accent'] : '#e2e8f0' }} !important;">
                    <div class="d-flex align-items-start gap-3 flex-grow-1" style="min-width: 240px;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width: 44px; height: 44px; background: {{ $palette['bg'] }}; color: {{ $palette['accent'] }};">
                            <i class="fa-solid {{ $palette['icon'] }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <h6 class="mb-0 fw-bold text-dark" style="font-size: 14.5px;">
                                    {{ $data['title'] ?? 'Notification' }}
                                </h6>
                                @if($isUnread)
                                    <span class="badge rounded-pill" style="background: {{ $palette['accent'] }}; font-size: 10px;">NEW</span>
                                @endif
                            </div>
                            <p class="text-muted mb-2" style="font-size: 13px; line-height: 1.55;">
                                {{ $data['message'] ?? '' }}
                            </p>
                            <div class="text-muted d-flex align-items-center gap-2" style="font-size: 11.5px;">
                                <i class="fa-regular fa-clock"></i>
                                <span>{{ $notification->created_at?->diffForHumans() ?? 'Just now' }}</span>
                                <span class="text-black-50">&bull;</span>
                                <span>{{ $notification->created_at?->format('M d, Y · h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if(!empty($data['url']))
                            <a href="{{ route($readRouteName, $notification->id) }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold">
                                Open &amp; Mark Read <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        @elseif($isUnread)
                            <a href="{{ route($readRouteName, $notification->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">
                                <i class="fa-solid fa-check me-1"></i> Mark Read
                            </a>
                        @else
                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">Read</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 68px; height: 68px;">
                        <i class="fa-regular fa-bell-slash fs-3 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">All Caught Up</h5>
                    <p class="text-muted mb-0" style="font-size: 13.5px;">No notifications yet. Activity alerts will appear here automatically.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
