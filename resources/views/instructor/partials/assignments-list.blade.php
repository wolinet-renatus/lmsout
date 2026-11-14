@forelse($assignments as $assignment)
    @php
        $pendingCount = (int) ($assignment->pending_submissions_count ?? 0);
        $gradedCount = (int) ($assignment->graded_submissions_count ?? 0);
        $totalSubs = (int) ($assignment->submissions_count ?? 0);
        $courseTitle = optional(optional($assignment->topic)->course)->title ?? 'Course';

        if ($pendingCount > 0) {
            $statusLabel = 'Needs Review';
            $statusBadgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
        } elseif ($gradedCount > 0) {
            $statusLabel = 'Graded';
            $statusBadgeClass = 'bg-success-subtle text-success border border-success-subtle';
        } else {
            $statusLabel = 'No Submissions';
            $statusBadgeClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
        }
    @endphp
    <div class="student-course-row" data-assignment-id="{{ $assignment->id }}">
        <div class="student-course-thumb-wrap d-flex align-items-center justify-content-center bg-primary-subtle text-primary">
            <i class="fa-solid fa-file-lines fa-2x"></i>
        </div>

        <div class="student-course-info">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge rounded-pill {{ $statusBadgeClass }}" style="font-size: 11px; font-weight: 600;">
                    {{ $statusLabel }}
                </span>
                <span class="text-muted small">
                    <i class="fa-solid fa-graduation-cap me-1"></i>{{ $courseTitle }}
                </span>
            </div>

            <h5 class="student-course-title mb-1">
                <a href="{{ route('instructor.assignments.show', $assignment->id) }}" class="text-decoration-none text-dark">
                    {{ $assignment->title }}
                </a>
            </h5>

            <p class="text-muted small mb-2">{{ $assignment->short_description }}</p>

            <div class="student-course-meta-chips">
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-inbox text-primary me-1"></i>{{ $totalSubs }} {{ $totalSubs === 1 ? 'Submission' : 'Submissions' }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-hourglass-half text-warning me-1"></i>{{ $pendingCount }} Pending
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-circle-check text-success me-1"></i>{{ $gradedCount }} Graded
                </span>
                @if(!empty($assignment->max_score))
                    <span class="badge bg-light text-dark border fw-normal">
                        <i class="fa-solid fa-award text-info me-1"></i>{{ $assignment->max_score }} pts
                    </span>
                @endif
            </div>
        </div>

        <div class="student-course-actions">
            <a href="{{ route('instructor.assignments.show', $assignment->id) }}" class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-eye me-1"></i>Review
            </a>

            <div class="dropdown">
                <button
                    class="btn btn-light btn-sm border"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="More actions for {{ $assignment->title }}"
                >
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item" href="{{ route('instructor.assignments.show', $assignment->id) }}">
                            <i class="fa-solid fa-list-check me-2 text-primary"></i>All Submissions
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('instructor.assignments.show', [$assignment->id, 'status' => 'pending']) }}">
                            <i class="fa-solid fa-hourglass-half me-2 text-warning"></i>Pending Review ({{ $pendingCount }})
                        </a>
                    </li>
                    @if(!empty($assignment->due_date))
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <span class="dropdown-item-text small text-muted">
                                <i class="fa-regular fa-calendar me-1"></i>Due {{ \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') }}
                            </span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@empty
    <div class="text-center py-5 px-3 bg-white border rounded-3">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-clipboard-list fa-2x"></i>
        </div>
        @if(!empty($currentSearch) || ($currentStatus ?? 'all') !== 'all' || !empty($selectedCourseId))
            <h5 class="fw-semibold mb-1">No assignments match your filters</h5>
            <p class="text-muted small mb-3">Try adjusting your search query, course, or status filter.</p>
            <button type="button" id="emptyStateResetBtn" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset Filters
            </button>
        @else
            <h5 class="fw-semibold mb-1">No assignments found</h5>
            <p class="text-muted small mb-0">Assignments created inside your course curriculum will appear here.</p>
        @endif
    </div>
@endforelse

