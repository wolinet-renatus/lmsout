@extends('layouts.student')

@section('content')
    <!-- Student Campus Hero Banner -->
    <div class="out-hero-banner out-hero-student">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="out-hero-badge">
                    <i class="fa-solid fa-graduation-cap"></i> Student Campus
                </span>
                <h1 class="out-hero-title">
                    {{ __('student.welcome_back', ['name' => $firstName]) }}
                </h1>
                <p class="out-hero-subtitle">
                    {{ __('student.continue_journey') }}
                </p>
            </div>
            <div class="out-hero-actions">
                <a href="{{ route('courses') }}" class="out-pill-btn out-pill-yellow">
                    <i class="fa-solid fa-compass"></i> Explore Courses
                </a>
                <a href="{{ route('student.certificates') }}" class="out-pill-btn out-pill-outline">
                    <i class="fa-solid fa-certificate"></i> My Certificates
                </a>
                <a href="{{ route('student.tickets.index') }}" class="out-pill-btn out-pill-outline">
                    <i class="fa-solid fa-headset"></i> Support Desk
                </a>
            </div>
        </div>
    </div>

    <!-- Compact Metric Stat Cards Grid -->
    <div class="out-stat-grid">
        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-blue">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value" id="kpiEnrolled">{{ $stats['total_enrollments'] }}</div>
                <p class="out-stat-label">{{ __('student.enrolled_courses') }}</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-emerald">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value" id="kpiCompleted">{{ $stats['completed_courses'] }}</div>
                <p class="out-stat-label">{{ __('student.completed') }}</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-amber">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value" id="kpiPending">{{ $stats['pending_enrollments'] }}</div>
                <p class="out-stat-label">In Progress / Pending</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-sky">
                <i class="fa-solid fa-circle-play"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value" id="kpiLessons">{{ $stats['total_lessons'] }}</div>
                <p class="out-stat-label">{{ __('student.total_lessons') }}</p>
            </div>
        </div>
    </div>

    <!-- Real-Time Campus & Communication Summary Bar -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="{{ route('student.notifications.index') }}" class="out-card text-decoration-none d-flex align-items-center justify-content-between p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(1, 59, 166, 0.1); color: #013ba6;">
                        <i class="fa-solid fa-bell"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px;">Unread Alerts</div>
                        <div class="text-muted" style="font-size: 12px;">Campus events &amp; billing</div>
                    </div>
                </div>
                <span id="summaryUnreadNotifs" class="badge rounded-pill {{ ($stats['unread_notifications'] ?? 0) > 0 ? 'bg-danger' : 'bg-light text-muted border' }} px-3 py-2">
                    {{ $stats['unread_notifications'] ?? 0 }}
                </span>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('student.messages.index') }}" class="out-card text-decoration-none d-flex align-items-center justify-content-between p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(34, 141, 221, 0.12); color: #0369a1;">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px;">Instructor Messages</div>
                        <div class="text-muted" style="font-size: 12px;">Direct academic inbox</div>
                    </div>
                </div>
                <span id="summaryUnreadMsgs" class="badge rounded-pill {{ ($stats['unread_messages'] ?? 0) > 0 ? 'bg-primary' : 'bg-light text-muted border' }} px-3 py-2">
                    {{ $stats['unread_messages'] ?? 0 }}
                </span>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('student.campus.invoices.index') }}" class="out-card text-decoration-none d-flex align-items-center justify-content-between p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(217, 119, 6, 0.12); color: #b45309;">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px;">Fee Balance Due</div>
                        <div class="text-muted" style="font-size: 12px;">View invoices &amp; receipts</div>
                    </div>
                </div>
                <span id="summaryOutstandingBalance" class="fw-bold {{ ($stats['outstanding_balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 13.5px;">
                    {{ currency_format($stats['outstanding_balance'] ?? 0) }}
                </span>
            </a>
        </div>
    </div>

    <!-- Upcoming Live Class Banner (If Scheduled) -->
    @if ($upcomingLiveClass)
        <div class="out-card mb-4 overflow-hidden border-0 shadow-sm"
             style="background: {{ $upcomingLiveClass->is_currently_live ? 'linear-gradient(135deg, #b91c1c 0%, #ef4444 100%)' : 'linear-gradient(135deg, #0f172a 0%, #013ba6 100%)' }}; color: #ffffff;">
            <div class="p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        @if ($upcomingLiveClass->is_currently_live)
                            <span class="badge bg-danger text-uppercase fw-bold px-3 py-1">
                                <i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i> Live Now
                            </span>
                        @else
                            <span class="badge bg-white text-dark text-uppercase fw-bold px-3 py-1">
                                <i class="fa-solid fa-clock me-1"></i> Upcoming Session
                            </span>
                        @endif
                        <span class="text-white-50" style="font-size: 13px;">
                            {{ $upcomingLiveClass->course->title ?? 'Course' }}
                        </span>
                    </div>
                    <h3 class="text-white fw-bold mb-1" style="font-size: 20px;">
                        {{ $upcomingLiveClass->title }}
                    </h3>
                    <p class="text-white-50 mb-0" style="font-size: 13px;">
                        <i class="fa-solid fa-calendar me-1"></i> {{ $upcomingLiveClass->scheduled_at->format('M d, Y · h:i A') }}
                        @if ($upcomingLiveClass->duration_minutes)
                            <span class="mx-2">&bull;</span>
                            <i class="fa-solid fa-hourglass-half me-1"></i> {{ $upcomingLiveClass->duration_minutes }} min
                        @endif
                    </p>
                </div>
                <div>
                    @if ($upcomingLiveClass->is_currently_live || ($upcomingLiveClass->minutes_until <= 30 && $upcomingLiveClass->minutes_until > 0))
                        <a href="{{ $upcomingLiveClass->join_url }}" target="_blank" rel="noopener" class="btn btn-warning fw-bold px-4 py-2 rounded-pill text-dark">
                            <i class="fa-solid fa-video me-1"></i> Join Class Now
                        </a>
                    @else
                        <div class="text-center px-4 py-2 rounded-3" style="background: rgba(255,255,255,0.12);">
                            <span class="d-block text-white-50 text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Starts in</span>
                            <span class="fw-bold text-white fs-5">
                                @if ($upcomingLiveClass->minutes_until > 1440)
                                    {{ floor($upcomingLiveClass->minutes_until / 1440) }}d {{ floor(($upcomingLiveClass->minutes_until % 1440) / 60) }}h
                                @elseif ($upcomingLiveClass->minutes_until > 60)
                                    {{ floor($upcomingLiveClass->minutes_until / 60) }}h {{ $upcomingLiveClass->minutes_until % 60 }}m
                                @else
                                    {{ $upcomingLiveClass->minutes_until }}m
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content Filter Bar -->
    @include('partials.portal-dashboard-filter', [
        'actionRoute' => route('student.dashboard'),
        'filters' => $filters ?? [],
        'statusOptions' => $statusOptions ?? [],
        'searchPlaceholder' => 'Search courses, instructors, invoices, control numbers...',
    ])

    <!-- Main Content 2-Column Responsive Grid -->
    <div class="row g-4">
        <!-- Left Column: Continue Learning & Enrolled Courses -->
        <div class="col-xl-8 col-lg-7">
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-book-open-reader text-primary"></i> Continue Learning
                    </h3>
                    <a href="{{ route('student.my-courses') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                        My Courses ({{ $stats['total_enrollments'] }})
                    </a>
                </div>
                <div class="out-card-body p-3">
                    @forelse($recentEnrollments as $enrollment)
                        @php
                            $course = $enrollment->course;
                            $courseImage = $course?->featured_image
                                ? asset('storage/' . $course->featured_image)
                                : asset('assets/front/img/home-1/courses/courses-01.jpg');
                        @endphp
                        @if($course)
                            <div class="out-course-item">
                                <img src="{{ $courseImage }}" alt="{{ $course->title }}" class="out-course-thumb" onerror="this.src='{{ asset('assets/front/img/home-1/courses/courses-01.jpg') }}'">
                                <div class="out-course-meta">
                                    <h4 class="out-course-title">
                                        <a href="{{ route('student.courses.access', $course->id) }}">{{ $course->title }}</a>
                                    </h4>
                                    <div class="out-course-badges">
                                        <span><i class="fa-solid fa-user text-muted me-1"></i> {{ $course->instructor->name ?? 'OUT Faculty' }}</span>
                                        <span><i class="fa-solid fa-circle-play text-muted me-1"></i> {{ $enrollment->lessons_count }} Lessons</span>
                                        @if($enrollment->enrolled_at)
                                            <span class="d-none d-sm-inline"><i class="fa-solid fa-calendar text-muted me-1"></i> {{ $enrollment->enrolled_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="{{ route('student.courses.access', $course->id) }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 12.5px;">
                                        Resume <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="out-empty-state">
                            <div class="out-empty-state-icon">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <div class="out-empty-state-title">No Matching Enrolled Courses</div>
                            <p class="out-empty-state-text mb-3">Try clearing active filters or browse the university catalog to enroll.</p>
                            <a href="{{ route('courses') }}" class="btn btn-primary btn-sm rounded-pill px-3 py-2 fw-semibold">
                                <i class="fa-solid fa-compass me-1"></i> Browse Course Catalog
                            </a>
                        </div>
                    @endforelse
                </div>
                @include('partials.portal-card-pagination', ['paginator' => $recentEnrollments ?? null])
            </div>

            <!-- My Campus Invoices & GePG Control Numbers -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-file-invoice-dollar text-success"></i> Invoices &amp; Control Numbers
                    </h3>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 11px;">
                            <i class="fa-solid fa-signal me-1"></i> Live Sync
                        </span>
                        <a href="{{ route('student.campus.invoices.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                            All Invoices
                        </a>
                    </div>
                </div>
                <div class="out-card-body p-0 table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Invoice</th>
                                <th>Control No</th>
                                <th>Course</th>
                                <th>Balance</th>
                                <th class="pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody id="dashboardInvoicesBody">
                            @forelse($recentInvoices ?? [] as $inv)
                                <tr>
                                    <td class="ps-3"><code>{{ $inv->invoice_number }}</code></td>
                                    <td><code>{{ $inv->control_number ?? '-' }}</code></td>
                                    <td>{{ \Illuminate\Support\Str::limit($inv->course?->title ?? 'Course Fee', 28) }}</td>
                                    <td>{{ currency_format($inv->balance) }}</td>
                                    <td class="pe-3">
                                        <span class="badge bg-{{ $inv->payment_status === 'paid' ? 'success' : ($inv->payment_status === 'partial' ? 'warning text-dark' : 'danger') }}">
                                            {{ strtoupper($inv->payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No matching invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.portal-card-pagination', ['paginator' => $recentInvoices ?? null])
            </div>

            <!-- Internal Notifications & Campus Events -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-bell text-warning"></i> Recent Notifications &amp; Events
                        <span id="dashboardNotifBadge" class="badge rounded-pill ms-2 {{ ($stats['unread_notifications'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' }}" style="font-size: 11px;">
                            {{ $stats['unread_notifications'] ?? 0 }} Unread
                        </span>
                    </h3>
                    <div class="d-flex align-items-center gap-2">
                        @if(($stats['unread_notifications'] ?? 0) > 0)
                            <a href="{{ route('student.notifications.readAll') }}" class="btn btn-sm btn-light border rounded-pill px-3 py-1" style="font-size: 11.5px; font-weight: 600;">
                                Mark All Read
                            </a>
                        @endif
                        <a href="{{ route('student.notifications.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                            View All
                        </a>
                    </div>
                </div>
                <div class="out-card-body p-3" id="dashboardNotifFeed">
                    @forelse($recentNotifications ?? [] as $notif)
                        @php
                            $ndata = is_array($notif->data) ? $notif->data : [];
                            $isUnread = $notif->read_at === null;
                        @endphp
                        <a href="{{ route('student.notifications.read', $notif->id) }}"
                           class="d-flex align-items-start justify-content-between gap-3 p-2 rounded-3 text-decoration-none mb-2 border {{ $isUnread ? 'bg-light border-primary-subtle' : 'bg-white' }}">
                            <div class="d-flex align-items-start gap-2" style="min-width: 0;">
                                <i class="fa-solid fa-circle-dot mt-1 {{ $isUnread ? 'text-primary' : 'text-muted' }}" style="font-size: 11px;"></i>
                                <div style="min-width: 0;">
                                    <div class="fw-semibold text-dark text-truncate" style="font-size: 13px;">{{ $ndata['title'] ?? 'Notification' }}</div>
                                    <div class="text-muted text-truncate" style="font-size: 12px;">{{ $ndata['message'] ?? '' }}</div>
                                </div>
                            </div>
                            <span class="text-muted flex-shrink-0" style="font-size: 11px;">{{ $notif->created_at?->diffForHumans(null, true) }}</span>
                        </a>
                    @empty
                        <div class="text-center text-muted py-3" style="font-size: 13px;">
                            No recent notifications. Activity and billing updates will appear here.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Campus Community & Discussion Card -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-users text-primary"></i> Academic Community & Forum
                    </h3>
                    <a href="{{ route('student.community.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                        Open Community
                    </a>
                </div>
                <div class="out-card-body p-3">
                    <div class="p-3 rounded-3" style="background: rgba(1, 59, 166, 0.04); border: 1px solid rgba(1, 59, 166, 0.1);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="fs-3 text-primary"><i class="fa-solid fa-comments"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1" style="color: #013ba6; font-size: 14px;">Connect with Fellow OUT Scholars</h6>
                                <p class="text-muted mb-0" style="font-size: 12.5px;">
                                    Join peer discussions, share study notes, ask questions, and collaborate with learners across all university faculties.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Communication, Campus Timetable & Advising -->
        <div class="col-xl-4 col-lg-5">
            <!-- Direct Messages / Instructor Inbox -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-comments text-primary"></i> Direct Messages
                    </h3>
                    <a href="{{ route('student.messages.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                        Open Inbox
                    </a>
                </div>
                <div class="out-card-body p-3">
                    @forelse($recentConversations ?? [] as $conv)
                        @php
                            $instructorName = $conv->instructor?->name ?? 'OUT Instructor';
                            $lastBody = $conv->latestMessage?->body ?: ($conv->latestMessage ? 'Sent attachment' : 'Start chatting');
                        @endphp
                        <a href="{{ route('student.messages.show', $conv->id) }}"
                           class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-3 text-decoration-none border mb-2 {{ ($conv->unread_count ?? 0) > 0 ? 'bg-light border-primary-subtle' : 'bg-white' }}">
                            <div style="min-width: 0;">
                                <div class="fw-semibold text-dark text-truncate" style="font-size: 13px;">{{ $instructorName }}</div>
                                <div class="text-muted text-truncate" style="font-size: 11.5px;">{{ \Illuminate\Support\Str::limit($lastBody, 45) }}</div>
                            </div>
                            @if(($conv->unread_count ?? 0) > 0)
                                <span class="badge bg-primary rounded-pill">{{ $conv->unread_count }}</span>
                            @else
                                <i class="fa-solid fa-chevron-right text-muted" style="font-size: 11px;"></i>
                            @endif
                        </a>
                    @empty
                        <div class="text-center text-muted py-2" style="font-size: 12.5px;">
                            No active instructor conversations yet.
                        </div>
                    @endforelse
                </div>
            </div>

            @if(!empty($upcomingCampusSchedule))
                <div class="out-card">
                    <div class="out-card-header">
                        <h3 class="out-card-title">
                            <i class="fa-solid fa-calendar-days text-success"></i> Campus Timetable
                        </h3>
                        <a href="{{ route('student.campus.timetable') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                            Full Schedule
                        </a>
                    </div>
                    <div class="out-card-body p-3">
                        <div class="fw-bold text-dark mb-1" style="font-size: 13.5px;">{{ $upcomingCampusSchedule->course?->title ?? 'Campus Cohort' }}</div>
                        <div class="text-muted mb-1" style="font-size: 12px;">
                            <i class="fa-solid fa-location-dot me-1 text-primary"></i>
                            {{ $upcomingCampusSchedule->courseAssignment?->venue?->name ?? $upcomingCampusSchedule->venue?->name ?? 'Regional Campus Venue' }}
                        </div>
                        <div class="text-muted mb-2" style="font-size: 12px;">
                            <i class="fa-solid fa-chalkboard-user me-1 text-info"></i>
                            {{ $upcomingCampusSchedule->courseAssignment?->instructor?->name ?? 'Assigned Lecturer' }}
                        </div>
                        <a href="{{ route('student.campus.timetable') }}" class="btn btn-sm btn-light border rounded-pill w-100 fw-semibold" style="font-size: 12px;">
                            View Live Session Status <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Quick Study Shortcuts -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-bolt text-warning"></i> Quick Shortcuts
                    </h3>
                </div>
                <div class="out-card-body p-3">
                    <a href="{{ route('courses') }}" class="out-action-tile">
                        <i class="fa-solid fa-compass"></i>
                        <span>Browse Course Catalog</span>
                    </a>
                    <a href="{{ route('student.live-classes.index') }}" class="out-action-tile">
                        <i class="fa-solid fa-video"></i>
                        <span>Live Classes Schedule</span>
                    </a>
                    <a href="{{ route('student.assignments') }}" class="out-action-tile">
                        <i class="fa-solid fa-clipboard-check"></i>
                        <span>My Pending Assignments</span>
                    </a>
                    <a href="{{ route('student.quiz-attempts') }}" class="out-action-tile">
                        <i class="fa-solid fa-circle-question"></i>
                        <span>Quiz Attempts & Grades</span>
                    </a>
                    <a href="{{ route('student.ai-chat.index') }}" class="out-action-tile">
                        <i class="fa-solid fa-robot"></i>
                        <span>AI Study Assistant</span>
                    </a>
                    <a href="{{ route('student.certificates') }}" class="out-action-tile">
                        <i class="fa-solid fa-certificate"></i>
                        <span>Earned Certificates</span>
                    </a>
                </div>
            </div>

            <!-- Student Support & Academic Advising -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-circle-question text-info"></i> Need Student Help?
                    </h3>
                </div>
                <div class="out-card-body p-3">
                    <div class="p-3 rounded-3 mb-3" style="background: rgba(34, 141, 221, 0.06); border: 1px solid rgba(34, 141, 221, 0.15);">
                        <h6 class="fw-bold mb-1" style="color: #0369a1; font-size: 13.5px;">Student Advisory Desk</h6>
                        <p class="text-muted mb-0" style="font-size: 12.5px;">
                            For course inquiries, examination schedules, or technical issues with online materials.
                        </p>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('student.tickets.create') }}" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold py-2">
                            <i class="fa-solid fa-headset me-1"></i> Submit Support Ticket
                        </a>
                        <a href="mailto:vc@out.ac.tz" class="btn btn-light btn-sm rounded-pill text-muted border py-2" style="font-size: 12.5px;">
                            <i class="fa-solid fa-envelope me-1"></i> vc@out.ac.tz
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $dashboardLiveUrl = route('student.dashboard.live');
    @endphp
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const liveUrl = @json($dashboardLiveUrl);

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function refreshDashboard() {
        fetch(liveUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.stats) return;

                const s = data.stats;
                const setText = function (id, val) {
                    const el = document.getElementById(id);
                    if (el) el.textContent = String(val ?? 0);
                };
                setText('kpiEnrolled', s.total_enrollments);
                setText('kpiCompleted', s.completed_courses);
                setText('kpiPending', s.pending_enrollments);
                setText('kpiLessons', s.total_lessons);
                setText('summaryUnreadNotifs', s.unread_notifications);
                setText('summaryUnreadMsgs', s.unread_messages);

                const balEl = document.getElementById('summaryOutstandingBalance');
                if (balEl) {
                    balEl.textContent = s.outstanding_balance_formatted;
                    balEl.className = 'fw-bold ' + ((s.outstanding_balance || 0) > 0 ? 'text-danger' : 'text-success');
                }

                const notifBadge = document.getElementById('dashboardNotifBadge');
                if (notifBadge) {
                    notifBadge.textContent = (s.unread_notifications || 0) + ' Unread';
                    notifBadge.className = 'badge rounded-pill ms-2 ' + ((s.unread_notifications || 0) > 0 ? 'bg-danger' : 'bg-secondary');
                }

                const feedEl = document.getElementById('dashboardNotifFeed');
                if (feedEl && Array.isArray(data.notifications)) {
                    if (data.notifications.length === 0) {
                        feedEl.innerHTML = '<div class="text-center text-muted py-3" style="font-size: 13px;">No recent notifications. Activity and billing updates will appear here.</div>';
                    } else {
                        feedEl.innerHTML = data.notifications.map(function (n) {
                            return '<a href="' + esc(n.read_url || '#') + '" class="d-flex align-items-start justify-content-between gap-3 p-2 rounded-3 text-decoration-none mb-2 border ' + (n.is_unread ? 'bg-light border-primary-subtle' : 'bg-white') + '">'
                                + '<div class="d-flex align-items-start gap-2" style="min-width: 0;">'
                                + '<i class="fa-solid fa-circle-dot mt-1 ' + (n.is_unread ? 'text-primary' : 'text-muted') + '" style="font-size: 11px;"></i>'
                                + '<div style="min-width: 0;"><div class="fw-semibold text-dark text-truncate" style="font-size: 13px;">' + esc(n.title) + '</div>'
                                + '<div class="text-muted text-truncate" style="font-size: 12px;">' + esc(n.message) + '</div></div></div>'
                                + '<span class="text-muted flex-shrink-0" style="font-size: 11px;">' + esc(n.time_ago) + '</span></a>';
                        }).join('');
                    }
                }

                const invBody = document.getElementById('dashboardInvoicesBody');
                const qs = window.location.search || '';
                if (invBody && Array.isArray(data.invoices) && !qs.includes('search=') && !qs.includes('status=') && !qs.includes('invoices_page=')) {
                    if (data.invoices.length === 0) {
                        invBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No matching invoices found.</td></tr>';
                    } else {
                        invBody.innerHTML = data.invoices.map(function (inv) {
                            const cls = inv.payment_status === 'paid' ? 'bg-success' : (inv.payment_status === 'partial' ? 'bg-warning text-dark' : 'bg-danger');
                            return '<tr>'
                                + '<td class="ps-3"><code>' + esc(inv.invoice_number) + '</code></td>'
                                + '<td><code>' + esc(inv.control_number || '-') + '</code></td>'
                                + '<td>' + esc(inv.course_title) + '</td>'
                                + '<td>' + esc(inv.balance_formatted) + '</td>'
                                + '<td class="pe-3"><span class="badge ' + cls + '">' + esc(String(inv.payment_status || 'unpaid').toUpperCase()) + '</span></td>'
                                + '</tr>';
                        }).join('');
                    }
                }
            })
            .catch(function () {});
    }

    setInterval(refreshDashboard, 12000);
});
</script>
@endpush
