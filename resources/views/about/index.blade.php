@extends('layouts.app')

@section('content')
    <!-- 1. Brand Gradient Hero Banner -->
    <section class="edux-page-hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="edux-page-hero__eyebrow">
                        <i class="fa-solid fa-building-columns"></i> {{ $heroSettings['subheading'] ?? __('frontend.about_us') }}
                    </span>
                    <h1 class="edux-page-hero__title">
                        {{ $heroSettings['heading'] ?? 'Transforming Learning Into Lasting Impact' }}
                    </h1>
                    <p class="edux-page-hero__desc">
                        {{ $heroSettings['description'] ?? 'The Open University of Tanzania provides open, online, and blended higher education—delivering affordable and quality education accessible across all regions.' }}
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ $heroSettings['cta_url'] ?? route('courses') }}" class="edux-btn-primary" style="background: var(--theme-yellow, #f4ed2d); color: #04153b !important;">
                        <span>{{ $heroSettings['cta_label'] ?? __('frontend.explore_courses') }}</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="edux-page-wrap">
        <div class="container">
            <!-- 2. Key Platform Stats Strip -->
            <div class="edux-surface-card mb-5" style="margin-top: -58px; position: relative; z-index: 3;">
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width: 38px; height: 38px; background: var(--theme); color: #ffffff;">
                            <i class="fa-solid fa-book-open"></i>
                        </span>
                        <div style="font-size: 24px; font-weight: 800; color: #0f172a;">{{ number_format($stats['courses'] ?? 0) }}+</div>
                        <div class="text-muted small">{{ __('frontend.active_courses') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width: 38px; height: 38px; background: var(--theme); color: #ffffff;">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </span>
                        <div style="font-size: 24px; font-weight: 800; color: #0f172a;">{{ number_format($stats['instructors'] ?? 0) }}+</div>
                        <div class="text-muted small">{{ __('frontend.expert_instructors') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width: 38px; height: 38px; background: var(--theme); color: #ffffff;">
                            <i class="fa-solid fa-user-graduate"></i>
                        </span>
                        <div style="font-size: 24px; font-weight: 800; color: #0f172a;">{{ number_format($stats['students'] ?? 0) }}+</div>
                        <div class="text-muted small">{{ __('frontend.learners_enrolled') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-2" style="width: 38px; height: 38px; background: var(--theme); color: #ffffff;">
                            <i class="fa-solid fa-globe"></i>
                        </span>
                        <div style="font-size: 24px; font-weight: 800; color: #0f172a;">All Regions</div>
                        <div class="text-muted small">Open &amp; Blended Access</div>
                    </div>
                </div>
            </div>

            <!-- 3. Our Identity & Why Choose Us (2-Column Cards) -->
            <div class="row g-4 mb-5">
                <div class="col-lg-6">
                    <div class="edux-surface-card">
                        <div class="edux-inner-header__eyebrow">OUR IDENTITY</div>
                        <h2 class="fw-bold mb-2" style="font-size: 22px; color: #0f172a;">The Open University of Tanzania</h2>
                        <p class="text-muted small mb-4" style="font-size: 13.5px; line-height: 1.6;">
                            Committed to expanding access to flexible, high-standard university and professional education through innovative digital learning technologies.
                        </p>

                        <div class="row g-3">
                            @foreach([
                                ['icon' => 'fa-solid fa-laptop-file', 'title' => 'Open and online learning', 'desc' => 'Study from anywhere with digital courseware and virtual classrooms.'],
                                ['icon' => 'fa-solid fa-hand-holding-heart', 'title' => 'Affordable and quality education for all', 'desc' => 'Accessible tuition and accredited academic programs.'],
                                ['icon' => 'fa-solid fa-door-open', 'title' => 'Open admission', 'desc' => 'Inclusive pathways designed for lifelong learners and professionals.'],
                                ['icon' => 'fa-solid fa-map-location-dot', 'title' => 'Available in all regions', 'desc' => 'Supported by regional centers across Tanzania and beyond.'],
                                ['icon' => 'fa-solid fa-people-arrows', 'title' => 'Blended Learning', 'desc' => 'Combining one-on-one mentorship with interactive online learning.'],
                                ['icon' => 'fa-solid fa-file-circle-check', 'title' => 'Regular & On Demand Examination', 'desc' => 'Flexible assessment schedules tailored to your readiness.'],
                            ] as $pillar)
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 h-100" style="background: #f8fafc; border: 1px solid #f1f5f9;">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="{{ $pillar['icon'] }}" style="color: var(--theme); font-size: 14px;"></i>
                                            <strong style="font-size: 13px; color: #0f172a;">{{ $pillar['title'] }}</strong>
                                        </div>
                                        <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.45;">{{ $pillar['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="edux-surface-card">
                        <div class="edux-inner-header__eyebrow">{{ $featureSettings['title'] ?? __('frontend.why_choose_us') }}</div>
                        <h2 class="fw-bold mb-2" style="font-size: 22px; color: #0f172a;">
                            {{ $featureSettings['subtitle'] ?? __('frontend.learning_aligns') }}
                        </h2>
                        <p class="text-muted small mb-4" style="font-size: 13.5px; line-height: 1.6;">
                            {{ $featureSettings['description'] ?? __('frontend.unlock_potential') }}
                        </p>

                        @if(!empty($featureSettings['items']))
                            <ul class="list-unstyled d-flex flex-column gap-2 mb-4">
                                @foreach ($featureSettings['items'] as $item)
                                    <li class="d-flex align-items-center gap-2" style="font-size: 13.5px; color: #334155;">
                                        <i class="fa-solid fa-circle-check" style="color: var(--theme);"></i>
                                        <span>{{ $item['text'] ?? '' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="row g-3">
                            @foreach (array_merge($highlightSettings['left'] ?? [], $highlightSettings['right'] ?? []) as $highlight)
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-3 d-flex align-items-center gap-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width: 40px; height: 40px; background: rgba(1, 59, 166, 0.08); color: var(--theme);">
                                            <i class="{{ $highlight['icon'] ?? 'fa-solid fa-star' }}"></i>
                                        </span>
                                        <div>
                                            <div class="fw-bold" style="font-size: 18px; color: #0f172a; line-height: 1.15;">
                                                {{ $highlight['count'] ?? '' }}
                                            </div>
                                            <div class="text-muted" style="font-size: 12px;">{{ $highlight['label'] ?? '' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Top Categories Grid -->
            @if ($topCategories->isNotEmpty())
                <div class="mb-5">
                    <div class="edux-inner-header">
                        <div>
                            <div class="edux-inner-header__eyebrow">{{ __('frontend.top_categories') }}</div>
                            <h2 class="edux-inner-header__title">{{ __('frontend.learning_paths_love') }}</h2>
                        </div>
                        <a href="{{ route('courses') }}" class="edux-btn-outline">
                            Browse Catalog <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="row g-3">
                        @foreach ($topCategories as $category)
                            <div class="col-lg-4 col-md-6">
                                <a href="{{ route('courses', ['category' => $category->name]) }}" class="edux-surface-card edux-surface-card--hover d-flex align-items-center justify-content-between text-decoration-none p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width: 42px; height: 42px; background: rgba(1, 59, 166, 0.08); color: var(--theme);">
                                            <i class="fa-solid fa-layer-group"></i>
                                        </span>
                                        <div>
                                            <h4 class="fw-bold mb-0" style="font-size: 14.5px; color: #0f172a;">{{ $category->name }}</h4>
                                            <span class="text-muted" style="font-size: 12px;">{{ $category->courses_count }} {{ __('frontend.courses') }}</span>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-muted small"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 5. Featured Instructors -->
            @if ($featuredInstructors->isNotEmpty())
                <div class="mb-5">
                    <div class="edux-inner-header">
                        <div>
                            <div class="edux-inner-header__eyebrow">{{ __('frontend.featured_teachers') }}</div>
                            <h2 class="edux-inner-header__title">{{ __('frontend.greatest_teachers') }}</h2>
                        </div>
                        <a href="{{ route('instructors.index') }}" class="edux-btn-outline">
                            All Instructors <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="row g-4">
                        @foreach ($featuredInstructors as $featured)
                            @php
                                $instPhoto = $featured->profile_photo
                                    ? frontend_media($featured->profile_photo, 'assets/front/img/home-1/team/team-01.jpg')
                                    : asset('assets/front/img/home-1/team/team-0' . (($loop->index % 4) + 1) . '.jpg');
                            @endphp
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="edux-surface-card edux-surface-card--hover text-center">
                                    <img src="{{ $instPhoto }}" alt="{{ $featured->name }}"
                                         class="rounded-circle mb-3"
                                         style="width: 76px; height: 76px; object-fit: cover; border: 2.5px solid var(--theme); padding: 2px;">
                                    <h4 class="fw-bold mb-1" style="font-size: 15px;">
                                        <a href="{{ route('instructors.show', $featured) }}" class="text-dark text-decoration-none">{{ $featured->name }}</a>
                                    </h4>
                                    <p class="text-muted small mb-2" style="font-size: 12px;">{{ $featured->specialization ?? 'Instructor' }}</p>
                                    <div class="d-flex justify-content-center gap-3 text-muted pt-2 border-top" style="font-size: 11.5px;">
                                        <span><i class="fa-solid fa-book-open me-1"></i>{{ $featured->courses_count }} {{ __('frontend.courses') }}</span>
                                        <span><i class="fa-solid fa-user-group me-1"></i>{{ number_format($featured->students_count) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 6. Student Testimonials -->
            @if ($recentReviews->isNotEmpty())
                <div>
                    <div class="text-center mb-4">
                        <div class="edux-inner-header__eyebrow">{{ __('frontend.learner_feedback') }}</div>
                        <h2 class="edux-inner-header__title">{{ __('frontend.what_students_say') }}</h2>
                    </div>
                    <div class="row g-4 justify-content-center">
                        @foreach ($recentReviews->take(3) as $review)
                            <div class="col-lg-4 col-md-6">
                                <div class="edux-surface-card d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="mb-2" style="color: #f59e0b; font-size: 12px;">
                                            @for ($i = 0; $i < 5; $i++)
                                                <i class="fa-solid fa-star {{ $i < $review->rating ? '' : 'text-muted opacity-25' }}"></i>
                                            @endfor
                                        </div>
                                        <p class="text-secondary mb-3" style="font-size: 13px; line-height: 1.6;">
                                            “{{ $review->comment_excerpt }}”
                                        </p>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 pt-3 border-top">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-bold text-white flex-shrink-0"
                                              style="width: 36px; height: 36px; background: var(--theme); font-size: 12px;">
                                            {{ strtoupper(substr($review->user->name ?? 'S', 0, 1)) }}
                                        </span>
                                        <div>
                                            <div class="fw-bold" style="font-size: 13px; color: #0f172a;">{{ $review->user->name ?? 'Student' }}</div>
                                            <div class="text-muted" style="font-size: 11px;">{{ $review->course->title ?? 'Course' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
