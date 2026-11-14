@extends('layouts.student')

@section('title', 'Profile & Settings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h2 class="h4 fw-bold mb-1">{{ __('student.account_settings') }}</h2>
        <p class="text-muted small mb-0">{{ __('student.update_profile_desc') }}</p>
    </div>
</div>

<!-- Mobile-first Compact Tab Navigation -->
<ul class="nav nav-pills gap-2 flex-nowrap overflow-auto pb-2 mb-3" id="studentSettingsTabs" role="tablist">
    <li class="nav-item flex-shrink-0" role="presentation">
        <button class="nav-link active btn-sm px-3 py-2" id="profile-tab" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab" aria-controls="tab-profile" aria-selected="true">
            <i class="fa-solid fa-user me-1"></i>Profile
        </button>
    </li>
    <li class="nav-item flex-shrink-0" role="presentation">
        <button class="nav-link btn-sm px-3 py-2" id="security-tab" data-bs-toggle="pill" data-bs-target="#tab-security" type="button" role="tab" aria-controls="tab-security" aria-selected="false">
            <i class="fa-solid fa-shield-halved me-1"></i>Security
        </button>
    </li>
    <li class="nav-item flex-shrink-0" role="presentation">
        <button class="nav-link btn-sm px-3 py-2" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#tab-notifications" type="button" role="tab" aria-controls="tab-notifications" aria-selected="false">
            <i class="fa-solid fa-bell me-1"></i>Notifications
        </button>
    </li>
    <li class="nav-item flex-shrink-0" role="presentation">
        <button class="nav-link btn-sm px-3 py-2 text-danger" id="account-tab" data-bs-toggle="pill" data-bs-target="#tab-account" type="button" role="tab" aria-controls="tab-account" aria-selected="false">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>Account
        </button>
    </li>
</ul>

<div class="tab-content" id="studentSettingsTabContent">
    <!-- Profile Tab -->
    <div class="tab-pane fade show active" id="tab-profile" role="tabpanel" aria-labelledby="profile-tab">
        <form id="profileForm" action="{{ route('student.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="bg-white border rounded-3 p-3 text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <img
                                src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png') }}"
                                alt="{{ $user->name }}"
                                id="avatarPreview"
                                class="rounded-circle border"
                                style="width: 88px; height: 88px; object-fit: cover; cursor: pointer;"
                            >
                        </div>
                        <div class="fw-bold text-dark">{{ $user->name }}</div>
                        <div class="text-muted small mb-3">{{ $user->email }}</div>
                        <input type="file" name="profile_photo" id="avatarInput" accept="image/*" class="d-none">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="changeAvatarBtn">
                            <i class="fa-solid fa-camera me-1"></i>{{ __('student.change_photo') }}
                        </button>
                        <div class="form-text mt-2">{{ __('student.recommended_size') }}</div>
                        @error('profile_photo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="bg-white border rounded-3 p-3 p-md-4">
                        <h6 class="fw-bold mb-3">{{ __('student.profile_information') }}</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('student.full_name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required placeholder="{{ __('student.enter_full_name') }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('auth.email') }} <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required placeholder="{{ __('auth.enter_email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('student.phone_number') }}</label>
                                <input type="tel" name="phone" class="form-control form-control-sm @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="{{ __('instructor.enter_phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                            <button type="reset" class="btn btn-light btn-sm border">{{ __('frontend.cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fa-solid fa-floppy-disk me-1"></i>{{ __('student.save_changes') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Security Tab -->
    <div class="tab-pane fade" id="tab-security" role="tabpanel" aria-labelledby="security-tab">
        <div class="row">
            <div class="col-lg-8">
                <div class="bg-white border rounded-3 p-3 p-md-4">
                    <h6 class="fw-bold mb-1">{{ __('student.change_password') }}</h6>
                    <p class="text-muted small mb-3">{{ __('student.update_password_desc') }}</p>

                    <form id="passwordForm" action="{{ route('student.settings.update') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">{{ __('student.current_password') }}</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="{{ __('student.enter_current_password') }}" id="currentPassword">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('currentPassword', this)" aria-label="Toggle password visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">{{ __('student.leave_blank_password') }}</div>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('student.new_password') }}</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('student.enter_new_password') }}" id="newPassword">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('newPassword', this)" aria-label="Toggle password visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">{{ __('student.min_password_length') }}</div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{{ __('student.confirm_new_password') }}</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" name="password_confirmation" class="form-control" placeholder="{{ __('student.confirm_new_password') }}" id="confirmPassword">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword', this)" aria-label="Toggle password visibility">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                            <button type="reset" class="btn btn-light btn-sm border">{{ __('frontend.cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="fa-solid fa-key me-1"></i>{{ __('student.update_password_btn') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Tab -->
    <div class="tab-pane fade" id="tab-notifications" role="tabpanel" aria-labelledby="notifications-tab">
        <div class="row">
            <div class="col-lg-8">
                <div class="bg-white border rounded-3 p-3 p-md-4">
                    <h6 class="fw-bold mb-1">Notification Preferences</h6>
                    <p class="text-muted small mb-3">{{ __('student.manage_preferences') }}</p>

                    <form action="{{ route('student.settings.notifications.update') }}" method="POST" id="studentNotificationForm">
                        @csrf
                        @method('PUT')

                        <div class="list-group list-group-flush">
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold small">{{ __('student.email_notifications') }}</div>
                                    <div class="text-muted small">{{ __('student.email_notifications_desc') }}</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="email_notifications" value="0">
                                    <input class="form-check-input notification-toggle" type="checkbox" role="switch" name="email_notifications" value="1" {{ data_get($notificationSettings, 'email_notifications', true) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold small">{{ __('student.course_reminders') }}</div>
                                    <div class="text-muted small">{{ __('student.course_reminders_desc') }}</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="course_reminders" value="0">
                                    <input class="form-check-input notification-toggle" type="checkbox" role="switch" name="course_reminders" value="1" {{ data_get($notificationSettings, 'course_reminders', true) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold small">{{ __('student.progress_reports') }}</div>
                                    <div class="text-muted small">{{ __('student.progress_reports_desc') }}</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="progress_reports" value="0">
                                    <input class="form-check-input notification-toggle" type="checkbox" role="switch" name="progress_reports" value="1" {{ data_get($notificationSettings, 'progress_reports', true) ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="list-group-item px-0 pb-0 d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <div class="fw-semibold small">{{ __('student.marketing_emails') }}</div>
                                    <div class="text-muted small">{{ __('student.marketing_emails_desc') }}</div>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <input type="hidden" name="marketing_emails" value="0">
                                    <input class="form-check-input notification-toggle" type="checkbox" role="switch" name="marketing_emails" value="1" {{ data_get($notificationSettings, 'marketing_emails', false) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Account / Danger Zone Tab -->
    <div class="tab-pane fade" id="tab-account" role="tabpanel" aria-labelledby="account-tab">
        <div class="row">
            <div class="col-lg-8">
                <div class="bg-white border border-danger-subtle rounded-3 p-3 p-md-4">
                    <h6 class="fw-bold text-danger mb-1">{{ __('student.danger_zone') }}</h6>
                    <p class="text-muted small mb-3">{{ __('student.destructive_actions') }}</p>

                    @isset($latestDeletionRequest)
                        @php($deletionStatus = $latestDeletionRequest->status)
                        <div class="alert {{ $deletionStatus === 'pending' ? 'alert-warning' : ($deletionStatus === 'rejected' ? 'alert-danger' : 'alert-success') }} small mb-3" role="alert">
                            @if ($deletionStatus === 'pending')
                                <strong>{{ __('student.awaiting_approval') }}:</strong> Your account deletion request was submitted on {{ $latestDeletionRequest->created_at->format('M d, Y') }}.
                            @elseif($deletionStatus === 'rejected')
                                <strong>Request rejected:</strong> {{ $latestDeletionRequest->note ?? 'Please contact support for more details.' }}
                            @elseif($deletionStatus === 'approved')
                                <strong>Approved:</strong> This request has already been processed.
                            @endif
                        </div>
                    @endisset

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 bg-danger-subtle rounded-3">
                        <div>
                            <div class="fw-semibold text-dark small">{{ __('student.delete_account') }}</div>
                            <div class="text-muted small">{{ __('student.delete_account_desc') }}</div>
                        </div>
                        @if (isset($deletionStatus) && $deletionStatus === 'pending')
                            <button type="button" class="btn btn-outline-danger btn-sm" disabled>
                                <i class="fa-solid fa-clock me-1"></i>{{ __('student.awaiting_approval') }}
                            </button>
                        @else
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteAccount()">
                                <i class="fa-solid fa-trash me-1"></i>{{ __('student.delete_account') }}
                            </button>
                        @endif
                    </div>

                    <form id="student-delete-account-form" action="{{ route('student.settings.account.destroy') }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
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
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');

    if (avatarPreview && avatarInput) {
        avatarPreview.addEventListener('click', function () { avatarInput.click(); });
    }
    if (changeAvatarBtn && avatarInput) {
        changeAvatarBtn.addEventListener('click', function () { avatarInput.click(); });
    }
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                avatarPreview.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    const notificationForm = document.getElementById('studentNotificationForm');
    if (notificationForm) {
        notificationForm.addEventListener('change', function (event) {
            if (event.target.classList.contains('notification-toggle')) {
                notificationForm.submit();
            }
        });
    }

    const TAB_KEY = 'out_student_settings_tab';
    const tabButtons = document.querySelectorAll('#studentSettingsTabs button[data-bs-toggle="pill"]');
    try {
        const savedTab = localStorage.getItem(TAB_KEY);
        if (savedTab) {
            const btn = document.querySelector('#studentSettingsTabs button[data-bs-target="' + savedTab + '"]');
            if (btn && window.bootstrap) {
                bootstrap.Tab.getOrCreateInstance(btn).show();
            }
        }
    } catch (e) {}

    tabButtons.forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            try { localStorage.setItem(TAB_KEY, btn.dataset.bsTarget); } catch (e) {}
        });
    });
});

function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function confirmDeleteAccount() {
    const form = document.getElementById('student-delete-account-form');
    if (!form) return;

    Swal.fire({
        title: @json(__('student.delete_account_title')),
        text: @json(__('student.delete_account_desc')),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: @json(__('student.yes_delete_it')),
        cancelButtonText: @json(__('frontend.cancel')),
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#1d4ed8'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: @json(__('student.submitting_request')),
                text: @json(__('student.please_wait_deletion')),
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                    form.submit();
                }
            });
        }
    });
}
</script>
@endpush
