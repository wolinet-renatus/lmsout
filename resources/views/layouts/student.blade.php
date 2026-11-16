<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ rtrim(url('/'), '/') }}">
    <meta name="author" content="OUT LMS — Wolinet">
    <meta name="application-name" content="{{ site_name() }}">
    <meta name="theme-color" content="#013ba6">
    <title>{{ (!empty($pageTitle) && $pageTitle !== site_name()) ? $pageTitle . ' | ' . site_name() . ' Student Campus' : site_name() . ' - Student Campus' }}</title>

    @stack('meta')

    <!-- Favicon & App Icons -->
    @php($favicon = branding_asset(['favicon_path', 'logos.favicon', 'favicon'], 'assets/front/img/logo/brand-favicon-v3.png'))
    <link rel="icon" type="image/png" href="{{ $favicon }}">
    <link rel="shortcut icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('assets/front/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/instructor/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/student/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/portal-app.css') }}">

    {!! ToastMagic::styles() !!}

    @stack('styles')
    @include('partials.dynamic-styles')
</head>

<body class="out-portal-body">
    <!-- Back to top button -->
    <button id="back-top" class="back-to-top show" aria-label="Back to top">
        <i class="fa-regular fa-arrow-up"></i>
    </button>

    <!-- Dedicated Full-Width Portal App Shell -->
    <div class="out-portal-wrapper">
        <!-- Dedicated Portal Header -->
        @include('partials.portal-header', ['portalType' => 'student'])

        <!-- Mobile Drawer Backdrop -->
        <div class="out-portal-backdrop" id="outPortalBackdrop"></div>

        <div class="out-portal-container">
            <!-- Full Height Sidebar Navigation (Reusable Component) -->
            @include('student.partials.sidebar', ['fromLayout' => true])

            <!-- Fluid Main Content Area (Full Remaining Width) -->
            <main class="out-portal-main">
                @include('partials.impersonation-banner')

                <div class="out-portal-content-body">
                    @yield('content')
                </div>

                <!-- Dedicated Portal Footer -->
                @include('partials.portal-footer')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/front/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/bootstrap.bundle.min.js?v=5.3.2') }}"></script>
    <script src="{{ asset('assets/front/js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/wow.min.js') }}"></script>
    <script src="{{ asset('assets/front/js/instructor/script.js') }}"></script>
    <script src="{{ asset('assets/front/js/portal-app.js') }}"></script>
    <script src="{{ asset('assets/front/js/sweetalert2@11.js') }}"></script>

    {!! ToastMagic::scripts() !!}

    @stack('scripts')
    @include('partials.form-draft-guard')
</body>
</html>
