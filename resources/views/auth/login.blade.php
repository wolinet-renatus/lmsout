@extends('layouts.auth')

@php
    $siteName = site_name();
    $pageTitle = 'Login | ' . $siteName;
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
                <i class="fa-solid fa-sparkles"></i>
                <span>Open &amp; Online Learning for All</span>
            </div>

            <h1 class="out-auth-heading">
                Grow your skills with <span>expert-led</span> courses.
            </h1>

            <p class="out-auth-desc">
                Join learners across Tanzania and worldwide. Video lessons, quizzes, live classes, verified certificates &mdash; everything in one place.
            </p>

            <div class="out-auth-features">
                <div class="out-auth-feature-item">
                    <div class="out-auth-feature-icon">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div class="out-auth-feature-text">
                        <h5>Learn at your own pace</h5>
                        <p>Flexible online and blended access to every course you enroll in.</p>
                    </div>
                </div>

                <div class="out-auth-feature-item">
                    <div class="out-auth-feature-icon">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <div class="out-auth-feature-text">
                        <h5>Earn verified certificates</h5>
                        <p>Share your academic achievements with QR-verifiable credentials from OUT.</p>
                    </div>
                </div>

                <div class="out-auth-feature-item">
                    <div class="out-auth-feature-icon">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                    <div class="out-auth-feature-text">
                        <h5>Learn together</h5>
                        <p>Live classes, discussions, and a supportive pan-African community.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Testimonial Quote -->
        <div class="out-auth-quote-card">
            <p class="out-auth-quote-text">
                &ldquo;The OUT blended learning model allowed me to balance my career and professional studies seamlessly. The instructors and course materials are exceptional.&rdquo;
            </p>
            <div class="out-auth-quote-author">
                <div class="out-auth-quote-avatar">AJ</div>
                <div>
                    <h6 class="out-auth-quote-name">Amina Juma</h6>
                    <p class="out-auth-quote-title">Software Engineer &bull; Dar es Salaam</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Authentication Form Panel -->
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
        <div class="out-auth-form-container out-auth-form-container--wide">
            <div class="out-auth-form-header">
                <h2 class="out-auth-form-title">Welcome back</h2>
                <p class="out-auth-form-sub">Login to continue your learning journey.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success py-2 px-3 mb-3 small" style="border-radius: 9px;">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" id="outLoginForm">
                @csrf

                <!-- Email Input -->
                <div class="out-form-group">
                    <label for="email" class="out-form-label">Email address</label>
                    <div class="out-form-input-wrap">
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            placeholder="name@lsmout.ac.tz"
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

                <!-- Password Input -->
                <div class="out-form-group">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label for="password" class="out-form-label mb-0">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="out-form-forgot-link">Forgot password?</a>
                        @endif
                    </div>
                    <div class="out-form-input-wrap">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="••••••••"
                            class="out-form-input @error('password') is-invalid @enderror"
                            required
                            autocomplete="current-password"
                        >
                        <span class="out-form-toggle-pwd" id="togglePasswordBtn" title="Toggle password visibility">
                            <i class="fa-regular fa-eye"></i>
                        </span>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block mt-1 small">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember Me Checkbox -->
                <div class="out-form-row">
                    <label class="out-form-checkbox-label" for="remember">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input mt-0" {{ old('remember') ? 'checked' : '' }}>
                        <span>Keep me signed in</span>
                    </label>
                </div>

                <x-recaptcha-widget class="mb-3" action="login" />

                <!-- Submit Button -->
                <button type="submit" class="out-auth-submit-btn" id="loginSubmitBtn">
                    <span>Login</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

                <!-- One-Click Demo Credentials Card (All Roles, RBAC & Isolation Details) -->
                @php
                    $demoRoles = collect(config('demo_accounts.roles', []));
                    $demoGroups = [
                        'global' => [
                            'title' => 'Global & Cross-Branch Governance',
                            'subtitle' => 'Access across all 17 centers via crossBranchRoles()',
                        ],
                        'branch' => [
                            'title' => 'Branch-Isolated Staff',
                            'subtitle' => 'Filtered by assigned center via BranchScoped + RBAC',
                        ],
                        'portal' => [
                            'title' => 'Academic & Learner Portals',
                            'subtitle' => 'Portal middleware isolation (/instructor/* & /student/*)',
                        ],
                    ];
                @endphp
                <div class="out-demo-box">
                    <div class="out-demo-box__header">
                        <span class="out-demo-box__badge">DEMO RBAC</span>
                        <h4 class="out-demo-box__title">One-Click Login Across All {{ $demoRoles->count() }} Roles</h4>
                    </div>
                    <p class="out-demo-box__sub">
                        Click any role below to sign in instantly (Password: <strong>{{ config('demo_accounts.default_password', 'password') }}</strong>) or hover/focus to inspect RBAC &amp; isolation rules.
                    </p>

                    <div class="out-demo-tabs" role="tablist" aria-label="Filter demo roles by isolation scope">
                        <button type="button" class="out-demo-tab is-active" data-filter="all">All ({{ $demoRoles->count() }})</button>
                        <button type="button" class="out-demo-tab" data-filter="global">Global / Cross-Branch ({{ $demoRoles->where('group', 'global')->count() }})</button>
                        <button type="button" class="out-demo-tab" data-filter="branch">Branch-Isolated ({{ $demoRoles->where('group', 'branch')->count() }})</button>
                        <button type="button" class="out-demo-tab" data-filter="portal">Academic Portals ({{ $demoRoles->where('group', 'portal')->count() }})</button>
                    </div>

                    @foreach ($demoGroups as $groupKey => $groupMeta)
                        @php $groupRoles = $demoRoles->where('group', $groupKey); @endphp
                        @if ($groupRoles->isNotEmpty())
                            <div class="out-demo-group" data-group-section="{{ $groupKey }}">
                                <div class="out-demo-group-label">
                                    <span>{{ $groupMeta['title'] }}</span>
                                    <span style="font-weight: 600; text-transform: none; letter-spacing: 0; color: #92400e;">{{ $groupMeta['subtitle'] }}</span>
                                </div>
                                <div class="out-demo-grid">
                                    @foreach ($groupRoles as $item)
                                        <button
                                            type="button"
                                            class="out-demo-btn"
                                            data-email="{{ $item['email'] }}"
                                            data-password="{{ $item['password'] }}"
                                            data-role="{{ $item['label'] }}"
                                            data-scope="{{ $item['scope_badge'] }}"
                                            data-scope-type="{{ $item['scope_type'] }}"
                                            data-allowed="{{ $item['allowed_modules'] }}"
                                            data-restricted="{{ $item['restricted_from'] }}"
                                            title="{{ $item['label'] }} ({{ $item['email'] }}) — {{ $item['scope_badge'] }}. Allowed: {{ $item['allowed_modules'] }}. {{ $item['restricted_from'] }}"
                                        >
                                            <span class="out-demo-btn__icon out-demo-btn__icon--{{ $item['scope_type'] }}">
                                                <i class="{{ $item['icon'] }}"></i>
                                            </span>
                                            <span class="out-demo-btn__text">
                                                <span class="out-demo-btn__top">
                                                    <strong class="out-demo-btn__role">{{ $item['label'] }}</strong>
                                                    <span class="out-demo-scope out-demo-scope--{{ $item['scope_type'] }}">{{ $item['scope_badge'] }}</span>
                                                </span>
                                                <span class="out-demo-btn__email">{{ $item['email'] }}</span>
                                                <span class="out-demo-btn__hint">{{ $item['short_rbac'] }}</span>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <div class="out-demo-inspector" id="demoRbacInspector">
                        <div class="out-demo-inspector__head">
                            <span class="out-demo-inspector__title" id="inspectorRoleTitle">RBAC &amp; Access Isolation Preview</span>
                            <span class="out-demo-scope out-demo-scope--global" id="inspectorScopeBadge">Hover any role card</span>
                        </div>
                        <div class="out-demo-inspector__meta">
                            <div><strong>Credential:</strong> <code id="inspectorEmail">admin@lsmout.ac.tz</code> / <code>{{ config('demo_accounts.default_password', 'password') }}</code></div>
                            <div><strong>Allowed Modules:</strong> <span id="inspectorAllowed">Select or hover a role above to inspect its permitted routes &amp; modules.</span></div>
                            <div><strong>Isolation &amp; Restrictions:</strong> <span id="inspectorRestricted">Global roles span all 17 branches; Branch roles enforce BranchScoped center isolation; Tutor/Student roles isolate to dedicated portals.</span></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Panel Footer -->
        <div class="out-auth-form-footer">
            <p class="out-auth-switch-text">
                Don't have an account?
                <a href="{{ route('register') }}" class="out-auth-switch-link">Create one free</a>
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

        const form = document.getElementById('outLoginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const submitBtn = document.getElementById('loginSubmitBtn');
        const demoButtons = document.querySelectorAll('.out-demo-btn');
        const filterTabs = document.querySelectorAll('.out-demo-tab');
        const groupSections = document.querySelectorAll('.out-demo-group');
        const inspectorRoleTitle = document.getElementById('inspectorRoleTitle');
        const inspectorScopeBadge = document.getElementById('inspectorScopeBadge');
        const inspectorEmail = document.getElementById('inspectorEmail');
        const inspectorAllowed = document.getElementById('inspectorAllowed');
        const inspectorRestricted = document.getElementById('inspectorRestricted');

        function updateInspector(btn) {
            if (!btn || !inspectorRoleTitle) {
                return;
            }
            const scopeType = btn.getAttribute('data-scope-type') || 'global';
            inspectorRoleTitle.textContent = btn.getAttribute('data-role') + ' — RBAC & Access Profile';
            inspectorScopeBadge.textContent = btn.getAttribute('data-scope') || 'Global';
            inspectorScopeBadge.className = 'out-demo-scope out-demo-scope--' + scopeType;
            inspectorEmail.textContent = btn.getAttribute('data-email') || '';
            inspectorAllowed.textContent = btn.getAttribute('data-allowed') || '';
            inspectorRestricted.textContent = btn.getAttribute('data-restricted') || '';
        }

        filterTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                filterTabs.forEach(t => t.classList.remove('is-active'));
                this.classList.add('is-active');
                const filter = this.getAttribute('data-filter');

                groupSections.forEach(section => {
                    const key = section.getAttribute('data-group-section');
                    section.style.display = (filter === 'all' || filter === key) ? '' : 'none';
                });
            });
        });

        // One-Click Credential Autofill, RBAC Inspection & Instant Login
        demoButtons.forEach(btn => {
            btn.addEventListener('mouseenter', function () {
                updateInspector(this);
            });
            btn.addEventListener('focus', function () {
                updateInspector(this);
            });
            btn.addEventListener('click', function () {
                demoButtons.forEach(b => b.classList.remove('is-active'));
                this.classList.add('is-active');
                updateInspector(this);

                const email = this.getAttribute('data-email');
                const password = this.getAttribute('data-password');
                const role = this.getAttribute('data-role');

                emailInput.value = email;
                passwordInput.value = password;

                // Visual feedback on button
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span>Signing in as ${role}...</span> <i class="fa-solid fa-circle-notch fa-spin"></i>`;

                // Quick submission
                setTimeout(() => {
                    form.submit();
                }, 250);
            });
        });

        // Form submit feedback
        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                if (!submitBtn.disabled) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span>Signing in...</span> <i class="fa-solid fa-circle-notch fa-spin"></i>`;
                }
            });
        }
    });
</script>
@endpush
