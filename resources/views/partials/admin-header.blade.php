<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="author" content="OUT LMS — Wolinet">
  <meta name="application-name" content="{{ site_name() }}">
  <meta name="theme-color" content="#013ba6">
  <title>@yield('title') | {{ site_name() }} Admin</title>
  <meta name="description" content="{{ site_name() }} Admin Control Center">
  @php($favicon = branding_asset(['favicon_path', 'logos.favicon', 'favicon'], 'assets/front/img/logo/brand-favicon-v3.png'))
  <link rel="icon" type="image/png" href="{{ $favicon }}">
  <link rel="shortcut icon" href="{{ $favicon }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

  <!-- General CSS Files -->
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/bootstrap/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/fontawesome/css/all.min.css') }}">

  <!-- CSS Libraries -->
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/jqvmap/dist/jqvmap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/weather-icon/css/weather-icons.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/weather-icon/css/weather-icons-wind.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/summernote/summernote-bs4.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/modules/select2/dist/css/select2.min.css') }}">

  <!-- Template CSS -->
  <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/css/components.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/admin/css/custom.css') }}">
  {!! ToastMagic::styles() !!}
  @stack('styles')

  @if(request()->routeIs('admin.dashboard'))
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style_v2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/animate.css') }}">
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
      rel="stylesheet">
  @endif

  @include('partials.dynamic-styles')
</head>

<body>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <div class="navbar-bg"></div>
      <nav class="navbar navbar-expand-lg main-navbar justify-content-end">
        <div class="d-flex justify-content-between w-100">
          <ul class="navbar-nav navbar-left">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
            @auth
              <li class="d-none d-md-flex align-items-center ml-2">
                <form action="{{ route('admin.campus.search') }}" method="GET" class="form-inline">
                  <div class="input-group input-group-sm">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control bg-white text-dark border-0" placeholder="Search applicant, invoice, control #..." style="min-width: 240px;">
                    <div class="input-group-append">
                      <button class="btn btn-light" type="submit" title="Global Search"><i class="fas fa-search text-primary"></i></button>
                    </div>
                  </div>
                </form>
              </li>
            @endauth
          </ul>
          <ul class="navbar-nav navbar-right align-items-center">
            @auth
              @php($headerUser = auth()->user())
              <li class="d-none d-lg-block mr-2">
                <span class="badge badge-light">{{ $headerUser->primaryRoleLabel() }}@if(! $headerUser->isMultiBranchAdmin() && $headerUser->branch) &bull; {{ $headerUser->branch->name }}@endif</span>
              </li>
              <li><a href="{{ route('admin.help.index') }}" class="nav-link" title="Online Help"><i class="fas fa-question-circle"></i> <span class="d-none d-sm-inline">Help</span></a></li>
              <li><a href="{{ route('home') }}" class="nav-link" target="_blank"><i class="fas fa-globe"></i> <span class="d-none d-sm-inline">Visit Website</span></a></li>
              <li class="dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user d-flex align-items-center">
                  @if ($headerUser->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($headerUser->profile_photo))
                    <img alt="{{ $headerUser->name }}" src="{{ asset('storage/' . $headerUser->profile_photo) }}" class="rounded-circle mr-2" width="32" height="32" style="object-fit: cover;">
                  @else
                    <img alt="{{ $headerUser->name }}" src="{{ asset('assets/images/avatar-1.png') }}" class="rounded-circle mr-2" width="32" height="32">
                  @endif
                  <div class="d-none d-md-inline-block text-left" style="line-height: 1.15;">
                    <div class="font-weight-bold" style="font-size: 13px;">{{ \Illuminate\Support\Str::limit($headerUser->name, 22) }}</div>
                  </div>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow-sm" style="min-width: 240px;">
                  <div class="dropdown-header text-left pb-2 border-bottom">
                    <div class="font-weight-bold text-dark">{{ $headerUser->name }}</div>
                    <div class="small text-muted text-truncate">{{ $headerUser->email }}</div>
                    <span class="badge badge-primary mt-1">{{ $headerUser->primaryRoleLabel() }}</span>
                  </div>
                  <a href="{{ route('admin.profile.edit') }}" class="dropdown-item has-icon mt-1">
                    <i class="far fa-user"></i> Edit Profile
                  </a>
                  <a href="{{ route('admin.help.index') }}" class="dropdown-item has-icon">
                    <i class="fas fa-question-circle"></i> Online Help
                  </a>
                  <div class="dropdown-divider"></div>
                  <a href="{{ route('logout') }}" class="dropdown-item has-icon text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                  </a>
                </div>
              </li>
            @else
              <li><a href="{{ route('home') }}" class="nav-link" target="_blank"><i class="fas fa-globe"></i> Visit Website</a></li>
            @endauth
          </ul>
        </div>
      </nav>

      @yield('content')