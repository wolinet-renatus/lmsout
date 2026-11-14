@extends('layouts.app')

@php
    $themeColors = \App\Models\FrontendSetting::getValue('theme.colors', [
        'primary' => '#013ba6',
        'secondary' => '#0f172a',
        'accent' => '#228ddd',
    ]);
@endphp

@push('styles')
<style>
    :root {
        --edtech-primary: {{ $themeColors['primary'] ?? '#013ba6' }};
        --edtech-secondary: {{ $themeColors['secondary'] ?? '#0f172a' }};
        --edtech-accent: {{ $themeColors['accent'] ?? '#228ddd' }};
    }
    .edtechx-hero {
        background: radial-gradient(circle at top right, rgba(6, 182, 212, 0.18), transparent 45%),
                    linear-gradient(135deg, var(--edtech-secondary) 0%, #228ddd 100%);
        color: #fff;
        padding: 95px 0 80px;
        position: relative;
        overflow: hidden;
    }
    .edtechx-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #67e8f9;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .edtechx-title {
        font-size: clamp(2.2rem, 4vw, 3.5rem);
        font-weight: 800;
        line-height: 1.15;
        color: #fff;
        margin-bottom: 20px;
    }
    .edtechx-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 16px;
        margin-top: 36px;
    }
    .edtechx-stat-card {
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 14px;
        padding: 18px;
        backdrop-filter: blur(8px);
    }
    .edtechx-stat-card h3 { color: #fff; font-weight: 800; margin-bottom: 4px; font-size: 26px; }
    .edtechx-stat-card span { color: #cbd5e1; font-size: 13px; }
    .edtechx-cat-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        transition: all .2s ease;
        height: 100%;
    }
    .edtechx-cat-card:hover {
        border-color: var(--edtech-primary);
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
    }
    .edtechx-course-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        transition: all .2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .edtechx-course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.1);
    }
</style>
@endpush

@section('content')
    <!-- EdTechX Hero Section -->
    <section class="edtechx-hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <div class="edtechx-pill">
                        <i class="fa-solid fa-bolt"></i> {{ $hero['preheading'] }}
                    </div>
                    <h1 class="edtechx-title">{{ $hero['heading'] }}</h1>
                    <p style="font-size: 17px; color: #cbd5e1; max-width: 620px; margin-bottom: 28px;">
                        {{ $hero['description'] }}
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ $hero['cta_url'] }}" class="theme-btn" style="background: var(--edtech-primary); color: #fff; padding: 14px 28px; border-radius: 10px; font-weight: 700;">
                            {{ $hero['cta_label'] }} <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                        <a href="{{ route('courses') }}" class="btn btn-outline-light" style="padding: 14px 26px; border-radius: 10px; font-weight: 600;">
                            Browse Courses
                        </a>
                    </div>

                    <div class="edtechx-stat-grid">
                        <div class="edtechx-stat-card">
                            <h3>{{ number_format($stats['courses'] ?? 0) }}+</h3>
                            <span>Published Courses</span>
                        </div>
                        <div class="edtechx-stat-card">
                            <h3>{{ number_format($stats['students'] ?? 0) }}+</h3>
                            <span>Active Learners</span>
                        </div>
                        <div class="edtechx-stat-card">
                            <h3>{{ number_format($stats['instructors'] ?? 0) }}+</h3>
                            <span>Expert Mentors</span>
                        </div>
                        <div class="edtechx-stat-card">
                            <h3>{{ number_format($stats['enrollments'] ?? 0) }}+</h3>
                            <span>Course Enrollments</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 text-center">
                    <img src="{{ asset('assets/front/img/home-1/hero/hero-1.png') }}" alt="EdTechX Hero" class="img-fluid" style="max-height: 440px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Top Categories -->
    <section class="section-padding">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end flex-wrap mb-4">
                <div>
                    <span class="text-uppercase fw-bold small" style="color: var(--edtech-primary); letter-spacing: 1.5px;">Skill Tracks</span>
                    <h2 class="fw-bold mt-1">Explore High-Demand Categories</h2>
                </div>
                <a href="{{ route('courses') }}" class="btn btn-outline-primary rounded-pill px-4">All Categories</a>
            </div>
            <div class="row g-4">
                @foreach($topCategories as $cat)
                    <div class="col-xl-4 col-md-6">
                        <a href="{{ route('courses', ['category' => $cat['slug']]) }}" class="text-decoration-none">
                            <div class="edtechx-cat-card">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span style="width: 48px; height: 48px; border-radius: 12px; background: #eef2ff; color: var(--edtech-primary); display: inline-flex; align-items: center; justify-content: center; font-size: 20px;">
                                        <i class="{{ $cat['icon_class'] }}"></i>
                                    </span>
                                    <span class="badge bg-light text-dark">{{ $cat['courses_count'] }} Courses</span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">{{ $cat['name'] }}</h5>
                                <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit($cat['description'], 90) }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Featured Courses -->
    <section class="section-padding section-bg">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end flex-wrap mb-4">
                <div>
                    <span class="text-uppercase fw-bold small" style="color: var(--edtech-primary); letter-spacing: 1.5px;">Featured Catalog</span>
                    <h2 class="fw-bold mt-1">Career-Ready Programs</h2>
                </div>
                <a href="{{ route('courses') }}" class="theme-btn style-2">Browse All Courses</a>
            </div>
            <div class="row g-4">
                @foreach($featuredCourses as $course)
                    <div class="col-lg-4 col-md-6">
                        <div class="edtechx-course-card">
                            <img src="{{ $course->display_image }}" alt="{{ $course->title }}" style="height: 210px; width: 100%; object-fit: cover;">
                            <div class="p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-light text-primary">{{ $course->display_category }}</span>
                                    <span class="fw-bold" style="color: var(--edtech-primary);">
                                        {{ $course->is_free ? 'Free' : format_currency($course->display_price) }}
                                    </span>
                                </div>
                                <h5 class="fw-bold mb-2">
                                    <a href="{{ route('courses.show', $course->id) }}" class="text-dark text-decoration-none">{{ $course->title }}</a>
                                </h5>
                                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center small text-muted">
                                    <span><i class="fa-solid fa-user-tie me-1"></i> {{ $course->instructor?->name ?? 'Instructor' }}</span>
                                    <span><i class="fa-solid fa-star text-warning me-1"></i> {{ $course->average_rating ?? '5.0' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection

