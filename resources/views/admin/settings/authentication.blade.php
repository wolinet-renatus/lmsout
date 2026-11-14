@extends('layouts.admin')

@section('title', 'Authentication & OTP')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></div>
        <div class="breadcrumb-item active">Authentication & OTP</div>
    </div>
@endsection

@section('main-content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Login Experience</h4>
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary"><i
                            class="fas fa-arrow-left"></i> Back to Settings</a>
                </div>
                <form action="{{ route('admin.settings.authentication.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="email_verification_required"
                                    name="email_verification_required" value="1"
                                    {{ old('email_verification_required', $settings['email_verification_required'] ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="email_verification_required">Require email
                                    verification for new accounts</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="login_otp_enabled"
                                    name="login_otp_enabled" value="1"
                                    {{ old('login_otp_enabled', $settings['login_otp_enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="login_otp_enabled">Enable OTP verification on
                                    login</label>
                            </div>
                            <small class="text-muted d-block mt-1">Users will receive a one-time code to confirm their
                                identity.</small>
                        </div>
                        <div class="form-group">
                            <label for="otp_expiry_minutes">OTP Expiry (minutes)</label>
                            <input type="number" name="otp_expiry_minutes" id="otp_expiry_minutes"
                                class="form-control @error('otp_expiry_minutes') is-invalid @enderror"
                                value="{{ old('otp_expiry_minutes', $settings['otp_expiry_minutes'] ?? 10) }}"
                                min="1" max="120" required>
                            @error('otp_expiry_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group d-none">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="recaptcha_on_login"
                                    name="recaptcha_on_login" value="1"
                                    {{ old('recaptcha_on_login', $settings['recaptcha_on_login'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="recaptcha_on_login">Require Recaptcha on login
                                    forms</label>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="text-primary font-weight-bold mb-3">SRS Account Protection &amp; Session Rules</h6>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="srs_lockout_enabled"
                                    name="srs_lockout_enabled" value="1"
                                    {{ old('srs_lockout_enabled', $settings['srs_lockout_enabled'] ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="srs_lockout_enabled">Enable Failed-Login Lockout &amp; Escalating Auto-Block</label>
                            </div>
                            <small class="text-muted d-block mt-1">Temporarily locks accounts after consecutive failed attempts and blocks repeat offenders until reviewed by admin.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="srs_max_attempts">Max Failed Attempts Before Lockout</label>
                                <input type="number" name="srs_max_attempts" id="srs_max_attempts"
                                    class="form-control @error('srs_max_attempts') is-invalid @enderror"
                                    value="{{ old('srs_max_attempts', $settings['srs_max_attempts'] ?? 4) }}" min="2" max="20">
                                @error('srs_max_attempts')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="srs_password_expiry_days">Password Expiry Policy (days, 0 = disabled)</label>
                                <input type="number" name="srs_password_expiry_days" id="srs_password_expiry_days"
                                    class="form-control @error('srs_password_expiry_days') is-invalid @enderror"
                                    value="{{ old('srs_password_expiry_days', $settings['srs_password_expiry_days'] ?? 0) }}" min="0" max="365">
                                @error('srs_password_expiry_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="srs_idle_timeout_minutes">Idle Session Timeout (minutes, 0 = disabled)</label>
                                <input type="number" name="srs_idle_timeout_minutes" id="srs_idle_timeout_minutes"
                                    class="form-control @error('srs_idle_timeout_minutes') is-invalid @enderror"
                                    value="{{ old('srs_idle_timeout_minutes', $settings['srs_idle_timeout_minutes'] ?? 5) }}" min="0" max="120">
                                @error('srs_idle_timeout_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="srs_single_session_enabled"
                                    name="srs_single_session_enabled" value="1"
                                    {{ old('srs_single_session_enabled', $settings['srs_single_session_enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="srs_single_session_enabled">Enforce Single Active Session Per User</label>
                            </div>
                            <small class="text-muted d-block mt-1">Signing in from a new browser automatically signs out previous sessions for the same account.</small>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">Save Authentication Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
