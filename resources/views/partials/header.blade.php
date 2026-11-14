@php
    $siteName = site_name();
    $logoUrl = branding_asset(['primary_logo_path', 'dark_logo_path', 'logos.primary', 'logos.secondary'], 'assets/front/img/logo/brand-logo-v3.png');
    $mainMenu = $frontendMenus['main-navigation'] ?? [];
    $currentUrl = url()->current();
@endphp

<!-- Clean Sticky Header Navigation Start -->
<header id="header-sticky" class="header-1 edux-navbar">
    <div class="container">
        <div class="mega-menu-wrapper">
            <div class="header-main edux-navbar__inner">
                <!-- Left: Fitted Brand Logo & Name -->
                <div class="logo edux-navbar__logo-wrap">
                    <a href="{{ route('home') }}" class="edux-header-brand">
                        <span class="edux-header-brand__badge">
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }} logo" class="edux-header-brand__img">
                        </span>
                        <span class="edux-header-brand__name">
                            {{ $siteName }}<span class="edux-header-brand__dot">.</span>
                        </span>
                    </a>
                </div>

                <!-- Center: Navigation Links -->
                <div class="mean__menu-wrapper edux-navbar__menu-wrap">
                    <div class="main-menu">
                        <nav id="mobile-menu">
                            <ul class="edux-navbar__nav-list">
                                @if (!empty($mainMenu))
                                    @include('partials.menu-items', [
                                        'items' => $mainMenu,
                                        'isRoot' => true,
                                    ])
                                @else
                                    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">{{ __('frontend.home') }}</a></li>
                                    <li><a href="{{ route('courses') }}" class="{{ request()->routeIs('courses*') ? 'active' : '' }}">{{ __('frontend.courses') }}</a></li>
                                    <li><a href="{{ route('instructors.index') }}" class="{{ request()->routeIs('instructors*') ? 'active' : '' }}">{{ __('frontend.instructors') }}</a></li>
                                    <li><a href="{{ route('bundles.index') }}" class="{{ request()->routeIs('bundles*') ? 'active' : '' }}">Bundles</a></li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Right: Real-Time Live Search, Locale/Currency, & Auth Actions -->
                <div class="header-right edux-navbar__actions">
                    <div class="edux-live-search" id="edux-live-search" data-endpoint="{{ route('live-search') }}">
                        <form action="{{ route('courses') }}" method="GET" class="edux-live-search__form" role="search">
                            <i class="fa-solid fa-magnifying-glass edux-live-search__icon"></i>
                            <input
                                type="text"
                                name="search"
                                id="edux-header-search-input"
                                class="edux-live-search__input"
                                value="{{ request('search') }}"
                                placeholder="Search courses, instructors..."
                                autocomplete="off"
                                aria-label="Search courses and instructors"
                                aria-expanded="false"
                                aria-controls="edux-live-search-dropdown"
                            >
                            <button type="button" class="edux-live-search__clear d-none" id="edux-live-search-clear" aria-label="Clear search">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <kbd class="edux-live-search__kbd" id="edux-live-search-kbd">⌘K</kbd>
                        </form>

                        <!-- Real-Time Results Dropdown -->
                        <div class="edux-live-search__dropdown" id="edux-live-search-dropdown" role="listbox" aria-label="Live search results">
                            <div class="edux-live-search__body" id="edux-live-search-body">
                                <div class="edux-live-search__loading">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i> Searching...
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (!empty($availableLocales) && count($availableLocales) > 1)
                        <div class="edux-navbar__lang d-none d-md-flex">
                            <i class="fa-solid fa-globe"></i>
                            <select onchange="window.location.href=this.value" aria-label="Language">
                                @foreach ($availableLocales as $code => $meta)
                                    <option value="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" {{ $code === $currentLocale ? 'selected' : '' }}>
                                        {{ strtoupper($code) }} · {{ currency_code() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <span class="edux-navbar__lang d-none d-lg-inline-flex">
                            <i class="fa-solid fa-globe"></i>
                            <span>{{ strtoupper(app()->getLocale()) }} &middot; {{ currency_code() }}</span>
                        </span>
                    @endif

                    @if(Auth::check())
                        @php
                            $currentUser = Auth::user();
                            $dashboardRoute = route('home');
                            $dashboardText = __('frontend.dashboard');

                            if ($currentUser->isAdmin()) {
                                $dashboardRoute = route('admin.dashboard');
                                $dashboardText = __('frontend.admin_dashboard');
                            } elseif ($currentUser->isInstructor()) {
                                $dashboardRoute = route('instructor.dashboard');
                                $dashboardText = __('frontend.instructor_dashboard');
                            } elseif ($currentUser->isStudent()) {
                                $dashboardRoute = route('student.dashboard');
                                $dashboardText = __('frontend.my_dashboard');
                            }
                        @endphp

                        @include('partials.notification-dropdown', ['currentUser' => $currentUser])

                        <a href="{{ $dashboardRoute }}" class="edux-navbar__cta-btn">
                            <i class="fa-regular fa-circle-user"></i>
                            <span>{{ $dashboardText }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="edux-navbar__cta-btn">
                            <span>{{ __('frontend.login') }}</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @endif

                    <div class="header__hamburger d-xl-none my-auto">
                        <div class="sidebar__toggle edux-navbar__Burger">
                            <i class="fas fa-bars"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

@include('partials.search')
