@extends('partials.admin-header')

@section('title', $title ?? 'Admin Dashboard')

@section('content')

    @include('partials.admin-sidebar')

    @php
        $inSystemSettings = request()->routeIs([
            'admin.settings.*',
            'admin.staff.*',
            'admin.roles.*',
            'admin.frontend.*',
            'admin.custom-pages.*',
            'admin.banners.*',
            'admin.account-deletions.*',
            'admin.languages.*',
            'admin.notifications.*',
            'admin.campus.templates.*',
            'admin.audit-logs.*',
            'admin.backups.*',
            'admin.updater.*',
            'admin.help.*',
        ]);
    @endphp

    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>@yield('title', 'Dashboard')</h1>
                @hasSection('breadcrumb')
                    <div class="section-header-breadcrumb">
                        @yield('breadcrumb')
                    </div>
                @endif
            </div>

            @if ($inSystemSettings)
                <div class="row">
                    <div class="col-lg-3 mb-4">
                        @include('partials.system-settings-sidebar')
                    </div>
                    <div class="col-lg-9">
                        @yield('main-content')
                    </div>
                </div>
            @else
                @yield('main-content')
            @endif

        </section>
        @include('partials.admin-footer')

    </div>

@endsection
