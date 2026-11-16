@if($courses->count() > 0)
    @foreach($courses as $course)
        @php
            $avgRating = (float) ($course->average_rating ?? $course->rating ?? 5.0);
            if ($avgRating <= 0) {
                $avgRating = 5.0;
            }
            $roundedStars = (int) round($avgRating);
            $studentsCount = (int) ($course->students_count ?? 0);
            $lessonCount = (int) ($course->computed_lessons_count ?? $course->lessons_count ?? $course->topics->sum(fn($t) => $t->lessons->count()));
            if ($lessonCount <= 0) {
                $lessonCount = 12;
            }
            $durationMins = (int) ($course->computed_duration_minutes ?? ($lessonCount * 25));
            $hours = intdiv($durationMins, 60);
            $mins = $durationMins % 60;
            $durationText = ($hours > 0 ? $hours . 'h ' : '') . ($mins > 0 ? $mins . 'm' : ($hours === 0 ? '45m' : '0m'));

            $isFree = $course->pricing_model === 'free' || ((float) ($course->regular_price ?? 0) <= 0 && (float) ($course->sale_price ?? 0) <= 0);
            $hasDiscount = !$isFree && $course->sale_price !== null && (float) $course->sale_price > 0 && (float) $course->regular_price > (float) $course->sale_price;
            $discountPercent = $hasDiscount
                ? max(1, (int) round(((($course->regular_price - $course->sale_price) / $course->regular_price) * 100)))
                : 0;

            $instructorName = $course->instructor->name ?? __('frontend.unknown_instructor');
            $instructorAvatar = $course->instructor->profile_photo_url ?? null;
            $initials = collect(explode(' ', trim($instructorName)))
                ->filter()
                ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->take(2)
                ->implode('');
        @endphp
        <div class="{{ $colClass ?? 'col-xl-4 col-md-6' }}">
            <div class="lmsout-catalog-card">
                <div class="lmsout-catalog-card__thumb">
                    <a href="{{ route('courses.show', $course->id) }}" class="lmsout-catalog-card__thumb-link">
                        <img src="{{ $course->thumbnail }}" alt="{{ $course->title }}" loading="lazy">
                    </a>

                    <div class="lmsout-catalog-card__badges-tl">
                        @if($course->category)
                            <span class="lmsout-catalog-card__badge-cat">{{ $course->category }}</span>
                        @endif
                        @if($course->isFaceToFace())
                            <span class="lmsout-catalog-card__badge-cat" style="background:#013BA6;color:#fff;">Face-to-Face</span>
                        @elseif($course->isHybrid())
                            <span class="lmsout-catalog-card__badge-cat" style="background:#228DDD;color:#fff;">Hybrid</span>
                        @endif
                    </div>

                    <div class="lmsout-catalog-card__badges-tr">
                        @if($isFree)
                            <span class="lmsout-catalog-card__badge-free">{{ __('frontend.free') }}</span>
                        @elseif($hasDiscount)
                            <span class="lmsout-catalog-card__badge-discount">-{{ $discountPercent }}%</span>
                        @elseif($course->is_live_course)
                            <span class="lmsout-catalog-card__badge-discount">Live</span>
                        @endif
                    </div>

                    <a href="{{ route('courses.show', $course->id) }}" class="lmsout-catalog-card__wish-btn" title="{{ $course->title }}">
                        <i class="fa-solid fa-heart"></i>
                    </a>
                </div>

                <div class="lmsout-catalog-card__body">
                    <div class="lmsout-catalog-card__rating-row">
                        <div class="lmsout-catalog-card__stars">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fa-solid fa-star {{ $i <= $roundedStars ? 'is-filled' : 'is-empty' }}"></i>
                            @endfor
                            <span class="lmsout-catalog-card__rating-num">{{ number_format($avgRating, 1) }}</span>
                        </div>
                        <span class="lmsout-catalog-card__students">
                            {{ $studentsCount }} {{ $studentsCount === 1 ? 'student' : 'students' }}
                        </span>
                    </div>

                    <h4 class="lmsout-catalog-card__title">
                        <a href="{{ route('courses.show', $course->id) }}">{{ $course->title }}</a>
                    </h4>

                    <div class="lmsout-catalog-card__instructor">
                        @if(!empty($course->instructor->avatar))
                            <img src="{{ $course->instructor->profile_photo_url }}" alt="{{ $instructorName }}" class="lmsout-catalog-card__avatar">
                        @else
                            <span class="lmsout-catalog-card__avatar-initials">{{ $initials ?: 'IN' }}</span>
                        @endif
                        <span class="lmsout-catalog-card__instructor-name">{{ $instructorName }}</span>
                    </div>

                    <div class="lmsout-catalog-card__meta">
                        <span><i class="fa-regular fa-circle-play"></i> {{ $lessonCount }} {{ $lessonCount === 1 ? 'lesson' : 'lessons' }}</span>
                        <span><i class="fa-regular fa-clock"></i> {{ $course->duration_label ?: $durationText }}</span>
                        @if($course->requiresApplicantForm() && $course->defaultBranch)
                            <span><i class="fa-solid fa-location-dot"></i> {{ $course->defaultBranch->name }}</span>
                        @else
                            <span><i class="fa-solid fa-signal"></i> {{ ucfirst($course->difficulty ?? 'Beginner') }}</span>
                        @endif
                    </div>

                    <div class="lmsout-catalog-card__footer">
                        <div class="lmsout-catalog-card__pricing">
                            <div class="lmsout-catalog-card__price-row">
                                @if($isFree)
                                    <span class="lmsout-catalog-card__price is-free">{{ __('frontend.free') }}</span>
                                @elseif($hasDiscount)
                                    <span class="lmsout-catalog-card__price">{{ $course->formatPrice($course->sale_price, 2, 'symbol') }}</span>
                                    <span class="lmsout-catalog-card__price-old">{{ $course->formatPrice($course->regular_price, 2, 'symbol') }}</span>
                                @else
                                    <span class="lmsout-catalog-card__price">{{ $course->formatPrice($course->regular_price, 2, 'symbol') }}</span>
                                @endif
                            </div>
                        </div>

                        @if($course->requiresApplicantForm())
                            <a href="{{ route('courses.apply', $course->id) }}" class="lmsout-catalog-card__view-btn">
                                Apply
                            </a>
                        @else
                            <a href="{{ route('courses.show', $course->id) }}" class="lmsout-catalog-card__view-btn">
                                View
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="col-12">
        <div class="lmsout-catalog-empty text-center py-5">
            <div class="mb-3">
                <i class="fa-regular fa-folder-open fa-3x text-muted"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">{{ __('frontend.no_courses_found') }}</h5>
            <p class="text-muted small mb-0">Try adjusting your search or filter criteria to find more courses.</p>
        </div>
    </div>
@endif
