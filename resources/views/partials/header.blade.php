@php
    $siteName = site_name();
    $logoUrl = branding_asset(['primary_logo_path', 'dark_logo_path', 'logos.primary', 'logos.secondary'], 'assets/front/img/logo/brand-logo-v3.png');
    $mainMenu = $frontendMenus['main-navigation'] ?? [];
    $currentUrl = url()->current();
@endphp

<!-- Clean Sticky Header Navigation Start -->
<header id="header-sticky" class="header-1 lmsout-navbar">
    <div class="container">
        <div class="mega-menu-wrapper">
            <div class="header-main lmsout-navbar__inner">
                <!-- Left: Fitted Brand Logo & Name -->
                <div class="logo lmsout-navbar__logo-wrap">
                    <a href="{{ route('home') }}" class="lmsout-header-brand">
                        <span class="lmsout-header-brand__badge">
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }} logo" class="lmsout-header-brand__img">
                        </span>
                        <span class="lmsout-header-brand__name">
                            {{ $siteName }}<span class="lmsout-header-brand__dot">.</span>
                        </span>
                    </a>
                </div>

                <!-- Center: Navigation Links -->
                <div class="mean__menu-wrapper lmsout-navbar__menu-wrap">
                    <div class="main-menu">
                        <nav id="mobile-menu">
                            <ul class="lmsout-navbar__nav-list">
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
                <div class="header-right lmsout-navbar__actions">
                    <div class="lmsout-live-search" id="lmsout-live-search" data-endpoint="{{ route('live-search') }}">
                        <form action="{{ route('courses') }}" method="GET" class="lmsout-live-search__form" role="search">
                            <i class="fa-solid fa-magnifying-glass lmsout-live-search__icon"></i>
                            <input
                                type="text"
                                name="search"
                                id="lmsout-header-search-input"
                                class="lmsout-live-search__input"
                                value="{{ request('search') }}"
                                placeholder="Search courses, instructors..."
                                autocomplete="off"
                                aria-label="Search courses and instructors"
                                aria-expanded="false"
                                aria-controls="lmsout-live-search-dropdown"
                            >
                            <button type="button" class="lmsout-live-search__clear d-none" id="lmsout-live-search-clear" aria-label="Clear search">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <kbd class="lmsout-live-search__kbd" id="lmsout-live-search-kbd">⌘K</kbd>
                        </form>

                        <!-- Real-Time Results Dropdown -->
                        <div class="lmsout-live-search__dropdown" id="lmsout-live-search-dropdown" role="listbox" aria-label="Live search results">
                            <div class="lmsout-live-search__body" id="lmsout-live-search-body">
                                <div class="lmsout-live-search__loading">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i> Searching...
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $activeCurrencyCode = currency_code();
                    @endphp
                    <div class="lmsout-navbar__lang d-none d-md-flex" title="Switch currency (1 USD = {{ number_format(usd_to_tzs_rate(), 0) }} TZS)">
                        <i class="fa-solid fa-coins"></i>
                        <select onchange="window.location.href=this.value" aria-label="Currency">
                            @foreach (supported_switch_currencies() as $curCode => $curLabel)
                                <option value="{{ request()->fullUrlWithQuery(['currency' => $curCode]) }}" {{ $curCode === $activeCurrencyCode ? 'selected' : '' }}>
                                    {{ $curCode === 'TZS' ? 'TSH (TZS)' : 'USD ($)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (!empty($availableLocales) && count($availableLocales) > 1)
                        <div class="lmsout-navbar__lang d-none d-md-flex">
                            <i class="fa-solid fa-globe"></i>
                            <select onchange="window.location.href=this.value" aria-label="Language">
                                @foreach ($availableLocales as $code => $meta)
                                    <option value="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" {{ $code === $currentLocale ? 'selected' : '' }}>
                                        {{ strtoupper($code) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <span class="lmsout-navbar__lang d-none d-lg-inline-flex">
                            <i class="fa-solid fa-globe"></i>
                            <span>{{ strtoupper(app()->getLocale()) }}</span>
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

                        <a href="{{ $dashboardRoute }}" class="lmsout-navbar__cta-btn">
                            <i class="fa-regular fa-circle-user"></i>
                            <span>{{ $dashboardText }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="lmsout-navbar__cta-btn">
                            <span>{{ __('frontend.login') }}</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @endif

                    <div class="header__hamburger d-xl-none my-auto">
                        <div class="sidebar__toggle lmsout-navbar__Burger">
                            <i class="fas fa-bars"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

@include('partials.search')
