@forelse($discussions as $d)
    @php
        $avatar = optional($d->user)->profile_photo
            ? asset('storage/' . $d->user->profile_photo)
            : asset('assets/front/img/home-1/courses/client-01.png');
        $authorName = optional($d->user)->name ?? 'User';
        $courseTitle = optional($d->course)->title ?? 'Course';
    @endphp
    <div class="student-course-row" data-discussion-id="{{ $d->id }}">
        <div class="student-course-thumb-wrap d-flex align-items-center justify-content-center bg-light">
            <img src="{{ $avatar }}" alt="{{ $authorName }}" loading="lazy" style="object-fit: cover;">
        </div>

        <div class="student-course-info">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                @if($d->is_pinned)
                    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 11px;">
                        <i class="fa-solid fa-thumbtack me-1"></i>Pinned
                    </span>
                @endif
                @if($d->is_announcement)
                    <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 11px;">
                        <i class="fa-solid fa-bullhorn me-1"></i>Announcement
                    </span>
                @endif
                <span class="text-muted small">
                    <i class="fa-solid fa-graduation-cap me-1"></i>{{ $courseTitle }}
                </span>
            </div>

            <h5 class="student-course-title mb-1">
                <a href="{{ route('instructor.discussions.show', $d->id) }}" class="text-decoration-none text-dark">
                    {{ $d->title }}
                </a>
            </h5>

            <div class="student-course-meta-chips">
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-regular fa-user text-primary me-1"></i>{{ $authorName }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-comments text-info me-1"></i>{{ (int) $d->replies_count }} {{ (int) $d->replies_count === 1 ? 'Reply' : 'Replies' }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-regular fa-thumbs-up text-success me-1"></i>{{ (int) $d->likes_count }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-regular fa-clock text-secondary me-1"></i>{{ $d->created_at->diffForHumans() }}
                </span>
            </div>
        </div>

        <div class="student-course-actions">
            <a href="{{ route('instructor.discussions.show', $d->id) }}" class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-eye me-1"></i>View
            </a>

            <div class="dropdown">
                <button
                    class="btn btn-light btn-sm border"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="More actions for {{ $d->title }}"
                >
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <a class="dropdown-item" href="{{ route('instructor.discussions.show', $d->id) }}">
                            <i class="fa-solid fa-comments me-2 text-primary"></i>Open Thread
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('instructor.discussions.destroy', $d->id) }}" onsubmit="return confirm('Delete this thread and all replies permanently?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fa-regular fa-trash-can me-2"></i>Delete Thread
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@empty
    <div class="text-center py-5 px-3 bg-white border rounded-3">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-comments fa-2x"></i>
        </div>
        @if(!empty($currentSearch) || ($currentFilter ?? 'all') !== 'all' || !empty($selectedCourseId))
            <h5 class="fw-semibold mb-1">No discussions match your filters</h5>
            <p class="text-muted small mb-3">Try adjusting your search query, course, or filter.</p>
            <button type="button" id="emptyStateResetBtn" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset Filters
            </button>
        @else
            <h5 class="fw-semibold mb-1">No discussion threads yet</h5>
            <p class="text-muted small mb-0">Course discussions and announcements will appear here.</p>
        @endif
    </div>
@endforelse

