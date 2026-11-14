@extends('layouts.auth')

@php
    $siteName = site_name();
    $pageTitle = 'Forgot Password | ' . $siteName;
    $logoUrl = branding_asset(['primary_logo_path', 'dark_logo_path', 'logos.primary'], 'assets/front/img/logo/brand-logo-v3.png');
@endphp

@section('content')
<div class="out-auth-layout">
    <!-- Left: OUT LMS Brand Showcase Panel -->
    <div class="out-auth-showcase">
        <a href="{{ route('home') }}" class="out-auth-showcase__brand">
            <span class="out-auth-showcase__brand-badge">
                <img src="{{ $logoUrl }}" alt="{{ $siteName }} Logo">
            </span>
            <span class="out-auth-showcase__brand-name">
                {{ $siteName }}<span>.</span>
            </span>
        </a>

        <div class="out-auth-showcase__content">
            <div class="out-auth-eyebrow">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Account Security &amp; Access</span>
            </div>

            <h1 class="out-auth-heading">
                Reset your account <span>password</span>.
            </h1>

            <p class="out-auth-desc">
                Don't worry &mdash; enter your registered email address and we'll send you a secure link to recover your account.
            </p>

            <div class="out-auth-features">
                <div class="out-auth-feature-item">
                    <div class="out-auth-feature-icon">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div class="out-auth-feature-text">
                        <h5>Secure Recovery Link</h5>
                        <p>Time-sensitive encrypted link delivered right to your inbox.</p>
                    </div>
                </div>

                <div class="out-auth-feature-item">
                    <div class="out-auth-feature-icon">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <div class="out-auth-feature-text">
                        <h5>Support Available</h5>
                        <p>Need extra help? Our academic helpdesk is available at vc@out.ac.tz.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="out-auth-quote-card">
            <p class="out-auth-quote-text">
                &ldquo;Account recovery is fast, verified, and secure with The Open University of Tanzania.&rdquo;
            </p>
            <div class="out-auth-quote-author">
                <div class="out-auth-quote-avatar">IT</div>
                <div>
                    <h6 class="out-auth-quote-name">OUT Support Services</h6>
                    <p class="out-auth-quote-title">ICT Directorate &bull; Dar es Salaam</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Forgot Password Form Panel -->
    <div class="out-auth-form-panel">
        <div class="out-auth-topbar">
            <a href="{{ route('login') }}" class="out-auth-back-link">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to login</span>
            </a>

            <div class="out-auth-lang-pill">
                <i class="fa-solid fa-globe text-primary"></i>
                <span>English &middot; {{ currency_code() }}</span>
            </div>
        </div>

        <div class="out-auth-form-container">
            <div class="out-auth-form-header">
                <h2 class="out-auth-form-title">Forgot Password</h2>
                <p class="out-auth-form-sub">Enter your email to receive a password reset link.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success py-2 px-3 mb-3 small" style="border-radius: 9px;">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST">
                @csrf

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
                            autofocus
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback d-block mt-1 small">{{ $message }}</div>
                    @enderror
                </div>

                <x-recaptcha-widget class="mb-3" action="password_reset" />

                <button type="submit" class="out-auth-submit-btn">
                    <span>Send Reset Link</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <div class="out-auth-form-footer">
            <p class="out-auth-switch-text">
                Remember your password?
                <a href="{{ route('login') }}" class="out-auth-switch-link">Sign in here</a>
            </p>
            <p class="out-auth-copy-text">
                &copy; {{ date('Y') }} The Open University of Tanzania. All rights reserved.
            </p>
        </div>
    </div>
</div>
@endsection
