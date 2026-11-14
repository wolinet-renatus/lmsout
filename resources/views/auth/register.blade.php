@extends('layouts.auth')

@php
    $siteName = site_name();
    $pageTitle = 'Create Account | ' . $siteName;
    $logoUrl = branding_asset(['primary_logo_path', 'dark_logo_path', 'logos.primary'], 'assets/front/img/logo/brand-logo-v3.png');
@endphp

@section('content')
<div class="out-auth-layout">
    <!-- Left: OUT LMS Brand Showcase Panel -->
    <div class="out-auth-showcase">
        <!-- Brand Logo & Header -->
        <a href="{{ route('home') }}" class="out-auth-showcase__brand">
            <span class="out-auth-showcase__brand-badge">
                <img src="{{ $logoUrl }}" alt="{{ $siteName }} Logo">
            </span>
            <span class="out-auth-showcase__brand-name">
                {{ $siteName }}<span>.</span>
            </span>
        </a>

        <!-- Main Showcase Content -->
        <div class="out-auth-showcase__content">
            <div class="out-auth-eyebrow">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Open Admission Across All Regions</span>
            </div>

            <h1 class="out-auth-heading">
                Start learning with <span>The Open University</span> of Tanzania.
            </h1>

            <p class="out-auth-desc">
                Create your student account to access world-class courses, digital libraries, and accredited programs designed for career advancement.
            </p>

            <div class="out-auth-features">
                <div class="out-auth-feature-item">
                    <div class="out-auth-feature-icon">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>
                    <div class="out-auth-feature-text">
                        <h5>Open &amp; Online Learning</h5>
                        <p>Study on your schedule with blended one-on-one and online modules.</p>
                    </div>
                </div>

                <div class="out-auth-feature-item">
                    <div class="out-auth-feature-icon">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <div class="out-auth-feature-text">
                        <h5>Accredited &amp; Recognized</h5>
                        <p>Fully accredited credentials recognized by TCU, HESLB, and employers worldwide.</p>
                    </div>
                </div>

                <div class="out-auth-feature-item">
                    <div class="out-auth-feature-icon">
                        <i class="fa-solid fa-globe"></i>
                    </div>
                    <div class="out-auth-feature-text">
                        <h5>Available Nationwide</h5>
                        <p>Learning centers in every region with regular and on-demand examinations.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Testimonial Quote -->
        <div class="out-auth-quote-card">
            <p class="out-auth-quote-text">
                &ldquo;Enrolling at OUT was the best decision for my career. The flexibility to study online while working full-time is unmatched.&rdquo;
            </p>
            <div class="out-auth-quote-author">
                <div class="out-auth-quote-avatar">BK</div>
                <div>
                    <h6 class="out-auth-quote-name">Baraka Kassim</h6>
                    <p class="out-auth-quote-title">Data Analyst &bull; Arusha, Tanzania</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Registration Form Panel -->
    <div class="out-auth-form-panel">
        <!-- Top Navigation -->
        <div class="out-auth-topbar">
            <a href="{{ route('home') }}" class="out-auth-back-link">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to site</span>
            </a>

            <div class="out-auth-lang-pill">
                <i class="fa-solid fa-globe text-primary"></i>
                <span>English &middot; {{ currency_code() }}</span>
            </div>
        </div>

        <!-- Form Wrapper -->
        <div class="out-auth-form-container">
            <div class="out-auth-form-header">
                <h2 class="out-auth-form-title">Create your account</h2>
                <p class="out-auth-form-sub">Join thousands of students and start learning today.</p>
            </div>

            <form action="{{ route('register') }}" method="POST" id="outRegisterForm">
                @csrf

                <!-- First & Last Name (2 Columns) -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label for="first_name" class="out-form-label">First name</label>
                        <input
                            type="text"
                            name="first_name"
                            id="first_name"
                            value="{{ old('first_name') }}"
                            placeholder="John"
                            class="out-form-input @error('first_name') is-invalid @enderror"
                            required
                            autocomplete="given-name"
                            autofocus
                        >
                        @error('first_name')
                            <div class="invalid-feedback d-block mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-sm-6">
                        <label for="last_name" class="out-form-label">Last name</label>
                        <input
                            type="text"
                            name="last_name"
                            id="last_name"
                            value="{{ old('last_name') }}"
                            placeholder="Doe"
                            class="out-form-input @error('last_name') is-invalid @enderror"
                            required
                            autocomplete="family-name"
                        >
                        @error('last_name')
                            <div class="invalid-feedback d-block mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Email Address -->
                <div class="out-form-group">
                    <label for="email" class="out-form-label">Email address</label>
                    <div class="out-form-input-wrap">
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            placeholder="name@example.com"
                            class="out-form-input @error('email') is-invalid @enderror"
                            required
                            autocomplete="email"
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block mt-1 small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="out-form-group">
                    <label for="password" class="out-form-label">Password</label>
                    <div class="out-form-input-wrap">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="At least 8 characters"
                            class="out-form-input @error('password') is-invalid @enderror"
                            required
                            autocomplete="new-password"
                        >
                        <span class="out-form-toggle-pwd" id="togglePasswordBtn" title="Toggle password visibility">
                            <i class="fa-regular fa-eye"></i>
                        </span>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block mt-1 small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="out-form-group">
                    <label for="password_confirmation" class="out-form-label">Confirm password</label>
                    <div class="out-form-input-wrap">
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            placeholder="Re-type password"
                            class="out-form-input"
                            required
                            autocomplete="new-password"
                        >
                        <span class="out-form-toggle-pwd" id="toggleConfirmPasswordBtn" title="Toggle password visibility">
                            <i class="fa-regular fa-eye"></i>
                        </span>
                    </div>
                </div>

                <!-- Terms & Privacy Agreement -->
                <div class="out-form-row mb-3">
                    <label class="out-form-checkbox-label" for="terms">
                        <input type="checkbox" name="terms" id="terms" class="form-check-input mt-0 @error('terms') is-invalid @enderror" required {{ old('terms') ? 'checked' : '' }}>
                        <span class="small">
                            I agree to the <a href="{{ url('/terms-conditions') }}" target="_blank" class="out-form-forgot-link">Terms of Service</a> &amp; <a href="{{ url('/terms-conditions') }}" target="_blank" class="out-form-forgot-link">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                @error('terms')
                    <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror

                <x-recaptcha-widget class="mb-3" action="student_register" />

                <!-- Submit Button -->
                <button type="submit" class="out-auth-submit-btn" id="registerSubmitBtn">
                    <span>Create Account</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

                <!-- One-Click Quick Access Hint -->
                <div class="p-3 mt-3 rounded-lg text-center" style="background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 12px;">
                    <p class="mb-1 small text-muted">
                        <i class="fa-solid fa-bolt text-warning mr-1"></i> Testing the platform or need instant demo access?
                    </p>
                    <a href="{{ route('login') }}" class="font-weight-bold" style="color: #013ba6; font-size: 12px; text-decoration: none;">
                        Try One-Click Demo Login as Admin, Instructor or Student &rarr;
                    </a>
                </div>
            </form>
        </div>

        <!-- Panel Footer -->
        <div class="out-auth-form-footer">
            <p class="out-auth-switch-text">
                Already have an account?
                <a href="{{ route('login') }}" class="out-auth-switch-link">Sign in here</a>
            </p>
            <p class="out-auth-copy-text">
                &copy; {{ date('Y') }} The Open University of Tanzania. All rights reserved.
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        setupPasswordToggle('#password', '#togglePasswordBtn');
        setupPasswordToggle('#password_confirmation', '#toggleConfirmPasswordBtn');

        const form = document.getElementById('outRegisterForm');
        const submitBtn = document.getElementById('registerSubmitBtn');

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span>Creating account...</span> <i class="fa-solid fa-circle-notch fa-spin"></i>`;
            });
        }
    });
</script>
@endpush
