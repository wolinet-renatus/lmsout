@forelse($questions as $question)
    @php
        $avatar = optional($question->user)->profile_photo
            ? asset('storage/' . $question->user->profile_photo)
            : asset('assets/front/img/home-1/courses/client-01.png');
        $authorName = optional($question->user)->name ?? 'Student';
        $answersCount = (int) ($question->answers_count ?? 0);
        $votesSum = (int) ($question->votes_sum_vote ?? 0);
        $viewsCount = (int) ($question->views ?? 0);
    @endphp
    <div class="student-course-row" data-question-id="{{ $question->id }}">
        <div class="student-course-thumb-wrap d-flex align-items-center justify-content-center bg-light">
            <img src="{{ $avatar }}" alt="{{ $authorName }}" loading="lazy" style="object-fit: cover;">
        </div>

        <div class="student-course-info">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge rounded-pill {{ $answersCount > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }}" style="font-size: 11px;">
                    {{ $answersCount > 0 ? 'Answered' : 'Unanswered' }}
                </span>
                @if(!empty($question->category))
                    <span class="text-muted small">
                        <i class="fa-solid fa-tag me-1"></i>{{ $question->category }}
                    </span>
                @endif
                <span class="text-muted small">
                    <i class="fa-regular fa-clock me-1"></i>{{ $question->created_at->diffForHumans() }}
                </span>
            </div>

            <h5 class="student-course-title mb-1">
                <a href="{{ route('student.community.show', $question->id) }}" class="text-decoration-none text-dark">
                    {{ $question->title }}
                </a>
            </h5>

            @if(!empty($question->description))
                <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit(strip_tags($question->description), 130) }}</p>
            @endif

            <div class="student-course-meta-chips">
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-regular fa-user text-primary me-1"></i>{{ $authorName }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-comments text-info me-1"></i>{{ $answersCount }} {{ $answersCount === 1 ? 'Answer' : 'Answers' }}
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-solid fa-arrow-up text-success me-1"></i>{{ $votesSum }} Votes
                </span>
                <span class="badge bg-light text-dark border fw-normal">
                    <i class="fa-regular fa-eye text-secondary me-1"></i>{{ $viewsCount }} Views
                </span>
            </div>
        </div>

        <div class="student-course-actions">
            <a href="{{ route('student.community.show', $question->id) }}" class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-comments me-1"></i>View Thread
            </a>
        </div>
    </div>
@empty
    <div class="text-center py-5 px-3 bg-white border rounded-3">
        <div class="mb-3 text-muted">
            <i class="fa-solid fa-comments fa-2x"></i>
        </div>
        @if(!empty($currentSearch) || ($filter ?? 'all') !== 'all')
            <h5 class="fw-semibold mb-1">{{ __('student.no_questions_found') }}</h5>
            <p class="text-muted small mb-3">Try adjusting your search query or filter.</p>
            <button type="button" id="emptyStateResetBtn" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-rotate-left me-1"></i>Reset Filters
            </button>
        @else
            <h5 class="fw-semibold mb-1">{{ __('student.no_questions_found') }}</h5>
            <p class="text-muted small mb-3">{{ __('student.no_questions_desc') }}</p>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#askQuestionModal">
                <i class="fa-solid fa-plus me-1"></i>{{ __('student.ask_question') }}
            </button>
        @endif
    </div>
@endforelse

