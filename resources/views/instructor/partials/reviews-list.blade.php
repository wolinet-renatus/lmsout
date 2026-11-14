@forelse($reviews as $review)
    @php
        $avatar = optional($review->user)->profile_photo
            ? asset('storage/' . $review->user->profile_photo)
            : asset('assets/front/img/home-1/courses/client-01.png');
        $studentName = optional($review->user)->name ?? 'Student';
        $courseTitle = optional($review->course)->title ?? 'Course';
        $ratingVal = (int) $review->rating;
    @endphp
    <div class="student-course-row" data-review-id="{{ $review->id }}">
        <div class="student-course-thumb-wrap d-flex align-items-center justify-content-center bg-light">
            <img src="{{ $avatar }}" alt="{{ $studentName }}" loading="lazy" style="object-fit: cover;">
        </div>

        <div class="student-course-info">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge rounded-pill bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 11px; font-weight: 600;">
                    <i class="fa-solid fa-star text-warning me-1"></i>{{ number_format($ratingVal, 1) }}
                </span>
                <span class="text-muted small">
                    <i class="fa-solid fa-graduation-cap me-1"></i>{{ $courseTitle }}
                </span>
                <span class="text-muted small">
                    <i class="fa-regular fa-calendar me-1"></i>{{ $review->created_at->format('M d, Y') }}
                </span>
            </div>

            <h5 class="student-course-title mb-1">{{ $studentName }}</h5>
            <p class="text-muted small mb-2">{{ $review->comment }}</p>

            @if($review->replies->count() > 0)
                <div class="mt-2 pt-2 border-top">
                    @foreach($review->replies as $reply)
                        <div class="bg-light rounded-2 p-2 mb-1 small">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold text-primary">
                                    <i class="fa-solid {{ $reply->is_instructor ? 'fa-chalkboard-user' : 'fa-user' }} me-1"></i>
                                    {{ $reply->is_instructor ? __('instructor.instructor_badge') : __('instructor.student_badge') }}
                                </span>
                                <span class="text-muted" style="font-size: 11px;">{{ $reply->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="text-dark">{{ $reply->reply }}</div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!$review->hasReplied)
                <div class="reply-form-container d-none mt-2 p-3 bg-light border rounded-3" id="reply-form-{{ $review->id }}">
                    <form action="{{ route('instructor.reviews.reply', $review->id) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <textarea
                                name="reply"
                                class="form-control form-control-sm"
                                rows="3"
                                placeholder="{{ __('instructor.write_reply_placeholder', ['name' => $studentName]) }}"
                                required
                            ></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light btn-sm border cancel-reply-btn" data-review-id="{{ $review->id }}">
                                {{ __('instructor.cancel_reply') }}
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-paper-plane me-1"></i>{{ __('instructor.send_reply') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <div class="student-course-actions">
            @if($review->hasReplied)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-2">
                    <i class="fa-solid fa-circle-check me-1"></i>Replied
                </span>
            @else
                <button type="button" class="btn btn-primary btn-sm px-3 toggle-reply-btn" data-review-id="{{ $review->id }}">
                    <i class="fa-solid fa-reply me-1"></i>Reply
                </button>
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-5 px-3 bg-white border rounded-3">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-comments fa-2x"></i>
        </div>
        @if(!empty($currentSearch) || ($currentRating ?? 'all') !== 'all' || !empty($selectedCourseId))
            <h5 class="fw-semibold mb-1">No reviews match your filters</h5>
            <p class="text-muted small mb-3">Try adjusting your search query, course, or rating filter.</p>
            <button type="button" id="emptyStateResetBtn" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset Filters
            </button>
        @else
            <h5 class="fw-semibold mb-1">{{ __('instructor.no_reviews_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('instructor.no_reviews_desc') }}</p>
        @endif
    </div>
@endforelse

