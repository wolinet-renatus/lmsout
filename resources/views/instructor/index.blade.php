@php use Illuminate\Support\Str; @endphp
@extends('layouts.app')

@section('content')
    <section class="lmsout-page-wrap">
        <div class="container">
            <!-- Clean Catalog Header -->
            <div class="lmsout-inner-header">
                <div>
                    <div class="lmsout-inner-header__eyebrow">EXPERT MENTORS &amp; FACULTY</div>
                    <h1 class="lmsout-inner-header__title">
                        Meet Our <span>{{ __('frontend.instructors') }}</span>
                    </h1>
                    <p class="lmsout-inner-header__sub">
                        {{ __('frontend.explore_instructor_desc') }}
                    </p>
                </div>
                @if(Route::has('instructor.become'))
                    <a href="{{ route('instructor.become') }}" class="lmsout-btn-primary">
                        <i class="fa-solid fa-chalkboard-user"></i>
                        <span>Become an Instructor</span>
                    </a>
                @endif
            </div>

            <div class="row g-4">
                @forelse($instructors as $instructor)
                    @php
                        $stats = $instructor->stats;
                        $initials = collect(explode(' ', trim($instructor->name)))
                            ->filter()
                            ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="lmsout-surface-card lmsout-surface-card--hover d-flex flex-column text-center">
                            <div class="mb-3">
                                @if(!empty($instructor->display_avatar))
                                    <img src="{{ $instructor->display_avatar }}" alt="{{ $instructor->name }}"
                                         class="rounded-circle"
                                         style="width: 84px; height: 84px; object-fit: cover; border: 3px solid var(--theme); padding: 2px;">
                                @else
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                                          style="width: 84px; height: 84px; background: var(--theme); font-size: 24px;">
                                        {{ $initials ?: 'IN' }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="fw-bold mb-1" style="font-size: 16px; color: #0f172a;">
                                <a href="{{ route('instructors.show', $instructor) }}" class="text-dark text-decoration-none">
                                    {{ $instructor->name }}
                                </a>
                            </h3>
                            <p class="text-muted small mb-3" style="font-size: 12.5px;">
                                {{ $instructor->headline ?: 'Senior Faculty & Instructor' }}
                            </p>

                            <div class="d-flex justify-content-around align-items-center py-2 px-3 rounded-3 mb-3 mt-auto"
                                 style="background: #f8fafc; border: 1px solid #f1f5f9; font-size: 12px; color: #475569;">
                                <span>
                                    <i class="fa-solid fa-book-open me-1" style="color: var(--theme);"></i>
                                    <strong>{{ $stats['courses'] }}</strong> {{ __('frontend.courses_count') }}
                                </span>
                                <span>
                                    <i class="fa-solid fa-user-group me-1" style="color: var(--theme-2);"></i>
                                    <strong>{{ number_format($stats['students']) }}</strong>
                                </span>
                                <span>
                                    <i class="fa-solid fa-star me-1 text-warning"></i>
                                    <strong>{{ $stats['rating'] ? number_format($stats['rating'], 1) : '5.0' }}</strong>
                                </span>
                            </div>

                            <a href="{{ route('instructors.show', $instructor) }}" class="lmsout-btn-outline w-100">
                                View Profile <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="lmsout-surface-card text-center py-5">
                            <i class="fa-solid fa-chalkboard-user fa-3x text-muted mb-3"></i>
                            <h5 class="fw-bold mb-1">{{ __('frontend.no_instructors_yet') }}</h5>
                        </div>
                    </div>
                @endforelse
            </div>

            @if($instructors->hasPages())
                <div class="mt-5 d-flex justify-content-center">
                    {{ $instructors->links('vendor.pagination.stisla-default') }}
                </div>
            @endif
        </div>
    </section>
@endsection
