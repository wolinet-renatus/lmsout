@extends('layouts.app')

@php
    $themeColors = \App\Models\FrontendSetting::getValue('theme.colors', [
        'primary' => '#013ba6',
        'secondary' => '#0f172a',
        'accent' => '#f4ed2d',
    ]);
@endphp

@push('styles')
<style>
    :root {
        --mentora-primary: {{ $themeColors['primary'] ?? '#013ba6' }};
        --mentora-secondary: {{ $themeColors['secondary'] ?? '#0f172a' }};
        --mentora-accent: {{ $themeColors['accent'] ?? '#f4ed2d' }};
    }
    .mentora-hero {
        background: linear-gradient(180deg, #f8fafc 0%, #eef5fc 100%);
        padding: 90px 0 75px;
        border-bottom: 1px solid #e2e8f0;
    }
    .mentora-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 999px;
        background: #ccfbf1;
        color: #013ba6;
        font-weight: 700;
        font-size: 12.5px;
        margin-bottom: 18px;
    }
    .mentora-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        padding: 28px;
        transition: all .2s ease;
        height: 100%;
    }
    .mentora-card:hover {
        border-color: var(--mentora-primary);
        box-shadow: 0 16px 32px rgba(15, 118, 110, 0.08);
    }
</style>
@endpush

@section('content')
    <!-- Mentora Executive Hero -->
    <section class="mentora-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="mentora-badge"><i class="fa-solid fa-award me-1"></i> {{ $hero['preheading'] }}</span>
                    <h1 style="font-size: clamp(2.2rem, 3.8vw, 3.3rem); font-weight: 800; color: #0f172a; line-height: 1.18; margin-bottom: 18px;">
                        {{ $hero['heading'] }}
                    </h1>
                    <p style="font-size: 17px; color: #475569; margin-bottom: 28px;">
                        {{ $hero['description'] }}
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ $hero['cta_url'] }}" class="theme-btn" style="background: var(--mentora-primary); color: #fff; padding: 14px 28px; border-radius: 999px; font-weight: 700;">
                            {{ $hero['cta_label'] }}
                        </a>
                        <a href="{{ route('instructors') }}" class="btn btn-outline-dark" style="padding: 14px 28px; border-radius: 999px; font-weight: 600;">
                            Meet Our Mentors
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="mentora-card text-center">
                                <h2 class="fw-bold" style="color: var(--mentora-primary);">{{ number_format($stats['courses'] ?? 0) }}+</h2>
                                <p class="text-muted mb-0">Accredited Courses</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mentora-card text-center">
                                <h2 class="fw-bold" style="color: var(--mentora-primary);">{{ number_format($stats['instructors'] ?? 0) }}+</h2>
                                <p class="text-muted mb-0">1-on-1 Industry Mentors</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mentora-card text-center">
                                <h2 class="fw-bold" style="color: var(--mentora-primary);">{{ number_format($stats['students'] ?? 0) }}+</h2>
                                <p class="text-muted mb-0">Global Learners</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mentora-card text-center">
                                <h2 class="fw-bold" style="color: var(--mentora-primary);">{{ number_format($stats['enrollments'] ?? 0) }}+</h2>
                                <p class="text-muted mb-0">Verified Certificates</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mentora Featured Courses -->
    <section class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <span class="mentora-badge">Mentora Curated Pathways</span>
                <h2 class="fw-bold">Master New Skills with Guided Mentorship</h2>
            </div>
            <div class="row g-4">
                @foreach($featuredCourses as $course)
                    <div class="col-lg-4 col-md-6">
                        <div class="mentora-card p-0 overflow-hidden d-flex flex-column">
                            <img src="{{ $course->display_image }}" alt="{{ $course->title }}" style="height: 215px; width: 100%; object-fit: cover;">
                            <div class="p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-light text-dark">{{ $course->display_category }}</span>
                                    <strong style="color: var(--mentora-primary);">
                                        {{ $course->is_free ? 'Free' : format_currency($course->display_price) }}
                                    </strong>
                                </div>
                                <h5 class="fw-bold mb-2">
                                    <a href="{{ route('courses.show', $course->id) }}" class="text-dark text-decoration-none">{{ $course->title }}</a>
                                </h5>
                                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                    <span>{{ $course->instructor?->name ?? 'Mentor' }}</span>
                                    <span><i class="fa-solid fa-star text-warning"></i> {{ $course->average_rating ?? '5.0' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection

