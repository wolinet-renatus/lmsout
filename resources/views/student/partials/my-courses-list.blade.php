@forelse($enrollments as $enrollment)
    @php
        $statusKey = 'in_progress';
        if ($enrollment['is_completed']) {
            $statusKey = 'completed';
        } elseif ($enrollment['is_pending']) {
            $statusKey = 'pending';
        }
        $accessUrl = $enrollment['course_id'] ? route('student.courses.access', $enrollment['course_id']) : '#';
        $courseUrl = $enrollment['course_id'] ? route('courses.show', $enrollment['course_id']) : '#';
        $discussionsUrl = $enrollment['course_id'] ? route('student.courses.discussions.index', $enrollment['course_id']) : '#';
        $imagePath = $enrollment['featured_image']
            ? asset('storage/' . $enrollment['featured_image'])
            : asset('assets/front/img/home-1/courses/courses-01.jpg');
    @endphp
    <div class="student-course-row"
         data-status="{{ $statusKey }}"
         data-title="{{ strtolower($enrollment['course_title']) }}"
         data-instructor="{{ strtolower($enrollment['instructor_name']) }}">
        
        <!-- Left: Compact Thumbnail -->
        <div class="student-course-thumb-wrap">
            <img src="{{ $imagePath }}" alt="{{ $enrollment['course_title'] }}" onerror="this.src='{{ asset('assets/front/img/home-1/courses/courses-01.jpg') }}'">
        </div>

        <!-- Center: Course Identity & Micro Details -->
        <div class="student-course-info">
            <h3 class="student-course-title">
                <a href="{{ $accessUrl }}">{{ $enrollment['course_title'] }}</a>
            </h3>
            <div class="student-course-meta-chips">
                @if($enrollment['course_category'])
                    <span class="badge bg-light text-dark border">{{ $enrollment['course_category'] }}</span>
                @endif
                <span>
                    <i class="fa-solid fa-circle-play text-primary me-1"></i> {{ $enrollment['lesson_count'] }} Lessons
                </span>
                <span>
                    <i class="fa-solid fa-chalkboard-user text-muted me-1"></i> {{ $enrollment['instructor_name'] }}
                </span>
                
                <!-- Status Chip -->
                @if ($enrollment['is_completed'])
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                        <i class="fa-solid fa-circle-check me-1"></i> Completed
                    </span>
                @elseif($enrollment['is_approved'])
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                        <i class="fa-solid fa-book-open-reader me-1"></i> In Progress
                    </span>
                @elseif($enrollment['is_pending'])
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">
                        <i class="fa-solid fa-clock me-1"></i> Pending
                    </span>
                @endif

                @if ($enrollment['is_live_course'])
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                        <i class="fa-solid fa-broadcast-tower me-1"></i> Live Session
                    </span>
                @endif
            </div>
        </div>

        <!-- Right: Primary Action + Progressive Disclosure Dropdown -->
        <div class="student-course-actions">
            @if ($enrollment['is_approved'] || $enrollment['is_completed'])
                <a href="{{ $accessUrl }}" class="btn btn-primary btn-sm rounded-pill px-3 py-1 fw-semibold text-nowrap">
                    <i class="fa-solid fa-play me-1" style="font-size: 11px;"></i> Continue
                </a>
            @else
                <button class="btn btn-light btn-sm text-muted rounded-pill px-3 py-1 border text-nowrap" disabled>
                    <i class="fa-solid fa-clock me-1"></i> Pending
                </button>
            @endif

            <!-- Hidden Until Needed: More Options Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light btn-sm rounded-circle border d-flex align-items-center justify-content-center"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        style="width: 32px; height: 32px; padding: 0;"
                        title="More Options">
                    <i class="fa-solid fa-ellipsis-vertical text-muted" style="font-size: 12px;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-1" style="min-width: 190px; border-radius: 10px;">
                    <li>
                        <a class="dropdown-item py-2" href="{{ $accessUrl }}" style="font-size: 13px;">
                            <i class="fa-solid fa-book-open text-primary me-2"></i> Launch Classroom
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ $courseUrl }}" target="_blank" style="font-size: 13px;">
                            <i class="fa-solid fa-circle-info text-secondary me-2"></i> Course Overview
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ $discussionsUrl }}" style="font-size: 13px;">
                            <i class="fa-solid fa-comments text-info me-2"></i> Study Discussion
                        </a>
                    </li>
                    @if ($enrollment['is_completed'])
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item py-2 text-success" href="{{ route('student.certificates') }}" style="font-size: 13px;">
                                <i class="fa-solid fa-certificate me-2"></i> View Certificate
                            </a>
                        </li>
                    @endif
                    @if ($enrollment['enrolled_at'])
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="px-3 py-1">
                            <span class="text-muted" style="font-size: 11px;">
                                Enrolled: {{ $enrollment['enrolled_at']->format('M d, Y') }}
                            </span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@empty
    <div class="out-card p-5 text-center">
        <div class="out-empty-state-icon mb-2">
            <i class="fa-solid fa-filter-circle-xmark text-muted" style="font-size: 36px;"></i>
        </div>
        <h5 class="fw-bold mb-1">No courses found</h5>
        <p class="text-muted mb-3" style="font-size: 13.5px;">No courses match your active search or filter criteria.</p>
        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1" id="emptyStateResetBtn">
            Reset Filters
        </button>
    </div>
@endforelse

