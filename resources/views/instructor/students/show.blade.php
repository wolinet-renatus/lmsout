@extends('layouts.instructor')

@section('title', $student['name'] . ' - Student Profile')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('instructor.students') }}" class="btn btn-light btn-sm border">
            <i class="fa-solid fa-arrow-left me-1"></i>Back to Students
        </a>
    </div>
</div>

<!-- Compact Student Profile Card -->
<div class="bg-white border rounded-3 p-3 p-md-4 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <img
            src="{{ $student['avatar'] }}"
            alt="{{ $student['name'] }}"
            class="rounded-circle border flex-shrink-0"
            style="width: 68px; height: 68px; object-fit: cover;"
        >
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h2 class="h4 fw-bold mb-0">{{ $student['name'] }}</h2>
                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle" style="font-size: 11px;">
                    {{ ucfirst($student['status'] ?? 'active') }}
                </span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                <span>
                    <i class="fa-regular fa-envelope me-1"></i>{{ $student['email'] }}
                </span>
                @if(!empty($student['phone']))
                    <span>
                        <i class="fa-solid fa-phone me-1"></i>{{ $student['phone'] }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Compact KPI Strip -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $stats['total_courses'] ?? 0 }}</div>
                <div class="text-muted small">Total Courses</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $stats['completed'] ?? 0 }}</div>
                <div class="text-muted small">Completed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $stats['completion_rate'] ?? 0 }}%</div>
                <div class="text-muted small">Completion Rate</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="bg-white border rounded-3 p-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                <i class="fa-solid fa-star"></i>
            </div>
            <div>
                <div class="h5 fw-bold mb-0">{{ $stats['average_score'] ?? 0 }}%</div>
                <div class="text-muted small">Average Score</div>
            </div>
        </div>
    </div>
</div>

<!-- Enrolled Courses Section -->
<div class="mb-3">
    <h3 class="h5 fw-bold mb-1">Enrolled Courses</h3>
    <p class="text-muted small mb-0">Courses that {{ explode(' ', $student['name'])[0] }} is currently taking or has completed.</p>
</div>

<div class="student-courses-container view-list">
    @forelse($enrolledCourses as $course)
        @php
            $status = $course['status'] ?? 'not_started';
            $statusLabel = match ($status) {
                'completed' => 'Completed',
                'in_progress' => 'In Progress',
                default => 'Not Started',
            };
            $statusBadgeClass = match ($status) {
                'completed' => 'bg-success-subtle text-success border border-success-subtle',
                'in_progress' => 'bg-primary-subtle text-primary border border-primary-subtle',
                default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            };
            $progress = (int) ($course['progress'] ?? 0);
        @endphp
        <div class="student-course-row">
            <div class="student-course-thumb-wrap">
                <img src="{{ $course['image'] }}" alt="{{ $course['title'] }}" loading="lazy">
            </div>

            <div class="student-course-info">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill {{ $statusBadgeClass }}" style="font-size: 11px; font-weight: 600;">
                        {{ $statusLabel }}
                    </span>
                </div>

                <h5 class="student-course-title mb-2">{{ $course['title'] }}</h5>

                <div class="mb-2" style="max-width: 360px;">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Progress</span>
                        <span class="fw-semibold text-dark">{{ $progress }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar {{ $status === 'completed' ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <div class="student-course-meta-chips">
                    <span class="badge bg-light text-dark border fw-normal">
                        <i class="fa-solid fa-video text-primary me-1"></i>{{ $course['lessons_completed'] }}/{{ $course['total_lessons'] }} Lessons
                    </span>
                    @if(!empty($course['score']))
                        <span class="badge bg-light text-dark border fw-normal">
                            <i class="fa-solid fa-trophy text-warning me-1"></i>Score: {{ $course['score'] }}%
                        </span>
                    @endif
                    <span class="badge bg-light text-dark border fw-normal">
                        <i class="fa-regular fa-calendar text-secondary me-1"></i>Started {{ date('M d, Y', strtotime($course['started_date'])) }}
                    </span>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5 px-3 bg-white border rounded-3">
            <div class="mb-3 text-muted">
                <i class="fa-solid fa-book-open fa-2x"></i>
            </div>
            <h5 class="fw-semibold mb-1">No Enrolled Courses</h5>
            <p class="text-muted small mb-0">This student has not enrolled in any of your courses yet.</p>
        </div>
    @endforelse
</div>
@endsection
