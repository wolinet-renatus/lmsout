@extends('layouts.admin')

@section('title', 'Homepage Themes & Brand Appearance')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item">Frontend Manager</div>
        <div class="breadcrumb-item">Homepage Theme</div>
    </div>
@endsection

@section('main-content')
    @php
        $defaultCoverVersion = @filemtime(public_path('assets/admin/img/default.png')) ?: time();
        $defaultCoverUrl = asset('assets/admin/img/default.png') . '?v=' . $defaultCoverVersion;
        $brandLogoUrl = branding_asset(['primary_logo_path', 'dark_logo_path'], 'assets/front/img/logo/brand-logo-v3.png');
        $themeColors = \App\Models\FrontendSetting::getValue('theme.colors', [
            'primary' => '#013ba6',
            'secondary' => '#0f172a',
            'accent' => '#228ddd',
        ]);
    @endphp

    <div class="row">
        <!-- Active Brand Theme Banner -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 14px; background: linear-gradient(120deg, #001f60 0%, #013ba6 55%, #228ddd 100%); color: #fff;">
                <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between" style="gap: 20px;">
                    <div class="d-flex align-items-center" style="gap: 16px;">
                        <div class="d-flex align-items-center justify-content-center bg-white rounded-lg shadow-sm" style="width: 58px; height: 58px; border-radius: 14px; flex-shrink: 0;">
                            <img src="{{ $brandLogoUrl }}" alt="{{ site_name() }} Logo" style="width: 42px; height: 42px; object-fit: contain;">
                        </div>
                        <div>
                            <span class="badge mb-1" style="background: rgba(244, 237, 45, 0.2); color: #f4ed2d; border: 1px solid rgba(244, 237, 45, 0.45); font-weight: 700; font-size: 11px; letter-spacing: 0.05em;">
                                OFFICIAL BRAND THEME
                            </span>
                            <h4 class="mb-1 text-white font-weight-bold">The Open University of Tanzania ({{ site_name() }})</h4>
                            <p class="mb-0" style="color: rgba(255, 255, 255, 0.85); font-size: 13px;">
                                Branded LMS theme with sticky navigation, real-time live search, modern course catalog, and logo-shaped footer.
                            </p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <a href="{{ route('home') }}" target="_blank" class="btn btn-light font-weight-bold" style="color: #013ba6; border-radius: 8px;">
                            <i class="fas fa-external-link-alt mr-1"></i> View Live Frontend
                        </a>
                        <a href="{{ route('admin.settings.branding.edit') }}" class="btn btn-outline-light font-weight-bold" style="border-radius: 8px;">
                            <i class="fas fa-sliders-h mr-1"></i> Branding Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Theme Upload Card -->
        <div class="col-12 mb-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h4><i class="fas fa-cloud-upload-alt text-primary mr-2"></i> Install New Theme Package</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.frontend.theme.upload') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row align-items-center">
                            <div class="col-md-8 col-lg-9">
                                <div class="form-group mb-md-0">
                                    <label class="font-weight-bold">Theme ZIP File</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('theme_zip') is-invalid @enderror" id="theme_zip" name="theme_zip" required>
                                        <label class="custom-file-label" for="theme_zip">Choose theme zip file...</label>
                                        @error('theme_zip')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">Upload a valid theme zip package containing <code>theme.json</code>, template files, and <code>assets/</code> directory.</small>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-3 text-right">
                                <button type="submit" class="btn btn-primary btn-block btn-lg">
                                    <i class="fas fa-upload mr-2"></i> Upload &amp; Install
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Discovered Themes & Brand Palette -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Available Themes &amp; Color System</h4>
                    <span class="badge badge-primary">{{ count($themes) + 1 }} Theme{{ count($themes) > 0 ? 's' : '' }} Installed</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- OUT Official Default Theme Card -->
                        <div class="col-md-6 col-lg-7 mb-4">
                            <div class="card h-100 border @if($activeTheme === null) border-primary shadow-sm @else border-light @endif" style="border-radius: 12px; overflow: hidden;">
                                <div class="position-relative bg-dark" style="height: 280px; overflow: hidden; border-bottom: 1px solid #e2e8f0;">
                                    <img src="{{ $defaultCoverUrl }}" class="w-100 h-100" style="object-fit: cover; object-position: top center;" alt="OUT Official LMS Theme Cover">

                                    <div class="position-absolute d-flex align-items-center" style="top: 12px; left: 12px; gap: 8px;">
                                        <span class="badge" style="background: #013ba6; color: #f4ed2d; font-size: 0.78rem; padding: 0.45em 0.85em; font-weight: 700; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                                            <i class="fas fa-university mr-1"></i> OUT Official Theme v3.0
                                        </span>
                                    </div>

                                    @if($activeTheme === null)
                                        <span class="badge badge-success position-absolute" style="top: 12px; right: 12px; font-size: 0.82rem; padding: 0.45em 0.85em; box-shadow: 0 4px 10px rgba(0,0,0,0.18);">
                                            <i class="fas fa-check-circle mr-1"></i> Active Theme
                                        </span>
                                    @endif
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            <h5 class="card-title mb-1 font-weight-bold">The Open University of Tanzania (OUT) LMS Theme</h5>
                                            <p class="text-muted small mb-2">By The Open University of Tanzania &bull; 3 Homepage Layouts (OUT Modern Default, EdTechX, Mentora)</p>
                                        </div>
                                        <a href="{{ route('home') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye mr-1"></i> Preview
                                        </a>
                                    </div>

                                    <p class="card-text text-secondary small flex-grow-1 mb-3">
                                        Custom-crafted for <strong>The Open University of Tanzania</strong> featuring Royal Blue (<code>#013ba6</code>), Sky Blue (<code>#228ddd</code>), and Emblem Yellow (<code>#f4ed2d</code>) branding, real-time header search, modern course cards, and logo-shaped footer.
                                    </p>

                                    <form method="POST" action="{{ route('admin.frontend.theme.update') }}" class="mt-auto">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="theme" value="">
                                        <div class="form-row align-items-end">
                                            <div class="col-sm-7 mb-2 mb-sm-0">
                                                <label class="small font-weight-bold mb-1">Homepage Layout Variant</label>
                                                <select name="variant" class="form-control form-control-sm">
                                                    <option value="index" {{ ($activeTheme === null && ($activeVariant ?? 'index') === 'index') ? 'selected' : '' }}>OUT Modern Default (Recommended)</option>
                                                    <option value="edtechx" {{ ($activeTheme === null && ($activeVariant ?? 'index') === 'edtechx') ? 'selected' : '' }}>EdTechX Modern (v3.0)</option>
                                                    <option value="mentora" {{ ($activeTheme === null && ($activeVariant ?? 'index') === 'mentora') ? 'selected' : '' }}>Mentora Executive (v3.0)</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-5">
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-check-circle mr-1"></i> {{ $activeTheme === null ? 'Apply Selected Variant' : 'Activate OUT Theme' }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Theme Color System Card -->
                        <div class="col-md-6 col-lg-5 mb-4">
                            <div class="card h-100 border border-primary" style="border-radius: 12px;">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h4 class="mb-0"><i class="fas fa-palette text-primary mr-1"></i> OUT Brand Color System</h4>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetOutBrandColors()">
                                        <i class="fas fa-undo mr-1"></i> Reset Defaults
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('admin.frontend.theme.update') }}" class="card-body d-flex flex-column justify-content-between">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <p class="text-muted small mb-3">
                                            Configure the global CSS brand palette synchronized with the official <strong>OUT</strong> emblem across all frontend pages.
                                        </p>

                                        <!-- Brand Swatch Bar Preview -->
                                        <div class="d-flex rounded overflow-hidden mb-3 shadow-sm" style="height: 28px; border: 1px solid #e2e8f0;">
                                            <div style="flex: 2; background: #013ba6;" title="Primary Royal Blue (#013ba6)"></div>
                                            <div style="flex: 2; background: #228ddd;" title="Secondary Sky Blue (#228ddd)"></div>
                                            <div style="flex: 1; background: #f4ed2d;" title="Emblem Yellow Accent (#f4ed2d)"></div>
                                            <div style="flex: 1; background: #0f172a;" title="Deep Navy Surface (#0f172a)"></div>
                                        </div>

                                        <div class="form-group mb-3 d-flex align-items-center justify-content-between p-2 rounded bg-light">
                                            <div>
                                                <label class="mb-0 small font-weight-bold d-block">Primary Brand Color (Royal Blue)</label>
                                                <code class="small" id="primary_color_code">{{ $themeColors['primary'] ?? '#013ba6' }}</code>
                                            </div>
                                            <input type="color" id="primary_color_input" name="primary_color" value="{{ $themeColors['primary'] ?? '#013ba6' }}" class="form-control" style="width: 56px; height: 36px; padding: 2px; cursor: pointer;" oninput="document.getElementById('primary_color_code').innerText = this.value">
                                        </div>

                                        <div class="form-group mb-3 d-flex align-items-center justify-content-between p-2 rounded bg-light">
                                            <div>
                                                <label class="mb-0 small font-weight-bold d-block">Secondary Dark Color (Deep Navy)</label>
                                                <code class="small" id="secondary_color_code">{{ $themeColors['secondary'] ?? '#0f172a' }}</code>
                                            </div>
                                            <input type="color" id="secondary_color_input" name="secondary_color" value="{{ $themeColors['secondary'] ?? '#0f172a' }}" class="form-control" style="width: 56px; height: 36px; padding: 2px; cursor: pointer;" oninput="document.getElementById('secondary_color_code').innerText = this.value">
                                        </div>

                                        <div class="form-group mb-2 d-flex align-items-center justify-content-between p-2 rounded bg-light">
                                            <div>
                                                <label class="mb-0 small font-weight-bold d-block">Accent Highlight Color (Sky Blue)</label>
                                                <code class="small" id="accent_color_code">{{ $themeColors['accent'] ?? '#228ddd' }}</code>
                                            </div>
                                            <input type="color" id="accent_color_input" name="accent_color" value="{{ $themeColors['accent'] ?? '#228ddd' }}" class="form-control" style="width: 56px; height: 36px; padding: 2px; cursor: pointer;" oninput="document.getElementById('accent_color_code').innerText = this.value">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block mt-3">
                                        <i class="fas fa-save mr-1"></i> Save Brand Colors
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Dynamic Discovered Themes -->
                        @foreach($themes as $slug => $theme)
                            @php
                                $isActive = ($activeTheme === $slug);
                                $variants = $theme['variants'] ?? [];
                                $hasMultipleVariants = count($variants) > 1;
                                $thumbnail = $theme['thumbnail'] ?? null;
                                $thumbnailUrl = $thumbnail ? theme_asset($thumbnail, $slug) : $defaultCoverUrl;
                            @endphp
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border @if($isActive) border-success shadow-sm @else border-light @endif" style="border-radius: 12px; overflow: hidden;">
                                    <div class="position-relative bg-light d-flex align-items-center justify-content-center" style="height: 200px; overflow: hidden;">
                                        <img src="{{ $thumbnailUrl }}" class="w-100 h-100" style="object-fit: cover; object-position: top center;" alt="{{ $theme['name'] }}" onerror="this.src='{{ $defaultCoverUrl }}'">

                                        @if($isActive)
                                            <span class="badge badge-success position-absolute" style="top: 10px; right: 10px; font-size: 0.85rem; padding: 0.4em 0.8em;">
                                                <i class="fas fa-check-circle mr-1"></i> Active
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h5 class="card-title mb-0">{{ $theme['name'] }}</h5>
                                            <span class="badge badge-secondary">v{{ $theme['version'] ?? '1.0.0' }}</span>
                                        </div>
                                        <p class="text-muted small mb-2">By {{ $theme['author'] ?? site_name() }}</p>
                                        <p class="card-text text-secondary small flex-grow-1">
                                            {{ $theme['description'] ?? 'Custom theme package for ' . site_name() . '.' }}
                                        </p>

                                        <form method="POST" action="{{ route('admin.frontend.theme.update') }}" class="mt-3">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="theme" value="{{ $slug }}">

                                            @if($hasMultipleVariants)
                                                <div class="form-group mb-2">
                                                    <label class="small font-weight-bold">Select Variant</label>
                                                    <select name="variant" class="form-control form-control-sm">
                                                        @foreach($variants as $variant)
                                                            <option value="{{ $variant['file'] }}" @if($isActive && $activeVariant === $variant['file']) selected @endif>
                                                                {{ $variant['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            <div class="row no-gutters">
                                                <div class="@if($isActive) col-12 @else col-9 pr-1 @endif">
                                                    @if($isActive && !$hasMultipleVariants)
                                                        <button type="button" class="btn btn-outline-success btn-block disabled" disabled>
                                                            Active Theme
                                                        </button>
                                                    @else
                                                        <button type="submit" class="btn btn-primary btn-block">
                                                            @if($isActive) Update Variant @else Activate @endif
                                                        </button>
                                                    @endif
                                                </div>
                                                @if(!$isActive)
                                                    <div class="col-3 pl-1">
                                                        <button type="button" class="btn btn-outline-danger btn-block" onclick="confirmDeleteTheme('{{ $slug }}')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="delete-theme-form" method="POST" action="{{ route('admin.frontend.theme.delete') }}" style="display:none;">
        @csrf
        @method('DELETE')
        <input type="hidden" name="theme" id="delete-theme-slug">
    </form>
@endsection

@push('scripts')
    <script>
        document.getElementById('theme_zip').addEventListener('change', function(e) {
            if (!e.target.files.length) return;
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            if (nextSibling) nextSibling.innerText = fileName;
        });

        function resetOutBrandColors() {
            var p = document.getElementById('primary_color_input');
            var s = document.getElementById('secondary_color_input');
            var a = document.getElementById('accent_color_input');
            if (p) { p.value = '#013ba6'; document.getElementById('primary_color_code').innerText = '#013ba6'; }
            if (s) { s.value = '#0f172a'; document.getElementById('secondary_color_code').innerText = '#0f172a'; }
            if (a) { a.value = '#228ddd'; document.getElementById('accent_color_code').innerText = '#228ddd'; }
        }

        function confirmDeleteTheme(slug) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the theme '" + slug + "' and all its assets. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-theme-slug').value = slug;
                    document.getElementById('delete-theme-form').submit();
                }
            });
        }
    </script>
@endpush
