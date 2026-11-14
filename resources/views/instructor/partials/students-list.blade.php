@forelse($students as $student)
    @php
        $completedCourses = (int) ($student['completed_courses'] ?? 0);
        $enrolledCourses = (int) ($student['enrolled_courses'] ?? 0);
        $progress = (int) ($student['progress'] ?? 0);
        $isCompleted = $completedCourses > 0;
        $statusLabel = $isCompleted ? 'Completed' : 'In Progress';
        $statusBadgeClass = $isCompleted
            ? 'bg-success-subtle text-success border border-success-subtle'
            : 'bg-primary-subtle text-primary border border-primary-subtle';
    @endphp
    <div class="student-course-row" data-student-id="{{ $student['id'] }}">
        <div class="student-course-thumb-wrap d-flex align-items-center justify-content-center bg-light">
            <img src="{{ $student['avatar'] }}" alt="{{ $student['name'] }}" loading="lazy" style="object-fit: cover;">
        </div>

        <div class="student-course-info">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge rounded-pill {{ $statusBadgeClass }}" style="font-size: 11px; font-weight: 600;">
                    {{ $statusLabel }}
                </span>
                <span class="text-muted small">
                    <i class="fa-regular fa-envelope me-1"></i>{{ $student['email'] }}
                </span>
            </div>

            <h5 class="student-course-title mb-2">
                <a href="{{ route('instructor.students.show', $student['id']) }}" class="text-decoration-none text-dark">
                    {{ $student['name'] }}
                </a>
            </h5>

            <div class="student-course-meta-chips">
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-book-open text-primary me-1"></i>{{ $enrolledCourses }} {{ $enrolledCourses === 1 ? 'Course' : 'Courses' }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-circle-check text-success me-1"></i>{{ $completedCourses }} Completed
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-chart-line text-info me-1"></i>{{ $progress }}% Avg Progress
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-regular fa-clock text-secondary me-1"></i>Active {{ $student['last_active'] }}
                </span>
            </div>
        </div>

        <div class="student-course-actions">
            <a href="{{ route('instructor.students.show', $student['id']) }}" class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-user me-1"></i>View Profile
            </a>

            <div class="dropdown">
                <button
                    class="btn btn-light btn-sm border"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="More actions for {{ $student['name'] }}"
                >
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item" href="{{ route('instructor.students.show', $student['id']) }}">
                            <i class="fa-solid fa-id-card me-2 text-primary"></i>Student Details
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <span class="dropdown-item-text small text-muted">
                            <i class="fa-regular fa-calendar-check me-1"></i>Enrolled {{ $student['enrollment_date'] }}
                        </span>
                    </li>
                    <li>
                        <span class="dropdown-item-text small text-muted">
                            <i class="fa-solid fa-coins me-1"></i>Earned {{ currency_format($student['total_spent'] ?? 0) }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@empty
    <div class="text-center py-5 px-3 bg-white border rounded-3">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-users fa-2x"></i>
        </div>
        @if(!empty($currentSearch) || ($currentStatus ?? 'all') !== 'all' || ($selectedCourse ?? 'all') !== 'all')
            <h5 class="fw-semibold mb-1">No students match your filters</h5>
            <p class="text-muted small mb-3">Try adjusting your search query, course, or status filter.</p>
            <button type="button" id="emptyStateResetBtn" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset Filters
            </button>
        @else
            <h5 class="fw-semibold mb-1">No enrolled students yet</h5>
            <p class="text-muted small mb-0">Students who enroll in your courses will appear here.</p>
        @endif
    </div>
@endforelse

