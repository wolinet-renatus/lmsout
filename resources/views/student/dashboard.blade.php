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
                <div class="out-stat-value">{{ $stats['total_enrollments'] }}</div>
                <p class="out-stat-label">{{ __('student.enrolled_courses') }}</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-emerald">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value">{{ $stats['completed_courses'] }}</div>
                <p class="out-stat-label">{{ __('student.completed') }}</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-amber">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value">{{ $stats['pending_enrollments'] }}</div>
                <p class="out-stat-label">In Progress / Pending</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-sky">
                <i class="fa-solid fa-circle-play"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value">{{ $stats['total_lessons'] }}</div>
                <p class="out-stat-label">{{ __('student.total_lessons') }}</p>
            </div>
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
                    <a href="{{ route('student.campus.invoices.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                        All Invoices
                    </a>
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
                        <tbody>
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

        <!-- Right Column: Shortcuts & Advising -->
        <div class="col-xl-4 col-lg-5">
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
@endsection
