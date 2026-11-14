@extends('layouts.admin')

@section('title', 'Edit Profile')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item active">Profile</div>
    </div>
@endsection

@section('main-content')
    @php
        $hasPhoto = $user->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo);
        $avatarUrl = $hasPhoto ? asset('storage/' . $user->profile_photo) : asset('assets/images/avatar-1.png');
    @endphp
    <div class="section-body">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2 class="section-title mb-1">Manage Profile</h2>
                    <p class="section-lead mb-0 mt-2">
                        Update your personal information, profile image with automatic resizing, and account password.
                    </p>
                </div>
                <div>
                    <span class="badge badge-primary">{{ $user->primaryRoleLabel() }}</span>
                    @if ($user->branch && ! $user->isMultiBranchAdmin())
                        <span class="badge badge-light ml-1">{{ $user->branch->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Account Information &amp; Avatar Upload</h4>
                    </div>
                    <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="name">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                                        class="form-control @error('name') is-invalid @enderror" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                                        class="form-control @error('email') is-invalid @enderror" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                    class="form-control @error('phone') is-invalid @enderror" placeholder="+255 700 000 000">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <hr class="my-4">

                            <div class="form-group mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="profile_photo" class="mb-0 font-weight-bold">Profile Photo &amp; Auto-Resize Options</label>
                                    @if ($hasPhoto)
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="remove_photo" name="remove_photo" value="1">
                                            <label class="custom-control-label text-danger small" for="remove_photo">Remove current photo</label>
                                        </div>
                                    @endif
                                </div>

                                <div class="p-3 rounded border bg-light">
                                    <div class="d-flex align-items-center flex-wrap" style="gap: 16px;">
                                        <div class="text-center">
                                            <img id="avatarPreviewImg"
                                                 src="{{ $avatarUrl }}"
                                                 data-default-src="{{ asset('assets/images/avatar-1.png') }}"
                                                 data-current-src="{{ $avatarUrl }}"
                                                 alt="Profile photo preview"
                                                 class="rounded-circle border shadow-sm bg-white"
                                                 style="width: 96px; height: 96px; object-fit: cover;">
                                            <div class="small text-muted mt-2" id="avatarMetaBadge">
                                                @if ($photoDimensions)
                                                    Saved: {{ $photoDimensions }}
                                                @else
                                                    Default Avatar
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex-grow-1" style="min-width: 240px;">
                                            <div class="custom-file mb-2">
                                                <input type="file" name="profile_photo" id="profile_photo"
                                                    class="custom-file-input @error('profile_photo') is-invalid @enderror"
                                                    accept="image/jpeg,image/png,image/webp,image/gif">
                                                <label class="custom-file-label" for="profile_photo" id="profile_photo_label">Choose image file...</label>
                                            </div>
                                            <small class="form-text text-muted mb-3">
                                                Accepted: JPG, PNG, WEBP, GIF (max 8MB). Large uploads are automatically resized &amp; EXIF-oriented.
                                            </small>
                                            @error('profile_photo')
                                                <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                                            @enderror

                                            <div class="form-row">
                                                <div class="form-group col-md-7 mb-2">
                                                    <label for="resize_preset" class="small font-weight-bold mb-1">Auto-Resize Preset</label>
                                                    <select name="resize_preset" id="resize_preset" class="form-control form-control-sm @error('resize_preset') is-invalid @enderror">
                                                        @foreach ($photoPresets as $key => $preset)
                                                            <option value="{{ $key }}"
                                                                    data-width="{{ $preset['width'] }}"
                                                                    data-height="{{ $preset['height'] }}"
                                                                    data-mode="{{ $preset['mode'] }}"
                                                                    {{ old('resize_preset', 'square_400') === $key ? 'selected' : '' }}>
                                                                {{ $preset['label'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-5 mb-2" id="customModeWrap" style="display: none;">
                                                    <label for="resize_mode" class="small font-weight-bold mb-1">Scaling Behavior</label>
                                                    <select name="resize_mode" id="resize_mode" class="form-control form-control-sm">
                                                        <option value="crop" {{ old('resize_mode', 'crop') === 'crop' ? 'selected' : '' }}>Center Crop (Exact W×H)</option>
                                                        <option value="fit" {{ old('resize_mode') === 'fit' ? 'selected' : '' }}>Keep Aspect Ratio (Fit)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-row" id="customDimsWrap" style="display: none;">
                                                <div class="form-group col-6 mb-0">
                                                    <label for="resize_width" class="small font-weight-bold mb-1">Width (px)</label>
                                                    <input type="number" name="resize_width" id="resize_width" min="64" max="2048"
                                                        value="{{ old('resize_width', 400) }}" class="form-control form-control-sm @error('resize_width') is-invalid @enderror">
                                                </div>
                                                <div class="form-group col-6 mb-0">
                                                    <label for="resize_height" class="small font-weight-bold mb-1">Height (px)</label>
                                                    <input type="number" name="resize_height" id="resize_height" min="64" max="2048"
                                                        value="{{ old('resize_height', 400) }}" class="form-control form-control-sm @error('resize_height') is-invalid @enderror">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Security &amp; Password</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Passwords must be at least 8 characters with uppercase, lowercase, a number, and a special character. Leave blank to keep your current password.
                        </p>
                        <form action="{{ route('admin.profile.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ old('name', $user->name) }}">
                            <input type="hidden" name="email" value="{{ old('email', $user->email) }}">
                            <input type="hidden" name="phone" value="{{ old('phone', $user->phone) }}">
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" name="password" id="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    autocomplete="new-password" placeholder="Enter new strong password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="password_confirmation">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="form-control" autocomplete="new-password"
                                    placeholder="Confirm new password">
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-lock mr-1"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('profile_photo');
            const fileLabel = document.getElementById('profile_photo_label');
            const previewImg = document.getElementById('avatarPreviewImg');
            const metaBadge = document.getElementById('avatarMetaBadge');
            const presetSelect = document.getElementById('resize_preset');
            const customModeWrap = document.getElementById('customModeWrap');
            const customDimsWrap = document.getElementById('customDimsWrap');
            const widthInput = document.getElementById('resize_width');
            const heightInput = document.getElementById('resize_height');
            const modeSelect = document.getElementById('resize_mode');
            const removeCheckbox = document.getElementById('remove_photo');

            let selectedRawDataUrl = null;
            let originalDims = null;

            function toggleCustomFields() {
                const isCustom = presetSelect.value === 'custom';
                customModeWrap.style.display = isCustom ? '' : 'none';
                customDimsWrap.style.display = isCustom ? '' : 'none';
            }

            function renderResizedPreview() {
                if (!selectedRawDataUrl) {
                    return;
                }

                const img = new Image();
                img.onload = function () {
                    const origW = img.naturalWidth;
                    const origH = img.naturalHeight;
                    originalDims = origW + '×' + origH;

                    const selectedOption = presetSelect.options[presetSelect.selectedIndex];
                    let targetW = parseInt(selectedOption.getAttribute('data-width') || '400', 10);
                    let targetH = parseInt(selectedOption.getAttribute('data-height') || '400', 10);
                    let mode = selectedOption.getAttribute('data-mode') || 'crop';

                    if (presetSelect.value === 'custom') {
                        targetW = Math.max(64, Math.min(2048, parseInt(widthInput.value || '400', 10)));
                        targetH = Math.max(64, Math.min(2048, parseInt(heightInput.value || '400', 10)));
                        mode = modeSelect.value || 'crop';
                    }

                    if (presetSelect.value === 'original' || mode === 'original') {
                        previewImg.src = selectedRawDataUrl;
                        previewImg.style.objectFit = 'contain';
                        metaBadge.textContent = 'Original: ' + originalDims + ' px';
                        return;
                    }

                    // Render scaled thumbnail preview matching server-side crop/fit behavior
                    const canvas = document.createElement('canvas');
                    let drawW = targetW;
                    let drawH = targetH;

                    if (mode === 'fit') {
                        const scale = Math.min(1, targetW / origW, targetH / origH);
                        drawW = Math.max(1, Math.round(origW * scale));
                        drawH = Math.max(1, Math.round(origH * scale));
                        canvas.width = drawW;
                        canvas.height = drawH;
                        canvas.getContext('2d').drawImage(img, 0, 0, drawW, drawH);
                        previewImg.style.objectFit = 'contain';
                    } else {
                        canvas.width = targetW;
                        canvas.height = targetH;
                        const srcScale = Math.max(targetW / origW, targetH / origH);
                        const srcCropW = targetW / srcScale;
                        const srcCropH = targetH / srcScale;
                        const sx = (origW - srcCropW) / 2;
                        const sy = (origH - srcCropH) / 2;
                        canvas.getContext('2d').drawImage(img, sx, sy, srcCropW, srcCropH, 0, 0, targetW, targetH);
                        previewImg.style.objectFit = 'cover';
                    }

                    previewImg.src = canvas.toDataURL('image/jpeg', 0.9);
                    metaBadge.textContent = originalDims + ' → ' + drawW + '×' + drawH + ' px';
                };
                img.src = selectedRawDataUrl;
            }

            if (fileInput) {
                fileInput.addEventListener('change', function (event) {
                    const file = event.target.files && event.target.files[0];
                    if (!file) {
                        return;
                    }
                    if (fileLabel) {
                        fileLabel.textContent = file.name;
                    }
                    if (removeCheckbox) {
                        removeCheckbox.checked = false;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        selectedRawDataUrl = e.target.result;
                        renderResizedPreview();
                    };
                    reader.readAsDataURL(file);
                });
            }

            if (presetSelect) {
                toggleCustomFields();
                presetSelect.addEventListener('change', function () {
                    toggleCustomFields();
                    renderResizedPreview();
                });
            }

            [widthInput, heightInput, modeSelect].forEach(el => {
                if (el) {
                    el.addEventListener('change', renderResizedPreview);
                    el.addEventListener('input', renderResizedPreview);
                }
            });

            if (removeCheckbox) {
                removeCheckbox.addEventListener('change', function () {
                    if (this.checked) {
                        selectedRawDataUrl = null;
                        previewImg.src = previewImg.getAttribute('data-default-src');
                        metaBadge.textContent = 'Photo will be removed';
                        if (fileInput) {
                            fileInput.value = '';
                        }
                        if (fileLabel) {
                            fileLabel.textContent = 'Choose image file...';
                        }
                    } else {
                        previewImg.src = previewImg.getAttribute('data-current-src');
                        metaBadge.textContent = 'Current Avatar';
                    }
                });
            }
        });
    </script>
@endpush
