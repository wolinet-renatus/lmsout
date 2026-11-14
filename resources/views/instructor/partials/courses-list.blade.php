@forelse($courses as $course)
    @php
        $status = $course['status'] ?? 'draft';
        $statusLabel = match ($status) {
            'published' => 'Published',
            'pending' => 'Pending Review',
            default => 'Draft',
        };
        $statusBadgeClass = match ($status) {
            'published' => 'bg-success-subtle text-success border border-success-subtle',
            'pending' => 'bg-warning-subtle text-warning border border-warning-subtle',
            default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        };
        $isFree = ($course['pricing_model'] ?? 'paid') === 'free';
    @endphp
    <div class="student-course-row" data-course-id="{{ $course['id'] }}">
        <div class="student-course-thumb-wrap">
            <img src="{{ $course['image_url'] }}" alt="{{ $course['title'] }}" loading="lazy">
            @if(!empty($course['is_live_course']))
                <span class="badge bg-danger position-absolute top-0 start-0 m-2" style="font-size: 11px;">
                    <i class="fa-solid fa-tower-broadcast me-1"></i>Live
                </span>
            @endif
        </div>

        <div class="student-course-info">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge rounded-pill {{ $statusBadgeClass }}" style="font-size: 11px; font-weight: 600;">
                    {{ $statusLabel }}
                </span>
                <span class="text-muted small">
                    <i class="fa-solid fa-folder-open me-1"></i>{{ $course['category'] }}
                </span>
            </div>

            <h5 class="student-course-title mb-2">
                <a href="{{ route('instructor.courses.edit', $course['id']) }}" class="text-decoration-none text-dark">
                    {{ $course['title'] }}
                </a>
            </h5>

            <div class="student-course-meta-chips">
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-book-open text-primary me-1"></i>{{ (int) $course['lessons'] }} {{ (int) $course['lessons'] === 1 ? 'Lesson' : 'Lessons' }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-users text-info me-1"></i>{{ (int) $course['students'] }} {{ (int) $course['students'] === 1 ? 'Student' : 'Students' }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-tag text-success me-1"></i>{{ $isFree ? 'Free' : $course['price'] }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-star text-warning me-1"></i>{{ number_format((float) $course['rating'], 1) }}
                </span>
            </div>
        </div>

        <div class="student-course-actions">
            <a href="{{ route('instructor.courses.edit', $course['id']) }}" class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-sliders me-1"></i>Manage
            </a>

            <div class="dropdown">
                <button
                    class="btn btn-light btn-sm border"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="More actions for {{ $course['title'] }}"
                >
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item" href="{{ route('instructor.courses.edit', $course['id']) }}">
                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i>Edit Course
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('courses.show', $course['id']) }}" target="_blank" rel="noopener">
                            <i class="fa-solid fa-arrow-up-right-from-square me-2 text-secondary"></i>View Public Page
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('instructor.discussions.index') }}">
                            <i class="fa-solid fa-comments me-2 text-info"></i>Course Forums
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <span class="dropdown-item-text small text-muted">
                            <i class="fa-regular fa-clock me-1"></i>Updated {{ $course['updated_at'] }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@empty
    <div class="text-center py-5 px-3 bg-white border rounded-3">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-layer-group fa-2x"></i>
        </div>
        @if(!empty($currentSearch) || ($currentStatus ?? 'all') !== 'all')
            <h5 class="fw-semibold mb-1">No courses match your filters</h5>
            <p class="text-muted small mb-3">Try adjusting your search query or status filter to see more courses.</p>
            <button type="button" id="emptyStateResetBtn" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset Filters
            </button>
        @else
            <h5 class="fw-semibold mb-1">No courses created yet</h5>
            <p class="text-muted small mb-3">Create your first course to start publishing lessons and enrolling students.</p>
            <a href="{{ route('instructor.courses.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>Create New Course
            </a>
        @endif
    </div>
@endforelse

