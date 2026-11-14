@extends('layouts.instructor')

@section('content')
    <!-- Instructor Creator Studio Hero Banner -->
    <div class="out-hero-banner out-hero-instructor">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="out-hero-badge">
                    <i class="fa-solid fa-chalkboard-user"></i> Instructor Studio
                </span>
                <h1 class="out-hero-title">
                    {{ __('instructor.welcome_back', ['name' => explode(' ', $user->name)[0]]) }}
                </h1>
                <p class="out-hero-subtitle">
                    {{ __('instructor.performance_subtitle') }}
                </p>
            </div>
            <div class="out-hero-actions">
                <a href="{{ route('instructor.courses.create') }}" class="out-pill-btn out-pill-yellow">
                    <i class="fa-solid fa-plus"></i> New Course
                </a>
                <a href="{{ route('instructor.live-classes.create') }}" class="out-pill-btn out-pill-outline">
                    <i class="fa-solid fa-video"></i> Schedule Class
                </a>
                <a href="{{ route('instructor.tickets.index') }}" class="out-pill-btn out-pill-outline">
                    <i class="fa-solid fa-headset"></i> Support Desk
                </a>
            </div>
        </div>
    </div>

    <!-- Compact Metric Stat Cards Grid -->
    <div class="out-stat-grid">
        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-blue">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value">{{ number_format($stats['courses']) }}</div>
                <p class="out-stat-label">Total Courses</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-sky">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value">{{ number_format($stats['students']) }}</div>
                <p class="out-stat-label">Active Students</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-amber">
                <i class="fa-solid fa-star"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value">{{ $stats['average_rating'] > 0 ? number_format($stats['average_rating'], 1) : '5.0' }}</div>
                <p class="out-stat-label">Average Rating</p>
            </div>
        </div>

        <div class="out-stat-card">
            <div class="out-stat-icon-wrap out-stat-icon-emerald">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="out-stat-content">
                <div class="out-stat-value">{{ $stats['earnings'] > 0 ? currency_format($stats['earnings']) : currency_format(0) }}</div>
                <p class="out-stat-label">Total Earnings</p>
            </div>
        </div>
    </div>

    <!-- Main Content Filter Bar -->
    @include('partials.portal-dashboard-filter', [
        'actionRoute' => route('instructor.dashboard'),
        'filters' => $filters ?? [],
        'statusOptions' => $statusOptions ?? [],
        'searchPlaceholder' => 'Search courses, learners, reviews...',
    ])

    <!-- Main Content 2-Column Responsive Grid -->
    <div class="row g-4">
        <!-- Left Column: Courses & Activity -->
        <div class="col-xl-8 col-lg-7">
            <!-- Popular & Recent Courses Card -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-book-bookmark text-primary"></i> My Active Courses
                    </h3>
                    <a href="{{ route('instructor.courses') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                        View All ({{ $stats['courses'] }})
                    </a>
                </div>
                <div class="out-card-body p-3">
                    @forelse($recentCourses as $course)
                        @php
                            $courseImage = $course->featured_image
                                ? asset('storage/' . $course->featured_image)
                                : asset('assets/front/img/home-1/courses/courses-01.jpg');
                            $lessonsCount = $course->topics->sum(fn($topic) => $topic->lessons->count());
                            $price = $course->sale_price ?? ($course->regular_price ?? 0);
                            $difficulty = $course->difficulty ?: 'General';
                        @endphp
                        <div class="out-course-item">
                            <img src="{{ $courseImage }}" alt="{{ $course->title }}" class="out-course-thumb" onerror="this.src='{{ asset('assets/front/img/home-1/courses/courses-01.jpg') }}'">
                            <div class="out-course-meta">
                                <h4 class="out-course-title">
                                    <a href="{{ route('instructor.courses.edit', $course->id) }}">{{ $course->title }}</a>
                                </h4>
                                <div class="out-course-badges">
                                    <span class="badge bg-light text-dark border">{{ $difficulty }}</span>
                                    <span><i class="fa-solid fa-circle-play text-muted me-1"></i> {{ $lessonsCount }} Lessons</span>
                                    <span><i class="fa-solid fa-user text-muted me-1"></i> {{ $course->students_count }} Students</span>
                                    @if($course->average_rating)
                                        <span class="text-warning"><i class="fa-solid fa-star me-1"></i> {{ number_format($course->average_rating, 1) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <span class="fw-bold text-dark d-none d-sm-inline" style="font-size: 14px;">
                                    {{ $course->pricing_model === 'free' ? 'Free' : currency_format($price) }}
                                </span>
                                <a href="{{ route('instructor.courses.edit', $course->id) }}" class="btn btn-sm btn-light border rounded-pill px-3 py-1" style="font-size: 12.5px; font-weight: 600;">
                                    Manage
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="out-empty-state">
                            <div class="out-empty-state-icon">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <div class="out-empty-state-title">No Matching Courses</div>
                            <p class="out-empty-state-text mb-3">Try adjusting your filters or create a new academic course.</p>
                            <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 py-2 fw-semibold">
                                <i class="fa-solid fa-plus me-1"></i> Create Your First Course
                            </a>
                        </div>
                    @endforelse
                </div>
                @include('partials.portal-card-pagination', ['paginator' => $recentCourses ?? null])
            </div>

            <!-- Recent Activity Feed Card -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-clock-rotate-left text-primary"></i> {{ __('instructor.recent_activity') }}
                    </h3>
                </div>
                <div class="out-card-body p-3">
                    @if(empty($activities) || (is_object($activities) && $activities->isEmpty()))
                        <div class="out-empty-state">
                            <div class="out-empty-state-icon">
                                <i class="fa-solid fa-bell-slash"></i>
                            </div>
                            <div class="out-empty-state-title">{{ __('instructor.no_activity_yet') }}</div>
                            <p class="out-empty-state-text">{{ __('instructor.no_activity_desc') }}</p>
                        </div>
                    @else
                        <ul class="out-activity-list">
                            @foreach ($activities as $activity)
                                <li class="out-activity-item">
                                    <div class="out-activity-icon">
                                        <i class="{{ $activity['icon'] ?? 'fa-solid fa-bell' }}"></i>
                                    </div>
                                    <div class="out-activity-content">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <h5 class="out-activity-title">{{ $activity['title'] }}</h5>
                                            <span class="out-activity-time">{{ $activity['relative_time'] ?? __('instructor.just_now') }}</span>
                                        </div>
                                        <p class="out-activity-desc">{{ $activity['description'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @include('partials.portal-card-pagination', ['paginator' => $activities ?? null])
            </div>
        </div>

        <!-- Right Column: Shortcuts & Info -->
        <div class="col-xl-4 col-lg-5">
            <!-- Quick Actions Tile Box -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-bolt text-warning"></i> Quick Actions
                    </h3>
                </div>
                <div class="out-card-body p-3">
                    <a href="{{ route('instructor.courses.create') }}" class="out-action-tile">
                        <i class="fa-solid fa-circle-plus"></i>
                        <span>Create New Course</span>
                    </a>
                    <a href="{{ route('instructor.live-classes.create') }}" class="out-action-tile">
                        <i class="fa-solid fa-video"></i>
                        <span>Schedule Live Class</span>
                    </a>
                    <a href="{{ route('instructor.analytics') }}" class="out-action-tile">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>View Analytics & Insights</span>
                    </a>
                    <a href="{{ route('instructor.tickets.create') }}" class="out-action-tile">
                        <i class="fa-solid fa-headset"></i>
                        <span>Submit Faculty Ticket</span>
                    </a>
                </div>
            </div>

            <!-- Instructor Support & Guidelines Card -->
            <div class="out-card">
                <div class="out-card-header">
                    <h3 class="out-card-title">
                        <i class="fa-solid fa-circle-info text-info"></i> OUT Faculty Support
                    </h3>
                </div>
                <div class="out-card-body p-3">
                    <div class="p-3 rounded-3 mb-3" style="background: rgba(1, 59, 166, 0.05); border: 1px solid rgba(1, 59, 166, 0.12);">
                        <h6 class="fw-bold mb-1" style="color: #013ba6; font-size: 13.5px;">Curriculum & Quality Standards</h6>
                        <p class="text-muted mb-0" style="font-size: 12.5px;">
                            Ensure all learning outcomes comply with OUT Academic Board standards and assessment criteria.
                        </p>
                    </div>
                    <div class="p-3 rounded-3" style="background: rgba(34, 141, 221, 0.06); border: 1px solid rgba(34, 141, 221, 0.15);">
                        <h6 class="fw-bold mb-1" style="color: #0369a1; font-size: 13.5px;">Need Technical Assistance?</h6>
                        <p class="text-muted mb-2" style="font-size: 12.5px;">
                            Our dedicated LMS Technical Support team is ready to assist you.
                        </p>
                        <a href="{{ route('instructor.tickets.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: 600;">
                            Open Support Desk
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
