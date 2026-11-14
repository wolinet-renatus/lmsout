<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="OUT LMS — Wolinet">
    <meta name="application-name" content="{{ site_name() }}">
    <meta name="theme-color" content="#013ba6">
    <meta name="description" content="{{ $metaDescription ?? site_name() . ' - ' . site_tagline() }}">
    <title>{{ !empty($pageTitle) ? (str_contains($pageTitle, site_name()) ? $pageTitle : $pageTitle . ' | ' . site_name()) : site_name() . ' - ' . site_tagline() }}</title>

    <!-- Favicon & App Icons -->
    @php($favicon = branding_asset(['favicon_path', 'logos.favicon', 'favicon'], 'assets/front/img/logo/brand-favicon-v3.png'))
    <link rel="icon" type="image/png" href="{{ $favicon }}">
    <link rel="shortcut icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/all.min.css') }}">

    {!! ToastMagic::styles() !!}

    <style>
        :root {
            --out-primary: #013ba6;
            --out-primary-dark: #001f60;
            --out-secondary: #228ddd;
            --out-accent: #f4ed2d;
            --out-navy: #0f172a;
            --out-slate: #64748b;
            --out-bg: #f8fafc;
            --out-border: #e2e8f0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #ffffff;
            color: #0f172a;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .out-auth-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ──────────────── Left Showcase Panel ──────────────── */
        .out-auth-showcase {
            flex: 0 0 46%;
            max-width: 46%;
            background: linear-gradient(135deg, #001f60 0%, #013ba6 52%, #1e70c9 100%);
            color: #ffffff;
            padding: 48px 54px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        /* Decorative ambient circles resembling OUT logo arcs */
        .out-auth-showcase::before {
            content: '';
            position: absolute;
            width: 520px;
            height: 520px;
            border-radius: 50%;
            border: 36px solid rgba(34, 141, 221, 0.18);
            top: -140px;
            right: -180px;
            pointer-events: none;
        }

        .out-auth-showcase::after {
            content: '';
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(244, 237, 45, 0.16) 0%, transparent 70%);
            bottom: 40px;
            left: -60px;
            pointer-events: none;
        }

        .out-auth-showcase__brand {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none !important;
        }

        .out-auth-showcase__brand-badge {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
        }

        .out-auth-showcase__brand-badge img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .out-auth-showcase__brand-name {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .out-auth-showcase__brand-name span {
            color: #f4ed2d;
        }

        .out-auth-showcase__content {
            position: relative;
            z-index: 2;
            margin: 40px 0;
        }

        .out-auth-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 22px;
        }

        .out-auth-eyebrow i {
            color: #f4ed2d;
        }

        .out-auth-heading {
            font-size: clamp(28px, 2.6vw, 40px);
            font-weight: 800;
            line-height: 1.22;
            letter-spacing: -0.025em;
            color: #ffffff;
            margin-bottom: 14px;
        }

        .out-auth-heading span {
            color: #f4ed2d;
        }

        .out-auth-desc {
            font-size: 14.5px;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.86);
            margin-bottom: 32px;
            max-width: 480px;
        }

        /* 3 Benefit Feature Items */
        .out-auth-features {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-bottom: 34px;
        }

        .out-auth-feature-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .out-auth-feature-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #f4ed2d;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .out-auth-feature-text h5 {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 2px;
        }

        .out-auth-feature-text p {
            font-size: 12.5px;
            color: rgba(255, 255, 255, 0.76);
            margin: 0;
            line-height: 1.5;
        }

        /* Testimonial Quote Card */
        .out-auth-quote-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 16px;
            padding: 20px 22px;
            position: relative;
            z-index: 2;
        }

        .out-auth-quote-text {
            font-size: 13px;
            font-style: italic;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.92);
            margin-bottom: 14px;
        }

        .out-auth-quote-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .out-auth-quote-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #228ddd, #f4ed2d);
            color: #001f60;
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .out-auth-quote-name {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
        }

        .out-auth-quote-title {
            font-size: 11.5px;
            color: rgba(255, 255, 255, 0.72);
            margin: 0;
        }

        /* ──────────────── Right Form Panel ──────────────── */
        .out-auth-form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            padding: 36px 48px;
            overflow-y: auto;
        }

        .out-auth-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 24px;
        }

        .out-auth-back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none !important;
            transition: color 0.18s ease, transform 0.18s ease;
        }

        .out-auth-back-link:hover {
            color: #013ba6;
            transform: translateX(-3px);
        }

        .out-auth-lang-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 11.5px;
            font-weight: 600;
            color: #475569;
        }

        .out-auth-form-container {
            max-width: 440px;
            width: 100%;
            margin: auto;
            padding: 10px 0;
        }

        .out-auth-form-container--wide {
            max-width: 680px;
        }

        .out-auth-form-header {
            margin-bottom: 24px;
        }

        .out-auth-form-title {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin: 0 0 6px;
        }

        .out-auth-form-sub {
            font-size: 13.5px;
            color: #64748b;
            margin: 0;
            font-weight: 500;
        }

        /* Form Inputs */
        .out-form-group {
            margin-bottom: 18px;
        }

        .out-form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 7px;
        }

        .out-form-input-wrap {
            position: relative;
        }

        .out-form-input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-size: 13.5px;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .out-form-input:focus {
            background: #ffffff;
            border-color: #013ba6;
            box-shadow: 0 0 0 3px rgba(1, 59, 166, 0.12);
        }

        .out-form-input.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .out-form-toggle-pwd {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            font-size: 14px;
            transition: color 0.18s ease;
        }

        .out-form-toggle-pwd:hover {
            color: #013ba6;
        }

        .out-form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 12.5px;
        }

        .out-form-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-weight: 500;
            cursor: pointer;
            user-select: none;
            margin-bottom: 0;
        }

        .out-form-forgot-link {
            color: #013ba6;
            font-weight: 600;
            text-decoration: none !important;
            transition: color 0.18s ease;
        }

        .out-form-forgot-link:hover {
            color: #228ddd;
            text-decoration: underline !important;
        }

        .out-auth-submit-btn {
            width: 100%;
            height: 46px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #013ba6 0%, #228ddd 100%);
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(1, 59, 166, 0.25);
            transition: all 0.2s ease;
        }

        .out-auth-submit-btn:hover {
            background: linear-gradient(135deg, #002b7a 0%, #1a7bc5 100%);
            box-shadow: 0 6px 20px rgba(1, 59, 166, 0.35);
            transform: translateY(-1px);
        }

        .out-auth-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* ──────────────── One-Click Demo Credentials Card ──────────────── */
        .out-demo-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 14px;
            padding: 14px 16px;
            margin-top: 22px;
            position: relative;
        }

        .out-demo-box__header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .out-demo-box__badge {
            display: inline-block;
            background: #f59e0b;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 2px 7px;
            border-radius: 4px;
        }

        .out-demo-box__title {
            font-size: 12.5px;
            font-weight: 700;
            color: #92400e;
            margin: 0;
        }

        .out-demo-box__sub {
            font-size: 11px;
            color: #b45309;
            margin: 0 0 10px;
        }

        .out-demo-box__sub strong {
            background: rgba(245, 158, 11, 0.15);
            padding: 1px 5px;
            border-radius: 3px;
            color: #78350f;
        }

        .out-demo-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .out-demo-tab {
            border: 1px solid #fcd34d;
            background: #ffffff;
            color: #92400e;
            font-size: 10.5px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .out-demo-tab:hover {
            border-color: #013ba6;
            color: #013ba6;
        }

        .out-demo-tab.is-active {
            background: #013ba6;
            border-color: #013ba6;
            color: #ffffff;
        }

        .out-demo-group {
            margin-bottom: 10px;
        }

        .out-demo-group-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #78350f;
            margin: 8px 0 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .out-demo-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .out-demo-btn {
            background: #ffffff;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            padding: 9px 10px;
            text-align: left;
            cursor: pointer;
            transition: all 0.18s ease;
            display: flex;
            align-items: flex-start;
            gap: 9px;
            width: 100%;
        }

        .out-demo-btn:hover {
            border-color: #013ba6;
            background: #f0f7ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(1, 59, 166, 0.1);
        }

        .out-demo-btn.is-active {
            border-color: #013ba6;
            background: #eff6ff;
            box-shadow: 0 0 0 2px rgba(1, 59, 166, 0.2);
        }

        .out-demo-btn__icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: rgba(1, 59, 166, 0.08);
            color: #013ba6;
            font-size: 11.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .out-demo-btn__icon--branch {
            background: rgba(5, 150, 105, 0.1);
            color: #047857;
        }

        .out-demo-btn__icon--portal {
            background: rgba(99, 102, 241, 0.1);
            color: #4338ca;
        }

        .out-demo-btn__text {
            min-width: 0;
            flex: 1;
            line-height: 1.25;
        }

        .out-demo-btn__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 2px;
        }

        .out-demo-btn__role {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .out-demo-scope {
            font-size: 9px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .out-demo-scope--global {
            background: #dbeafe;
            color: #1e40af;
        }

        .out-demo-scope--branch {
            background: #d1fae5;
            color: #065f46;
        }

        .out-demo-scope--portal {
            background: #e0e7ff;
            color: #3730a3;
        }

        .out-demo-btn__email {
            display: block;
            font-size: 10px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #013ba6;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .out-demo-btn__hint {
            display: block;
            font-size: 9.5px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .out-demo-inspector {
            margin-top: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #013ba6;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 11px;
            line-height: 1.45;
            color: #334155;
        }

        .out-demo-inspector__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 4px;
        }

        .out-demo-inspector__title {
            font-weight: 800;
            color: #0f172a;
            font-size: 11.5px;
        }

        .out-demo-inspector__meta {
            display: grid;
            gap: 2px;
        }

        .out-demo-inspector__meta strong {
            color: #0f172a;
        }

        /* Footer in form panel */
        .out-auth-form-footer {
            margin-top: 24px;
            text-align: center;
        }

        .out-auth-switch-text {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 10px;
        }

        .out-auth-switch-link {
            color: #013ba6;
            font-weight: 700;
            text-decoration: none !important;
        }

        .out-auth-switch-link:hover {
            color: #228ddd;
            text-decoration: underline !important;
        }

        .out-auth-copy-text {
            font-size: 11.5px;
            color: #94a3b8;
            margin: 0;
        }

        @media (max-width: 991px) {
            .out-auth-layout {
                flex-direction: column;
            }
            .out-auth-showcase {
                flex: none;
                max-width: 100%;
                padding: 36px 24px;
            }
            .out-auth-form-panel {
                padding: 32px 20px;
            }
            .out-demo-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    @yield('content')

    <!-- Scripts -->
    <script src="{{ asset('assets/front/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/bootstrap.bundle.min.js') }}"></script>
    {!! ToastMagic::scripts() !!}

    <script>
        // Password toggle function
        function setupPasswordToggle(inputSelector, toggleSelector) {
            const input = document.querySelector(inputSelector);
            const toggle = document.querySelector(toggleSelector);
            if (input && toggle) {
                toggle.addEventListener('click', function () {
                    const isPwd = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPwd ? 'text' : 'password');
                    const icon = toggle.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye', !isPwd);
                        icon.classList.toggle('fa-eye-slash', isPwd);
                    }
                });
            }
        }
    </script>
    @stack('scripts')
</body>
</html>

