@extends('layouts.auth')

@php
    $siteName = site_name();
    $pageTitle = 'Update Password | ' . $siteName;
    $logoUrl = branding_asset(['primary_logo_path', 'dark_logo_path', 'logos.primary'], 'assets/front/img/logo/brand-logo-v3.png');
@endphp

@section('content')
<div class="out-auth-layout">
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
                <span>Account Security Check</span>
            </div>
            <h1 class="out-auth-heading">
                Set a strong <span>personal password</span>.
            </h1>
            <p class="out-auth-desc">
                For your security, please set a new password before continuing to your learning dashboard.
            </p>
        </div>
    </div>

    <div class="out-auth-form-panel">
        <div class="out-auth-topbar">
            <a href="{{ route('logout') }}" class="out-auth-back-link">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Sign out</span>
            </a>
        </div>

        <div class="out-auth-form-container">
            <div class="out-auth-form-header">
                <h2 class="out-auth-form-title">Password Change Required</h2>
                <p class="out-auth-form-sub">Choose a new password of at least 8 characters.</p>
            </div>

            <form action="{{ route('password.force_change.update') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">New Password</label>
                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required autofocus>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    Update Password &amp; Continue
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
